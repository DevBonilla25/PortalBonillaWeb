<?php

use App\Enums\DeliveryType;
use App\Enums\DriverStatus;
use App\Enums\LogisticOperationStatus;
use App\Models\Company;
use App\Models\DriverProfile;
use App\Models\LogisticOperation;
use App\Models\Subzone;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function logisticOperationFixtures(): array
{
    $company = Company::query()->create(['name' => 'Bonilla', 'ruc' => '1799999999002', 'is_active' => true]);
    $supervisor = User::factory()->create(['company_id' => $company->id]);
    $driverUser = User::factory()->create(['company_id' => $company->id]);
    Role::query()->firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
    $driverUser->assignRole('driver');
    $driver = DriverProfile::query()->create(['user_id' => $driverUser->id, 'status' => DriverStatus::Assigned, 'is_active' => true]);
    $operation = LogisticOperation::query()->create([
        'company_id' => $company->id, 'driver_id' => $driver->id, 'created_by' => $supervisor->id,
        'origin' => 'La Mana', 'destination' => 'Otavalo', 'plant_name' => 'Planta Otavalo',
    ]);

    return [$company, $supervisor, $driverUser, $driver, $operation];
}

it('transitions an operation through controlled business actions', function () {
    [, $supervisor, , , $operation] = logisticOperationFixtures();
    Sanctum::actingAs($supervisor);

    $this->postJson("/api/v1/logistic-operations/{$operation->id}/transition", ['action' => 'start_trip'])
        ->assertOk()->assertJsonPath('data.status', LogisticOperationStatus::TravelingToPlant->value);

    $this->postJson("/api/v1/logistic-operations/{$operation->id}/transition", ['action' => 'start_queue'])
        ->assertUnprocessable();

    expect($operation->refresh()->started_at)->not->toBeNull()
        ->and($operation->status)->toBe(LogisticOperationStatus::TravelingToPlant)
        ->and($operation->transitions()->value('action'))->toBe('start_trip');
});

it('only exposes assigned operations to a driver', function () {
    [, , $driverUser, , $operation] = logisticOperationFixtures();
    Sanctum::actingAs($driverUser);

    $this->getJson('/api/v1/logistic-operations')->assertOk()->assertJsonPath('data.0.id', $operation->id);
});

it('keeps distribution metadata separate from the ticket workflow', function () {
    [$company] = logisticOperationFixtures();
    $zone = Zone::query()->create(['company_id' => $company->id, 'code' => 'DIST', 'name' => 'Distribucion', 'is_active' => true]);
    $subzone = Subzone::query()->create(['zone_id' => $zone->id, 'name' => 'Centro', 'is_active' => true]);
    $ticket = Ticket::query()->create([
        'company_id' => $company->id, 'zone_id' => $zone->id, 'subzone_id' => $subzone->id,
        'delivery_type' => DeliveryType::Distribution, 'google_maps_url' => 'https://maps.app.goo.gl/example',
        'ticket_code' => 'DIST-001', 'customer_name' => 'Cliente', 'delivery_address' => 'Destino',
    ]);

    expect($ticket->refresh()->delivery_type)->toBe(DeliveryType::Distribution)
        ->and($ticket->subzone->is($subzone))->toBeTrue();
});

it('requires a plant exit document before starting the return trip', function () {
    [, $supervisor, , , $operation] = logisticOperationFixtures();
    Sanctum::actingAs($supervisor);
    $operation->forceFill(['status' => LogisticOperationStatus::Loaded])->save();

    $this->postJson("/api/v1/logistic-operations/{$operation->id}/transition", ['action' => 'start_return'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Debe adjuntar la guia o documento entregado en planta antes de registrar la salida.');

    $operation->mediaAttachments()->create([
        'collection' => 'plant_exit_document', 'disk' => 'public', 'path' => 'test/guia.pdf',
    ]);

    $this->postJson("/api/v1/logistic-operations/{$operation->id}/transition", ['action' => 'start_return'])
        ->assertOk()
        ->assertJsonPath('data.status', LogisticOperationStatus::ReturningToOrigin->value);
});

it('records and finishes a route stop without changing operation status', function () {
    [, , $driverUser, , $operation] = logisticOperationFixtures();
    Sanctum::actingAs($driverUser);
    $operation->forceFill(['status' => LogisticOperationStatus::TravelingToPlant])->save();

    $response = $this->postJson("/api/v1/logistic-operations/{$operation->id}/stops", [
        'reason' => 'food', 'notes' => 'Parada para almorzar',
    ])->assertCreated()->assertJsonPath('data.reason', 'food');

    $stopId = $response->json('data.id');
    expect($operation->refresh()->status)->toBe(LogisticOperationStatus::TravelingToPlant);

    $this->postJson("/api/v1/logistic-operations/{$operation->id}/stops/{$stopId}/finish")
        ->assertOk()
        ->assertJsonPath('data.id', $stopId);

    expect($operation->stops()->find($stopId)->finished_at)->not->toBeNull();
});
