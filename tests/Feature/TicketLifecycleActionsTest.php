<?php

use App\Actions\Tickets\CancelTicketDefinitivelyAction;
use App\Actions\Tickets\RescheduleTicketAction;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function lifecycleTicket(TicketStatus $status): array
{
    $company = Company::query()->create(['name' => 'Empresa ciclo', 'ruc' => fake()->unique()->numerify('#############'), 'is_active' => true]);
    $user = User::factory()->create(['company_id' => $company->id]);
    $ticket = Ticket::query()->create([
        'company_id' => $company->id, 'ticket_code' => fake()->unique()->bothify('TCK-####'),
        'customer_name' => 'Cliente', 'delivery_address' => 'DirecciÃ³n', 'status' => $status,
    ]);

    return [$user, $ticket];
}

it('reprograms immediately back to warehouse and keeps a visible marker', function () {
    [$user, $ticket] = lifecycleTicket(TicketStatus::Loaded);
    $item = $ticket->items()->create([
        'product_name' => 'Producto revisado',
        'quantity' => 1,
        'loaded_quantity' => 1,
        'is_loaded' => true,
        'load_reviewed_by' => $user->id,
        'load_reviewed_at' => now(),
    ]);

    app(RescheduleTicketAction::class)->execute($ticket, 'Cliente solicita entrega maÃ±ana', $user);

    expect($ticket->refresh()->status)->toBe(TicketStatus::SentToWarehouse)
        ->and($ticket->rescheduled_count)->toBe(1)
        ->and($ticket->last_rescheduled_at)->not->toBeNull()
        ->and($ticket->reschedule_reason)->toBe('Cliente solicita entrega maÃ±ana')
        ->and($item->refresh()->loaded_quantity)->toBeNull()
        ->and($item->is_loaded)->toBeFalse()
        ->and($item->load_reviewed_at)->toBeNull();
});

it('makes definitive cancellation terminal', function () {
    [$user, $ticket] = lifecycleTicket(TicketStatus::Dispatched);

    app(CancelTicketDefinitivelyAction::class)->execute($ticket, 'Pedido anulado por el cliente', $user);

    expect($ticket->refresh()->status)->toBe(TicketStatus::Cancelled)
        ->and($ticket->cancelled_reason)->toBe('Pedido anulado por el cliente');

    app(TicketWorkflowService::class)->transition($ticket, TicketStatus::SentToWarehouse);
})->throws(DomainException::class);
