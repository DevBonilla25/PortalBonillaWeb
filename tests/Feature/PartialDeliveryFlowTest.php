<?php

use App\Actions\Deliveries\CreatePartialTicketAction;
use App\Actions\Deliveries\RegisterDeliveryEvidenceAction;
use App\Actions\Tickets\AssignTicketResourcesAction;
use App\Enums\DeliveryType;
use App\Enums\DriverStatus;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\DeliveryEvidence;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketLoadingService;
use App\Services\WarehousePanelService;
use App\Support\Warehouse\WarehousePanelColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
it('closes the delivered ticket and creates a warehouse ticket for missing products', function () {
    $company = Company::query()->create(['name' => 'Empresa Parciales', 'ruc' => '1799999999011', 'is_active' => true]);
    $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $user->id, 'status' => DriverStatus::Available, 'is_active' => true]);
    $ticket = Ticket::query()->create(['company_id' => $company->id, 'ticket_code' => 'FAC-4587', 'customer_name' => 'Cliente parcial', 'delivery_address' => 'Destino', 'delivery_type' => DeliveryType::Distribution, 'current_driver_id' => $driver->id, 'status' => TicketStatus::Unloading]);
    $cement = $ticket->items()->create(['product_name' => 'Cemento', 'quantity' => 10, 'loaded_quantity' => 10]);
    $paint = $ticket->items()->create(['product_name' => 'Pintura', 'quantity' => 5, 'loaded_quantity' => 3]);
    $evidence = app(RegisterDeliveryEvidenceAction::class)->execute($ticket, $driver, [
        'received_by_name' => 'Cliente',
        'items' => [
            ['ticket_item_id' => $cement->id, 'delivered_quantity' => 10],
            ['ticket_item_id' => $paint->id, 'delivered_quantity' => 3],
        ],
    ]);
    $child = Ticket::query()->where('parent_ticket_id', $ticket->id)->sole();
    expect($ticket->refresh()->status)->toBe(TicketStatus::Delivered)
        ->and($ticket->fulfillment_status)->toBe('partial')
        ->and($child)->not->toBeNull()
        ->and($child->ticket_code)->toBe('FAC-4587-01')
        ->and($child->parent_ticket_id)->toBe($ticket->id)
        ->and($child->status)->toBe(TicketStatus::PendingPartialDispatch)
        ->and((float) $child->items()->sole()->quantity)->toBe(2.0)
        ->and($evidence->items()->count())->toBe(2);
    $column = collect(WarehousePanelColumn::all())->firstWhere('key', 'partials');
    expect($column->matchesStatus($child->status))->toBeTrue();

    $columns = collect(WarehousePanelColumn::all())->keyBy('key');
    expect($columns)->not->toHaveKey('preparation')
        ->and($columns)->not->toHaveKey('dispatched')
        ->and($columns->get('loading')->matchesStatus(TicketStatus::Loading))->toBeTrue()
        ->and($columns->get('loading')->matchesStatus(TicketStatus::Loaded))->toBeTrue();
});
it('does not create a derived ticket when every loaded product was delivered', function () {
    $company = Company::query()->create(['name' => 'Empresa Completa', 'ruc' => '1799999999012', 'is_active' => true]);
    $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $user->id, 'status' => DriverStatus::Available, 'is_active' => true]);
    $ticket = Ticket::query()->create(['company_id' => $company->id, 'ticket_code' => 'FAC-5000', 'customer_name' => 'Cliente', 'delivery_address' => 'Destino', 'delivery_type' => DeliveryType::Distribution, 'current_driver_id' => $driver->id, 'status' => TicketStatus::Unloading]);
    $item = $ticket->items()->create(['product_name' => 'Producto', 'quantity' => 4, 'loaded_quantity' => 4]);
    $evidence = DeliveryEvidence::query()->create(['ticket_id' => $ticket->id, 'driver_id' => $driver->id, 'received_by_name' => 'Cliente', 'occurred_at' => now()]);
    $child = app(CreatePartialTicketAction::class)->execute($ticket, $evidence, [['ticket_item_id' => $item->id, 'delivered_quantity' => 4]]);
    expect($child)->toBeNull()->and($ticket->refresh()->fulfillment_status)->toBe('complete');
});

