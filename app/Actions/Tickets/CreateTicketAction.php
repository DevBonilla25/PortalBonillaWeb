<?php

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Arr;

class CreateTicketAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Ticket
    {
        return Ticket::query()->create([
            ...Arr::except($data, ['items']),
            'priority' => $data['priority'] ?? TicketPriority::Normal,
            'status' => $data['status'] ?? TicketStatus::Created,
        ]);
    }
}
