<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class DriverTicketQueryService
{
    /**
     * @return Builder<Ticket>
     */
    public function queryForDriver(DriverProfile $driver): Builder
    {
        return Ticket::query()
            ->where('current_driver_id', $driver->id)
            ->with([
                'zone',
                'warehouse',
                'currentVehicle',
                'items',
                'events' => fn ($query) => $query->latest('occurred_at')->limit(20),
                'deliveryEvidences',
                'novelties',
            ])
            ->latest('updated_at');
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginateForDriver(DriverProfile $driver, int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryForDriver($driver)->paginate($perPage);
    }

    public function findForDriver(DriverProfile $driver, Ticket $ticket): Ticket
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        return $ticket->load([
            'zone',
            'warehouse',
            'currentVehicle',
            'items',
            'events' => fn ($query) => $query->oldest('occurred_at'),
            'deliveryEvidences',
            'novelties',
        ]);
    }
}
