<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Warehouse\WarehousePanelColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WarehousePanelService
{
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

    public function baseQuery(?User $user = null): Builder
    {
        $query = Ticket::query()
            ->with(['zone', 'items', 'currentDriver.user', 'latestAssignment.assistants'])
            ->withCount('items')
            ->whereIn('status', $this->warehouseStatuses())
            ->orderByDesc('updated_at');

        if ($user?->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * @return Collection<string, Collection<int, Ticket>>
     */
    public function ticketsByColumn(?User $user = null, ?string $search = null): Collection
    {
        $query = $this->baseQuery($user);

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

        $tickets = $query->get();

        return collect(WarehousePanelColumn::all())
            ->mapWithKeys(fn (WarehousePanelColumn $column): array => [
                $column->key => $tickets->filter(
                    fn (Ticket $ticket): bool => $this->ticketBelongsToColumn($ticket, $column),
                )->values(),
            ]);
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
        if (in_array($ticket->status, [TicketStatus::Loading, TicketStatus::Loaded], true)
            && ! $ticket->items()->exists()) {
            return TicketStatus::Dispatched;
        }

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

        return in_array($ticket->status, [
            TicketStatus::Loading,
            TicketStatus::Loaded,
        ], true);
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

        if ($user->hasAnyRole(['super_admin', 'admin', 'warehouse_operator'])) {
            return true;
        }

        return $this->canBeLoadedBy($user, $ticket)
            && in_array($ticket->status, [
                TicketStatus::Picking,
                TicketStatus::AssignedToWarehouse,
            ], true);
    }
}
