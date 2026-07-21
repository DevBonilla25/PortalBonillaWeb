<?php

namespace App\Services;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Support\Warehouse\WarehousePanelColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WarehousePanelService
{
    private const RECEIVED_LIMIT = 30;

    private const DISPATCHED_LIMIT = 20;

    private const ADMIN_ROLES = ['super_admin', 'admin', 'supervisor'];

    private const WAREHOUSE_OPERATOR_ROLES = ['warehouse_operator'];

    private const WAREHOUSE_ASSISTANT_ROLES = ['warehouse_assistant'];

    /**
     * @return list<TicketStatus>
     */
    public function warehouseStatuses(): array
    {
        return collect(WarehousePanelColumn::all())
            ->flatMap(fn (WarehousePanelColumn $column): array => $column->statuses)
            ->merge([
                TicketStatus::AssignedToWarehouse,
                TicketStatus::Loaded,
            ])
            ->unique()
            ->values()
            ->all();
    }

    public function baseQuery(?User $user = null, ?int $warehouseId = null): Builder
    {
        $query = Ticket::query()
            ->with(['warehouse', 'zone', 'currentDriver.user', 'latestAssignment.assistants'])
            ->withCount('items')
            ->whereIn('status', $this->warehouseStatuses())
            ->orderByDesc('updated_at');

        if ($user?->company_id) {
            $query->where('company_id', $user->company_id);
        }

        $warehouseId = $this->effectiveWarehouseId($user, $warehouseId);

        if (! $warehouseId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('warehouse_id', $warehouseId);
    }

    public function canAccessPanel(?User $user): bool
    {
        return $user !== null
            && $user->can('View:WarehousePanel')
            && $user->hasAnyRole([...self::ADMIN_ROLES, ...self::WAREHOUSE_OPERATOR_ROLES, ...self::WAREHOUSE_ASSISTANT_ROLES]);
    }

    public function requiresWarehouseAssignment(?User $user): bool
    {
        return $this->shouldScopeToEmployeeWarehouse($user)
            && ! $this->employeeWarehouseId($user);
    }

    public function employeeWarehouseId(?User $user): ?int
    {
        return $user?->employee?->warehouse_id;
    }

    public function effectiveWarehouseId(?User $user, ?int $requestedWarehouseId = null): ?int
    {
        if ($this->shouldScopeToEmployeeWarehouse($user)) {
            return $this->employeeWarehouseId($user);
        }

        return $requestedWarehouseId;
    }

    /**
     * @return array<int, string>
     */
    public function warehouseOptions(?User $user): array
    {
        return Warehouse::query()
            ->with('branch')
            ->when($user?->company_id, fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId))
            ->when($this->shouldScopeToEmployeeWarehouse($user), function (Builder $query) use ($user): Builder {
                return $query->whereKey($this->employeeWarehouseId($user));
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Warehouse $warehouse): array => [
                $warehouse->id => $warehouse->branch
                    ? "{$warehouse->name} - {$warehouse->branch->name}"
                    : $warehouse->name,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Zone>
     */
    public function visibleZones(?User $user, ?int $warehouseId = null): Collection
    {
        $warehouseId = $this->effectiveWarehouseId($user, $warehouseId);

        if (! $warehouseId) {
            return collect();
        }

        return Ticket::query()
            ->with('zone')
            ->whereNotNull('zone_id')
            ->whereIn('status', $this->warehouseStatuses())
            ->when($user?->company_id, fn (Builder $query, int $companyId): Builder => $query->where('company_id', $companyId))
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->pluck('zone')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * @return Collection<int, TicketEvent>
     */
    public function loadedStatusEventsForPanel(?User $user, ?int $warehouseId = null, ?int $afterEventId = null): Collection
    {
        return $this->loadedStatusEventQuery($user, $warehouseId)
            ->with('ticket')
            ->when($afterEventId, fn (Builder $query, int $eventId): Builder => $query->where('id', '>', $eventId))
            ->orderBy('id')
            ->limit(10)
            ->get();
    }

    public function latestLoadedStatusEventIdForPanel(?User $user, ?int $warehouseId = null): ?int
    {
        return $this->loadedStatusEventQuery($user, $warehouseId)->max('id');
    }

    private function loadedStatusEventQuery(?User $user, ?int $warehouseId = null): Builder
    {
        $visibleTicketIds = $this->baseQuery($user, $warehouseId)
            ->select('tickets.id')
            ->reorder();

        return TicketEvent::query()
            ->where('event_type', TicketEventType::StatusChanged->value)
            ->where('new_status', TicketStatus::Loaded->value)
            ->whereIn('ticket_id', $visibleTicketIds);
    }

    private function shouldScopeToEmployeeWarehouse(?User $user): bool
    {
        return $user !== null
            && ! $user->hasAnyRole(self::ADMIN_ROLES)
            && $user->hasAnyRole([...self::WAREHOUSE_OPERATOR_ROLES, ...self::WAREHOUSE_ASSISTANT_ROLES]);
    }

    /**
     * @return Collection<string, Collection<int, Ticket>>
     */
    public function ticketsByColumn(?User $user = null, ?string $search = null, ?int $warehouseId = null, array $limits = []): Collection
    {
        $query = $this->baseQuery($user, $warehouseId);

        if (filled($search)) {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $query) use ($term): void {
                $query
                    ->where('ticket_code', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('guide_number', 'like', $term)
                    ->orWhereHas('zone', fn (Builder $zoneQuery) => $zoneQuery->where('name', 'like', $term))
                    ->orWhereHas('currentDriver.user', fn (Builder $driverQuery) => $driverQuery->where('name', 'like', $term));
            });
        }

        return collect(WarehousePanelColumn::all())
            ->mapWithKeys(function (WarehousePanelColumn $column) use ($query, $limits): array {
                $columnQuery = $query->clone()->reorder();

                $columnQuery->where(function (Builder $query) use ($column): void {
                    $query->whereIn('status', collect($column->statuses)->map->value->all());

                    if ($column->key === 'preparation') {
                        $query->orWhere('status', TicketStatus::AssignedToWarehouse->value);
                    }

                    if ($column->key === 'loading') {
                        $query->orWhere('status', TicketStatus::Loaded->value);
                    }
                });

                $column->key === 'received'
                    ? $columnQuery->orderBy('updated_at')
                    : $columnQuery->orderByDesc('updated_at');

                $limit = $limits[$column->key] ?? $this->defaultLimitForColumn($column->key);

                if ($limit !== null) {
                    $columnQuery->limit($limit + 1);
                }

                return [$column->key => $columnQuery->get()];
            });
    }

    /**
     * @return array<string, int>
     */
    public function defaultColumnLimits(): array
    {
        return [
            'received' => self::RECEIVED_LIMIT,
            'dispatched' => self::DISPATCHED_LIMIT,
        ];
    }

    private function defaultLimitForColumn(string $column): ?int
    {
        return $this->defaultColumnLimits()[$column] ?? null;
    }

    public function ticketBelongsToColumn(Ticket $ticket, WarehousePanelColumn $column): bool
    {
        if ($column->matchesStatus($ticket->status)) {
            return true;
        }

        return match ($column->key) {
            'preparation' => $ticket->status === TicketStatus::AssignedToWarehouse,
            'loading' => $ticket->status === TicketStatus::Loaded,
            default => false,
        };
    }

    public function nextAdvanceStatus(Ticket $ticket): ?TicketStatus
    {
        return match ($ticket->status) {
            TicketStatus::Picking => TicketStatus::Loading,
            TicketStatus::AssignedToWarehouse => TicketStatus::Loading,
            default => null,
        };
    }

    public function canAssign(Ticket $ticket): bool
    {
        return in_array($ticket->status, [
            TicketStatus::SentToWarehouse,
            TicketStatus::Picking,
            TicketStatus::AssignedToWarehouse,
        ], true);
    }

    public function canUserAssignResources(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole([...self::ADMIN_ROLES, ...self::WAREHOUSE_OPERATOR_ROLES])
            && $this->canAssign($ticket);
    }

    public function canAdvance(Ticket $ticket): bool
    {
        return $this->nextAdvanceStatus($ticket) !== null
            && $ticket->status->canTransitionTo($this->nextAdvanceStatus($ticket));
    }

    public function canReviewLoadingChecklist(Ticket $ticket): bool
    {
        if (! $ticket->items()->exists() || $ticket->isLoadingChecklistReviewed()) {
            return false;
        }

        return $ticket->status === TicketStatus::Loaded;
    }

    public function canMarkLoaded(Ticket $ticket): bool
    {
        return $ticket->status === TicketStatus::Loading
            && $ticket->status->canTransitionTo(TicketStatus::Loaded);
    }

    public function canUserMarkLoaded(User $user, Ticket $ticket): bool
    {
        if (! $this->canMarkLoaded($ticket)) {
            return false;
        }

        if ($user->hasAnyRole(self::ADMIN_ROLES)) {
            return true;
        }

        return $this->canBeLoadedBy($user, $ticket);
    }

    public function canUserReviewLoadingChecklist(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole([...self::ADMIN_ROLES, ...self::WAREHOUSE_OPERATOR_ROLES])
            && $this->canReviewLoadingChecklist($ticket);
    }

    public function canBeLoadedBy(User $user, Ticket $ticket): bool
    {
        $assignment = $ticket->latestAssignment;

        if (! $assignment) {
            return false;
        }

        if ($assignment->assistants->contains('id', $user->id)) {
            return true;
        }

        return $ticket->currentDriver?->user_id === $user->id;
    }

    public function loaderTicketsQuery(User $user): Builder
    {
        return Ticket::query()
            ->with(['zone', 'items', 'currentVehicle', 'currentDriver.user', 'latestAssignment.assistants'])
            ->whereIn('status', [
                TicketStatus::Picking,
                TicketStatus::AssignedToWarehouse,
                TicketStatus::Loading,
                TicketStatus::Loaded,
            ])
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('latestAssignment.assistants', fn (Builder $assistantQuery) => $assistantQuery->whereKey($user->id))
                    ->orWhereHas('currentDriver', fn (Builder $driverQuery) => $driverQuery->where('user_id', $user->id));
            })
            ->orderByDesc('updated_at');
    }

    public function canUserAdvanceLoading(User $user, Ticket $ticket): bool
    {
        if (! $this->canAdvance($ticket)) {
            return false;
        }

        if ($user->hasAnyRole(self::ADMIN_ROLES)) {
            return true;
        }

        return $this->canBeLoadedBy($user, $ticket)
            && in_array($ticket->status, [
                TicketStatus::Picking,
                TicketStatus::AssignedToWarehouse,
            ], true);
    }
}
