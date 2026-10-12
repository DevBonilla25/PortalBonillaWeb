<?php

use App\Enums\DriverStatus;
use App\Enums\PickupOrderStatus;
use App\Models\Company;
use App\Models\DeliveryRoute;
use App\Models\DriverProfile;
use App\Models\PickupOrder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function pickupFlowFixtures(): array
{
    $company = Company::query()->create(['name' => 'Retiros', 'ruc' => '1799999999010', 'is_active' => true]);
    $supervisor = User::factory()->create(['company_id' => $company->id]);
    $driverUser = User::factory()->create(['company_id' => $company->id]);
    Role::query()->firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
    $driverUser->assignRole('driver');
    $vehicle = Vehicle::query()->create(['company_id' => $company->id, 'code' => 'RET-01', 'plate' => 'RET-100', 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $driverUser->id, 'default_vehicle_id' => $vehicle->id, 'status' => DriverStatus::Assigned, 'is_active' => true]);
    $route = DeliveryRoute::query()->create(['company_id' => $company->id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'created_by' => $supervisor->id]);
    $pickup = PickupOrder::query()->create([
        'company_id' => $company->id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id,
        'created_by' => $supervisor->id, 'code' => 'RET-000001', 'pickup_name' => 'Proveedor de repuestos',
        'pickup_address' => 'Av. Principal 123', 'item_description' => 'Dos cajas de repuestos',
    ]);
    $route->tasks()->create(['sequence' => 1, 'task_type' => 'pickup', 'pickup_order_id' => $pickup->id]);
    $route->forceFill(['status' => 'in_progress', 'started_at' => now()])->save();

    return [$supervisor, $driverUser, $route, $pickup];
}

it('advances an assigned pickup without driver acceptance', function () {
    [, $driverUser, $route, $pickup] = pickupFlowFixtures();
    Sanctum::actingAs($driverUser);

    foreach (['start_trip', 'arrive_pickup', 'start_loading', 'complete_pickup'] as $action) {
        $this->postJson("/api/v1/driver/pickup-orders/{$pickup->id}/events", [
            'action' => $action, 'latitude' => -0.18, 'longitude' => -78.46,
        ])->assertOk();
    }

    expect($pickup->refresh()->status)->toBe(PickupOrderStatus::PickedUp)
        ->and($route->tasks()->first()->pickupOrder->is($pickup))->toBeTrue();
});

it('removes a pickup from the driver list after warehouse receipt', function () {
    [, $driverUser, , $pickup] = pickupFlowFixtures();
    Sanctum::actingAs($driverUser);

    $pickup->forceFill(['status' => PickupOrderStatus::TransportingToWarehouse])->save();
    $this->getJson('/api/v1/driver/pickup-orders')
        ->assertOk()
        ->assertJsonPath('data.0.id', $pickup->id);

    $pickup->forceFill(['status' => PickupOrderStatus::ReceivedAtWarehouse, 'received_at' => now()])->save();
    $this->getJson('/api/v1/driver/pickup-orders')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects a pickup vehicle that is not assigned to the driver', function () {
    [$supervisor, , , $pickup] = pickupFlowFixtures();
    $otherVehicle = Vehicle::query()->create([
        'company_id' => $pickup->company_id, 'code' => 'RET-02', 'plate' => 'RET-200', 'is_active' => true,
    ]);

    expect(fn () => PickupOrder::query()->create([
        'company_id' => $pickup->company_id,
        'warehouse_id' => $pickup->warehouse_id,
        'driver_id' => $pickup->driver_id,
        'vehicle_id' => $otherVehicle->id,
        'created_by' => $supervisor->id,
        'code' => 'RET-000002',
        'pickup_name' => 'Proveedor incompatible',
        'pickup_address' => 'Direccion de prueba',
        'item_description' => 'Material de prueba',
    ]))->toThrow(ValidationException::class, 'El vehiculo debe ser el asignado al chofer.');
});

it('prevents starting a pickup before it belongs to an active route', function () {
    [, $driverUser, $route, $pickup] = pickupFlowFixtures();
    $route->tasks()->delete();
    Sanctum::actingAs($driverUser);

    $this->postJson("/api/v1/driver/pickup-orders/{$pickup->id}/events", ['action' => 'start_trip'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Debes iniciar la ruta antes de gestionar esta orden de retiro.');

    expect($pickup->refresh()->status)->toBe(PickupOrderStatus::Assigned);
});

it('prevents closing a route until picked up goods are received at warehouse', function () {
    [$supervisor, , $route, $pickup] = pickupFlowFixtures();
    $pickup->forceFill(['status' => PickupOrderStatus::PickedUp, 'picked_up_at' => now()])->save();
    $route->forceFill(['status' => 'at_warehouse', 'started_at' => now(), 'returning_at' => now(), 'arrived_warehouse_at' => now()])->save();
    Sanctum::actingAs($supervisor);

    $this->postJson("/api/v1/delivery-routes/{$route->id}/events", ['type' => 'complete'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'La ruta tiene retiros pendientes de recibir en bodega.');
});

it('returns mixed route tasks in operational sequence', function () {
    [, $driverUser, $route, $pickup] = pickupFlowFixtures();
    Sanctum::actingAs($driverUser);

    $this->getJson("/api/v1/delivery-routes/{$route->id}")
        ->assertOk()
        ->assertJsonPath('data.tasks.0.type', 'pickup')
        ->assertJsonPath('data.tasks.0.pickup_order.code', $pickup->code)
        ->assertJsonMissingPath('data.tasks.0.pickup');
});