it('moves an assigned warehouse ticket directly to loading', function () {
    $company = Company::query()->create(['name' => 'Empresa Carga', 'ruc' => '1799999999013', 'is_active' => true]);
    $warehouseUser = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $driverUser = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $driverUser->id, 'status' => DriverStatus::Available, 'is_active' => true]);
    $ticket = Ticket::query()->create(['company_id' => $company->id, 'ticket_code' => 'FAC-CARGA', 'customer_name' => 'Cliente carga', 'delivery_address' => 'Destino', 'delivery_type' => DeliveryType::Distribution, 'status' => TicketStatus::SentToWarehouse]);

    app(AssignTicketResourcesAction::class)->execute($ticket, $driver->id, null, $warehouseUser->id, [], $warehouseUser);

    expect($ticket->refresh()->status)->toBe(TicketStatus::Loading);
});

it('creates one pending ticket when the loading checklist is partial', function () {
    $company = Company::query()->create(['name' => 'Empresa Checklist', 'ruc' => '1799999999014', 'is_active' => true]);
    $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $user->id, 'status' => DriverStatus::Available, 'is_active' => true]);
    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'FAC-CHECK',
        'external_source' => 'morfeus',
        'external_source_type' => 'pending_invoice',
        'external_invoice_id' => 1713680,
        'external_document_number' => '002-105-000155998',
        'external_warehouse_id' => 4,
        'customer_name' => 'Cliente checklist',
        'delivery_address' => 'Destino',
        'delivery_type' => DeliveryType::Distribution,
        'status' => TicketStatus::Loaded,
    ]);
    $item = $ticket->items()->create(['product_name' => 'Producto parcial', 'quantity' => 5]);

    app(TicketLoadingService::class)->reviewChecklist($ticket, [[
        'id' => $item->id,
        'product_name' => $item->product_name,
        'quantity' => 5,
        'loaded_quantity' => 3,
        'is_loaded' => true,
        'load_observation' => 'Dos unidades pendientes',
    ]], $user);

    $child = Ticket::query()->where('source_ticket_id', $ticket->id)->sole();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Dispatched)
        ->and($ticket->fulfillment_status)->toBe('partial')
        ->and($child->ticket_code)->toBe('FAC-CHECK-01')
        ->and($child->external_invoice_id)->toBeNull()
        ->and($child->external_document_number)->toBe('002-105-000155998')
        ->and($child->status)->toBe(TicketStatus::PendingPartialDispatch)
        ->and((float) $child->items()->sole()->quantity)->toBe(2.0);

    $evidence = DeliveryEvidence::query()->create([
        'ticket_id' => $ticket->id,
        'driver_id' => $driver->id,
        'received_by_name' => 'Cliente',
        'occurred_at' => now(),
    ]);

    app(CreatePartialTicketAction::class)->execute($ticket, $evidence, [[
        'ticket_item_id' => $item->id,
        'delivered_quantity' => 3,
    ]]);

    expect(Ticket::query()->where('source_ticket_id', $ticket->id)->count())->toBe(1);
});

it('hides assignment on loading cards while preserving assignment in ticket detail statuses', function () {
    $ticket = new Ticket(['status' => TicketStatus::Loading]);

    expect(app(WarehousePanelService::class)->canAssign($ticket))->toBeFalse();

    $source = file_get_contents(app_path('Filament/Resources/Tickets/Actions/TicketRecordActions.php'));
    expect($source)->toContain('TicketStatus::Loading', 'TicketStatus::Loaded');
});

it('shows fulfillment as pending until the delivery result is known', function () {
    $ticket = new Ticket(['status' => TicketStatus::Loading, 'fulfillment_status' => 'complete']);
    expect($ticket->fulfillmentLabel())->toBe('Pendiente');

    $ticket->status = TicketStatus::Delivered;
    expect($ticket->fulfillmentLabel())->toBe('Completa');

    $ticket->fulfillment_status = 'partial';
    expect($ticket->fulfillmentLabel())->toBe('Entrega parcial');
});
