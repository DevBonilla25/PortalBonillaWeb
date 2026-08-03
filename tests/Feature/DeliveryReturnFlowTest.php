<?php

use App\Actions\Tickets\PrepareTicketReassignmentAction;
use App\Actions\Tickets\ReceiveTicketReturnAction;
use App\Enums\DriverStatus;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\DriverProfile;
use App\Models\NoveltyReason;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function deliveryReturnFixtures(): array
{
    $company = Company::query()->create(['name' => 'Empresa Retornos', 'ruc' => '1799999999002', 'is_active' => true]);
    $user = User::factory()->create(['company_id' => $company->id, 'is_active' => true]);
    $vehicle = Vehicle::query()->create(['company_id' => $company->id, 'code' => 'RET-01', 'plate' => 'RET-001', 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $user->id, 'default_vehicle_id' => $vehicle->id, 'status' => DriverStatus::Assigned, 'is_active' => true]);
    $ticket = Ticket::query()->create([
        'company_id' => $company->id, 'current_driver_id' => $driver->id, 'current_vehicle_id' => $vehicle->id,
        'ticket_code' => 'TCK-RETURN-001', 'customer_name' => 'Cliente retorno',
        'delivery_address' => 'Dirección retorno', 'status' => TicketStatus::Dispatched,
    ]);
    $reason = NoveltyReason::query()->create([
        'company_id' => $company->id, 'code' => 'client_unavailable', 'name' => 'Cliente ausente',
        'requires_photo' => false, 'is_active' => true,
    ]);

    return [$user, $driver, $ticket, $reason];
}

it('requires arrival and unloading before delivery evidence', function () {
    [$user, , $ticket] = deliveryReturnFixtures();
    Sanctum::actingAs($user, ['driver']);

    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/change-status", ['status' => 'in_route'])->assertOk();
    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/change-status", ['status' => 'at_destination'])->assertOk();
    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/change-status", ['status' => 'unloading'])
        ->assertOk()
        ->assertJsonPath('data.status', 'unloading');

    expect($ticket->refresh()->arrived_destination_at)->not->toBeNull()
        ->and($ticket->unloading_at)->not->toBeNull()
        ->and($ticket->deliveryAttempts()->count())->toBe(1);
});

it('closes a failed delivery as returning and lets warehouse prepare reassignment', function () {
    [$user, , $ticket, $reason] = deliveryReturnFixtures();
    Sanctum::actingAs($user, ['driver']);

    foreach (['in_route', 'at_destination'] as $status) {
        $this->postJson("/api/v1/driver/tickets/{$ticket->id}/change-status", compact('status'))->assertOk();
    }

    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/failed-delivery", [
        'novelty_reason_id' => $reason->id,
        'description' => 'Cliente no se encontraba.',
        'goods_remain_on_vehicle' => true,
    ])->assertOk();

    expect($ticket->refresh()->status)->toBe(TicketStatus::Returning)
        ->and($ticket->deliveryAttempts()->first()->status)->toBe('failed_returning');

    $warehouseUser = User::factory()->create(['company_id' => $ticket->company_id, 'is_active' => true]);
    app(ReceiveTicketReturnAction::class)->execute($ticket, $warehouseUser, 'Mercadería completa.');
    expect($ticket->refresh()->status)->toBe(TicketStatus::ArrivedBack);

    app(PrepareTicketReassignmentAction::class)->execute($ticket, $warehouseUser);
    expect($ticket->refresh()->status)->toBe(TicketStatus::PendingReassignment)
        ->and($ticket->current_driver_id)->toBeNull()
        ->and($ticket->current_vehicle_id)->toBeNull();
});
