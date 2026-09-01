<?php

use App\Enums\DriverStatus;
use App\Enums\TicketStatus;
use App\Enums\WarehouseType;
use App\Models\Company;
use App\Models\DeliveryRoute;
use App\Models\DriverProfile;
use App\Models\PickupOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function automaticRouteFixtures(): array
{
    $company = Company::query()->create(['name' => 'Rutas automaticas', 'ruc' => '1799999999003', 'is_active' => true]);
    $driverUser = User::factory()->create(['company_id' => $company->id]);
    Role::query()->firstOrCreate(['name' => 'driver', 'guard_name' => 'web']);
    $driverUser->assignRole('driver');
    $vehicle = Vehicle::query()->create(['company_id' => $company->id, 'code' => 'RUTA-01', 'plate' => 'ABC-1234', 'is_active' => true]);
    $driver = DriverProfile::query()->create(['user_id' => $driverUser->id, 'default_vehicle_id' => $vehicle->id, 'status' => DriverStatus::Assigned, 'is_active' => true]);
    $tickets = collect([1, 2])->map(fn (int $number) => Ticket::query()->create([
        'company_id' => $company->id, 'current_driver_id' => $driver->id, 'current_vehicle_id' => $vehicle->id,
        'ticket_code' => "AUTO-00{$number}", 'customer_name' => "Cliente {$number}",
        'delivery_address' => "Destino {$number}", 'status' => TicketStatus::Dispatched,
    ]));

    return [$driverUser, $driver, $vehicle, $tickets];
}

it('automatically creates and starts a route with every dispatched ticket', function () {
    [$driverUser, $driver, $vehicle, $tickets] = automaticRouteFixtures();
    $pickup = PickupOrder::query()->create([
        'company_id' => $driverUser->company_id, 'driver_id' => $driver->id, 'vehicle_id' => $vehicle->id,
        'created_by' => $driverUser->id, 'code' => 'RET-AUTO-001', 'pickup_name' => 'Proveedor',
        'pickup_address' => 'Punto de retiro', 'item_description' => 'Materiales',
    ]);
    Sanctum::actingAs($driverUser);

    $this->postJson('/api/v1/driver/routes/start', ['latitude' => -0.18, 'longitude' => -78.46])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonCount(3, 'data.tasks')
        ->assertJsonPath('data.tasks.2.pickup_order.id', $pickup->id);

    expect(DeliveryRoute::query()->count())->toBe(1)
        ->and($tickets->map(fn (Ticket $ticket) => $ticket->refresh()->status)->all())
        ->each->toBe(TicketStatus::InRoute)
        ->and($tickets->sum(fn (Ticket $ticket) => $ticket->events()->where('new_status', TicketStatus::InRoute)->count()))->toBe(2)
        ->and($tickets->sum(fn (Ticket $ticket) => $ticket->deliveryAttempts()->count()))->toBe(2);
});

it('starts one route with tickets dispatched from different warehouses', function () {
    [$driverUser, , , $tickets] = automaticRouteFixtures();
    $warehouses = collect([1, 2])->map(fn (int $number) => Warehouse::query()->create([
        'company_id' => $driverUser->company_id,
        'code' => "BOD-{$number}",
        'name' => "Bodega {$number}",
        'type' => WarehouseType::Branch,
    ]));
    $tickets->each(fn (Ticket $ticket, int $index) => $ticket->forceFill([
        'warehouse_id' => $warehouses[$index]->id,
    ])->save());
    Sanctum::actingAs($driverUser);

    $this->postJson('/api/v1/driver/routes/start')
        ->assertOk()
        ->assertJsonCount(2, 'data.tasks');

    expect(DeliveryRoute::query()->sole()->warehouse_id)->toBeNull()
        ->and($tickets->map(fn (Ticket $ticket) => $ticket->refresh()->status)->all())
        ->each->toBe(TicketStatus::InRoute);
});
it('returns the active route when start is requested more than once', function () {
    [$driverUser] = automaticRouteFixtures();
    Sanctum::actingAs($driverUser);

    $first = $this->postJson('/api/v1/driver/routes/start')->assertOk()->json('data.id');
    $second = $this->postJson('/api/v1/driver/routes/start')->assertOk()->json('data.id');

    expect($second)->toBe($first)->and(DeliveryRoute::query()->count())->toBe(1);
});

it('waits until warehouse dispatches every assigned ticket', function () {
    [$driverUser, , , $tickets] = automaticRouteFixtures();
    $tickets->each->forceFill(['status' => TicketStatus::Loaded])->each->save();
    Sanctum::actingAs($driverUser);

    $this->postJson('/api/v1/driver/routes/start')->assertUnprocessable()
        ->assertJsonPath('message', 'Bodega debe despachar 2 ticket(s) antes de iniciar la ruta.');
});

it('does not expose manual route creation', function () {
    [$driverUser] = automaticRouteFixtures();
    Sanctum::actingAs($driverUser);

    $this->postJson('/api/v1/delivery-routes', [])->assertMethodNotAllowed();
});
