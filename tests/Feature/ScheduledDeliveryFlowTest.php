<?php

use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Actions\Tickets\ScheduleTicketDeliveryAction;
use App\Enums\DeliveryType;
use App\Enums\DriverStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Enums\WarehouseType;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Company;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehousePanelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-02 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function scheduledTicketContext(): array
{
    $company = Company::query()->create(['name' => 'Empresa Programada', 'ruc' => fake()->unique()->numerify('#############'), 'is_active' => true]);
    $warehouse = Warehouse::query()->create([
        'company_id' => $company->id,
        'code' => fake()->unique()->bothify('BOD-###'),
        'name' => 'Bodega Principal',
        'type' => WarehouseType::General,
        'is_active' => true,
    ]);

    return [$company, $warehouse];
}

it('sets today as the default delivery date', function () {
    [$company] = scheduledTicketContext();

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'PROG-001',
        'customer_name' => 'Cliente',
        'delivery_address' => 'Destino',
        'delivery_type' => DeliveryType::Distribution,
    ]);

    expect($ticket->scheduled_delivery_date->toDateString())->toBe('2026-11-02')
        ->and($ticket->deliveryScheduleLabel())->toBe('Hoy');
});

it('moves an active ticket back to warehouse when delivery is postponed', function () {
    [$company, $warehouse] = scheduledTicketContext();
    $warehouseUser = User::factory()->create(['company_id' => $company->id]);
    $driverUser = User::factory()->create(['company_id' => $company->id]);
    $driver = DriverProfile::query()->create(['user_id' => $driverUser->id, 'status' => DriverStatus::Available, 'is_active' => true]);
    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'ticket_code' => 'PROG-002',
        'customer_name' => 'Cliente',
        'delivery_address' => 'Destino',
        'delivery_type' => DeliveryType::Distribution,
        'status' => TicketStatus::SentToWarehouse,
    ]);

    app(AssignTicketResourcesAction::class)->execute($ticket, $driver->id, null, $warehouseUser->id, [], $warehouseUser);
    app(ScheduleTicketDeliveryAction::class)->execute($ticket, '2026-11-10', 'Cliente solicita entrega posterior', $warehouseUser);

    expect($ticket->refresh()->status)->toBe(TicketStatus::SentToWarehouse)
        ->and($ticket->current_driver_id)->toBeNull()
        ->and($ticket->scheduled_delivery_date->toDateString())->toBe('2026-11-10')
        ->and($ticket->isScheduledForFuture())->toBeTrue()
        ->and($ticket->events()->where('event_type', TicketEventType::DeliveryDateChanged)->exists())->toBeTrue();
});

it('lists future deliveries as pending and releases them automatically on their date', function () {
    [$company, $warehouse] = scheduledTicketContext();
    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'ticket_code' => 'PROG-003',
        'customer_name' => 'Cliente',
        'delivery_address' => 'Destino',
        'delivery_type' => DeliveryType::Distribution,
        'status' => TicketStatus::SentToWarehouse,
        'scheduled_delivery_date' => '2026-11-03',
    ]);

    $service = app(WarehousePanelService::class);
    $columns = $service->ticketsByColumn(warehouseId: $warehouse->id);

    expect($columns->get('partials')->pluck('id'))->toContain($ticket->id)
        ->and($columns->get('received')->pluck('id'))->not->toContain($ticket->id)
        ->and($service->canAssign($ticket))->toBeFalse();

    $warehouseUser = User::factory()->create(['company_id' => $company->id]);
    expect(fn () => app(AssignTicketResourcesAction::class)->execute(
        $ticket,
        null,
        null,
        $warehouseUser->id,
        [],
        $warehouseUser,
    ))->toThrow(DomainException::class, 'fecha futura');

    Carbon::setTestNow('2026-11-03 08:00:00');
    $ticket->refresh();
    $columns = $service->ticketsByColumn(warehouseId: $warehouse->id);

    expect($columns->get('partials')->pluck('id'))->not->toContain($ticket->id)
        ->and($columns->get('received')->pluck('id'))->toContain($ticket->id)
        ->and($service->canAssign($ticket))->toBeTrue();
});

it('filters tickets by scheduled delivery date status', function () {
    [$company] = scheduledTicketContext();
    $base = [
        'company_id' => $company->id,
        'customer_name' => 'Cliente',
        'delivery_address' => 'Destino',
        'delivery_type' => DeliveryType::Distribution,
    ];

    Ticket::query()->create([...$base, 'ticket_code' => 'DATE-1', 'scheduled_delivery_date' => '2026-11-02']);
    Ticket::query()->create([...$base, 'ticket_code' => 'DATE-2', 'scheduled_delivery_date' => '2026-11-04']);
    Ticket::query()->create([...$base, 'ticket_code' => 'DATE-3', 'scheduled_delivery_date' => '2026-11-01']);

    expect(TicketsTable::applyDeliveryScheduleFilter(Ticket::query(), 'today')->pluck('ticket_code')->all())->toBe(['DATE-1'])
        ->and(TicketsTable::applyDeliveryScheduleFilter(Ticket::query(), 'future')->pluck('ticket_code')->all())->toBe(['DATE-2'])
        ->and(TicketsTable::applyDeliveryScheduleFilter(Ticket::query(), 'overdue')->pluck('ticket_code')->all())->toBe(['DATE-3']);
});
