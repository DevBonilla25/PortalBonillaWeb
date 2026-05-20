<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Pages\WarehousePanel;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use App\Services\WarehousePanelService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('groups warehouse tickets into kanban columns', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999001',
        'is_active' => true,
    ]);

    $zone = Zone::query()->create([
        'company_id' => $company->id,
        'code' => 'NORTE',
        'name' => 'Norte',
        'is_active' => true,
    ]);

    Ticket::query()->create([
        'company_id' => $company->id,
        'zone_id' => $zone->id,
        'ticket_code' => 'TCK-100',
        'customer_name' => 'Cliente A',
        'delivery_address' => 'Calle 1',
        'status' => TicketStatus::SentToWarehouse,
    ]);

    Ticket::query()->create([
        'company_id' => $company->id,
        'zone_id' => $zone->id,
        'ticket_code' => 'TCK-101',
        'customer_name' => 'Cliente B',
        'delivery_address' => 'Calle 2',
        'status' => TicketStatus::Picking,
        'priority' => TicketPriority::High,
    ]);

    $grouped = app(WarehousePanelService::class)->ticketsByColumn();

    expect($grouped->get('received'))->toHaveCount(1)
        ->and($grouped->get('preparation'))->toHaveCount(1)
        ->and($grouped->get('loading'))->toHaveCount(0);
});

it('does not advance from sent to warehouse without assignment', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999003',
        'is_active' => true,
    ]);

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'TCK-201',
        'customer_name' => 'Cliente Demo',
        'delivery_address' => 'Calle 4',
        'status' => TicketStatus::SentToWarehouse,
    ]);

    expect(app(WarehousePanelService::class)->nextAdvanceStatus($ticket))->toBeNull();
});

it('resolves the next warehouse advance status from preparation to loading', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999002',
        'is_active' => true,
    ]);

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'TCK-200',
        'customer_name' => 'Cliente Demo',
        'delivery_address' => 'Calle 3',
        'status' => TicketStatus::Picking,
    ]);

    $service = app(WarehousePanelService::class);

    expect($service->nextAdvanceStatus($ticket))->toBe(TicketStatus::Loading);
});

it('requires checklist before dispatch when loading has items', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999004',
        'is_active' => true,
    ]);

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'TCK-202',
        'customer_name' => 'Cliente Demo',
        'delivery_address' => 'Calle 5',
        'status' => TicketStatus::Loading,
    ]);

    $ticket->items()->create([
        'product_name' => 'Cemento',
        'quantity' => 10,
    ]);

    $service = app(WarehousePanelService::class);

    expect($service->nextAdvanceStatus($ticket))->toBeNull()
        ->and($service->canReviewLoadingChecklist($ticket))->toBeTrue();
});

it('allows warehouse operators to access the warehouse panel page', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('ViewAny:Ticket');

    $this->actingAs($user)
        ->get(WarehousePanel::getUrl())
        ->assertSuccessful();
});
