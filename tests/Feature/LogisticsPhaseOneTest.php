<?php

use App\Enums\DriverStatus;
use App\Enums\TicketStatus;
use App\Enums\VehicleStatus;
use App\Models\Company;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Zone;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates logistics catalog records with enum casts', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999001',
        'is_active' => true,
    ]);

    $zone = Zone::query()->create([
        'company_id' => $company->id,
        'code' => 'NORTE',
        'name' => 'Zona norte',
        'is_active' => true,
    ]);

    $vehicle = Vehicle::query()->create([
        'company_id' => $company->id,
        'code' => 'VH-001',
        'plate' => 'ABC-1234',
        'status' => VehicleStatus::Available,
        'is_active' => true,
    ]);

    $user = User::factory()->create(['company_id' => $company->id]);

    $driverProfile = DriverProfile::query()->create([
        'user_id' => $user->id,
        'default_vehicle_id' => $vehicle->id,
        'status' => DriverStatus::Available,
        'is_active' => true,
    ]);

    expect($zone->company->is($company))->toBeTrue()
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::Available)
        ->and($driverProfile->refresh()->status)->toBe(DriverStatus::Available)
        ->and($driverProfile->defaultVehicle->is($vehicle))->toBeTrue();
});

it('validates ticket status transitions from the workflow service', function () {
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999001',
        'is_active' => true,
    ]);

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'ticket_code' => 'TCK-001',
        'customer_name' => 'Cliente Demo',
        'delivery_address' => 'Direccion demo',
        'status' => TicketStatus::Created,
    ]);

    $service = app(TicketWorkflowService::class);

    $service->transition($ticket, TicketStatus::SentToWarehouse);

    expect($ticket->refresh()->status)->toBe(TicketStatus::SentToWarehouse);

    $service->transition($ticket, TicketStatus::InRoute);
})->throws(DomainException::class);
