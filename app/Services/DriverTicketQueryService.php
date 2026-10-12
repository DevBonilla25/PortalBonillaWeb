<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\DriverProfile;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DriverTicketQueryService
{
    /**
     * @return list<TicketStatus>
     */
    public static function activeStatuses(): array
    {
        return [
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded,
            TicketStatus::Dispatched,
            TicketStatus::InRoute,
            TicketStatus::AtDestination,
            TicketStatus::Unloading,
            TicketStatus::DeliveryFailed,
            TicketStatus::Returning,
        ];
    }

    /**
     * @return list<TicketStatus>
     */
    public static function historyStatuses(): array
    {
        return [
            TicketStatus::Delivered,
            TicketStatus::ArrivedBack,
            TicketStatus::Cancelled,
        ];
    }

    /**
     * @return Builder<Ticket>
     */
    public function queryForDriver(DriverProfile $driver, ?string $scope = null, ?TicketStatus $status = null): Builder
    {
        $query = Ticket::query()
            ->where('current_driver_id', $driver->id)
            ->with([
                'zone',
                'subzone',
                'warehouse',
                'currentVehicle',
            ])
            ->withCount('items')
            ->latest('updated_at');

        $statuses = match ($scope) {
            'history' => self::historyStatuses(),
            default => self::activeStatuses(),
        };

        $query->whereIn(
            'status',
            array_map(fn (TicketStatus $ticketStatus): string => $ticketStatus->value, $statuses),
        );

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        if (request()->filled('delivery_type')) {
            $query->where('delivery_type', request()->string('delivery_type'));
        }
        if (request()->filled('zone_id')) {
            $query->where('zone_id', request()->integer('zone_id'));
        }
        if (request()->filled('subzone_id')) {
            $query->where('subzone_id', request()->integer('subzone_id'));
        }

        return $query;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function activeForDriver(DriverProfile $driver, ?TicketStatus $status = null): Collection
    {
        return $this->queryForDriver($driver, 'active', $status)->get();
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function paginateForDriver(
        DriverProfile $driver,
        int $perPage = 15,
        ?string $scope = null,
        ?TicketStatus $status = null,
    ): LengthAwarePaginator {
        return $this->queryForDriver($driver, $scope, $status)->paginate($perPage);
    }

    public function findForDriver(DriverProfile $driver, Ticket $ticket): Ticket
    {
        abort_unless((int) $ticket->current_driver_id === (int) $driver->id, 404);

        return $ticket->load([
            'zone',
            'subzone',
            'warehouse',
            'currentVehicle',
            'items',
            'events' => fn ($query) => $query->oldest('occurred_at'),
            'deliveryEvidences.mediaAttachments',
            'novelties.mediaAttachments',
            'novelties.reason',
        ]);
    }
}
