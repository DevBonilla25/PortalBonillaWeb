<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketLoadingService;

class ReviewTicketLoadingChecklistAction
{
    public function __construct(
        private readonly TicketLoadingService $loading,
    ) {}

    /**
     * @param  list<array{id:int, loaded_quantity:mixed, is_loaded:mixed, load_observation?:string|null}>  $items
     */
    public function execute(Ticket $ticket, array $items, User $reviewedBy): Ticket
    {
        return $this->loading->reviewChecklist($ticket, $items, $reviewedBy);
    }
}
