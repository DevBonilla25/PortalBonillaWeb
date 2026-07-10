<?php

use App\Enums\DriverStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\DriverFcmToken;
use App\Models\DriverNotification;
use App\Models\DriverProfile;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function driverApiFixtures(): array
{
    $company = Company::query()->create([
        'name' => 'Empresa Demo',
        'ruc' => '1799999999001',
        'is_active' => true,
    ]);

    $vehicle = Vehicle::query()->create([
        'company_id' => $company->id,
        'code' => 'VH-API',
        'plate' => 'API-001',
        'is_active' => true,
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'driver@example.test',
        'password' => Hash::make('secret-password'),
        'is_active' => true,
    ]);

    $driver = DriverProfile::query()->create([
        'user_id' => $user->id,
        'default_vehicle_id' => $vehicle->id,
        'status' => DriverStatus::Assigned,
        'is_active' => true,
    ]);

    $ticket = Ticket::query()->create([
        'company_id' => $company->id,
        'current_driver_id' => $driver->id,
        'current_vehicle_id' => $vehicle->id,
        'ticket_code' => 'TCK-API-001',
        'customer_name' => 'Cliente API',
        'customer_phone' => '0999999999',
        'delivery_address' => 'Calle API',
        'status' => TicketStatus::Dispatched,
    ]);

    $ticket->items()->create([
        'product_name' => 'Producto API',
        'quantity' => 2,
    ]);

    return [$user, $driver, $ticket];
}

it('logs in a driver and returns a sanctum token', function () {
    driverApiFixtures();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'driver@example.test',
        'password' => 'secret-password',
        'device_name' => 'PWA Chofer',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token_type',
            'access_token',
            'user' => [
                'id',
                'driver_profile' => ['id'],
            ],
        ]);
});

it('returns only tickets assigned to the authenticated driver', function () {
    [$user, $driver, $ticket] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    Ticket::query()->create([
        'company_id' => $ticket->company_id,
        'ticket_code' => 'TCK-OTHER',
        'customer_name' => 'Otro cliente',
        'delivery_address' => 'Otra direccion',
        'status' => TicketStatus::Dispatched,
    ]);

    $this->getJson('/api/v1/driver/tickets')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ticket->id)
        ->assertJsonCount(1, 'data');

    expect($driver->tickets()->count())->toBe(1);
});

it('allows a driver to change status with location and records an event', function () {
    [$user, $driver, $ticket] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/change-status", [
        'status' => TicketStatus::InRoute->value,
        'latitude' => -0.9332100,
        'longitude' => -79.2258100,
        'accuracy' => 8.5,
        'local_event_id' => 'evt-status-001',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', TicketStatus::InRoute->value);

    expect($ticket->refresh()->status)->toBe(TicketStatus::InRoute)
        ->and($driver->refresh()->last_latitude)->toEqual('-0.9332100')
        ->and($ticket->events()->where('event_type', TicketEventType::StatusChanged->value)->exists())->toBeTrue();
});

it('rejects access to a ticket assigned to another driver', function () {
    [$user] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $otherTicket = Ticket::query()->create([
        'ticket_code' => 'TCK-NOT-MINE',
        'customer_name' => 'Cliente ajeno',
        'delivery_address' => 'Direccion ajena',
        'status' => TicketStatus::Dispatched,
    ]);

    $this->getJson("/api/v1/driver/tickets/{$otherTicket->id}")
        ->assertNotFound();
});

it('registers delivery evidence and ticket novelties from driver api', function () {
    config(['filesystems.logistics_media_disk' => 'public']);

    Storage::fake('public');
    [$user, $driver, $ticket] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/evidence", [
        'received_by_name' => 'Receptor Demo',
        'photo' => UploadedFile::fake()->image('delivery.jpg'),
        'latitude' => -0.9332100,
        'longitude' => -79.2258100,
    ])
        ->assertOk()
        ->assertJsonPath('data.received_by_name', 'Receptor Demo');

    $this->postJson("/api/v1/driver/tickets/{$ticket->id}/novelties", [
        'novelty_type' => 'cliente_ausente',
        'description' => 'El cliente no se encontraba en el domicilio.',
        'latitude' => -0.9332100,
        'longitude' => -79.2258100,
    ])
        ->assertOk()
        ->assertJsonPath('data.novelty_type', 'cliente_ausente');

    expect($ticket->deliveryEvidences()->count())->toBe(1)
        ->and($ticket->novelties()->count())->toBe(1)
        ->and($ticket->events()->where('driver_id', $driver->id)->count())->toBe(2);
});

it('records driver location points idempotently when local event id is present', function () {
    [$user, $driver, $ticket] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $payload = [
        'ticket_id' => $ticket->id,
        'latitude' => -0.9332100,
        'longitude' => -79.2258100,
        'local_event_id' => 'loc-001',
    ];

    $this->postJson('/api/v1/driver/location', $payload)->assertOk();
    $this->postJson('/api/v1/driver/location', $payload)->assertOk();

    expect($driver->locationPoints()->count())->toBe(1)
        ->and($ticket->events()->where('event_type', TicketEventType::LocationRecorded->value)->count())->toBe(1);
});

it('stores and updates the authenticated driver fcm token', function () {
    [$user, $driver] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $payload = [
        'token' => 'fcm-device-token-001',
        'platform' => 'android',
    ];

    $this->postJson('/api/v1/driver/fcm-token', $payload)
        ->assertOk()
        ->assertJsonPath('message', 'Token FCM registrado.')
        ->assertJsonPath('data.platform', 'android');

    $this->postJson('/api/v1/driver/fcm-token', [
        'token' => 'fcm-device-token-001',
        'platform' => 'ios',
    ])
        ->assertOk()
        ->assertJsonPath('data.platform', 'ios');

    expect(DriverFcmToken::query()->count())->toBe(1)
        ->and($driver->fcmTokens()->first())
        ->not->toBeNull()
        ->and($driver->fcmTokens()->first()->platform)->toBe('ios');
});

it('rejects fcm token registration without an active driver profile', function () {
    $user = User::factory()->create(['is_active' => true]);
    Sanctum::actingAs($user, ['driver']);

    $this->postJson('/api/v1/driver/fcm-token', [
        'token' => 'fcm-device-token-002',
        'platform' => 'android',
    ])->assertForbidden();
});

it('lists authenticated driver notifications and marks one as read', function () {
    [$user, $driver, $ticket] = driverApiFixtures();
    Sanctum::actingAs($user, ['driver']);

    $notification = DriverNotification::query()->create([
        'driver_profile_id' => $driver->id,
        'ticket_id' => $ticket->id,
        'type' => 'ticket_dispatched',
        'title' => 'Ticket despachado',
        'body' => 'El ticket TCK-API-001 esta listo para entrega.',
        'data' => [
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
        ],
    ]);

    $this->getJson('/api/v1/driver/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.id', $notification->id)
        ->assertJsonPath('data.0.read_at', null);

    $this->postJson("/api/v1/driver/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('data.id', $notification->id)
        ->assertJsonPath('data.type', 'ticket_dispatched');

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('does not allow a driver to read another driver notification', function () {
    [$user, , $ticket] = driverApiFixtures();
    $otherUser = User::factory()->create(['is_active' => true]);
    $otherDriver = DriverProfile::query()->create([
        'user_id' => $otherUser->id,
        'status' => DriverStatus::Assigned,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user, ['driver']);

    $notification = DriverNotification::query()->create([
        'driver_profile_id' => $otherDriver->id,
        'ticket_id' => $ticket->id,
        'type' => 'ticket_assigned',
        'title' => 'Ticket asignado',
        'body' => 'Se te asigno un ticket.',
    ]);

    $this->postJson("/api/v1/driver/notifications/{$notification->id}/read")
        ->assertNotFound();
});
