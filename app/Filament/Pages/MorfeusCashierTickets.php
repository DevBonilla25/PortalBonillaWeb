<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Services\Morfeus\MorfeusTicketService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Throwable;

class MorfeusCashierTickets extends Page
{
    use HasLogisticsNavigation;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Mis tickets Morfeus';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Mis tickets Morfeus';

    protected static string $routePath = 'morfeus-cashier-tickets';

    protected string $view = 'filament.pages.morfeus-cashier-tickets';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'desde')]
    public ?string $dateFrom = null;

    #[Url(as: 'hasta')]
    public ?string $dateTo = null;

    #[Url(as: 'estado')]
    public string $status = '';

    #[Url(as: 'vista')]
    public string $mode = 'pending_warehouse';

    public string $limit = '100';

    public ?string $loadError = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $selectedTicket = null;

    public ?string $detailError = null;

    public function getHeading(): string|Htmlable
    {
        return 'Mis tickets Morfeus';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tickets de despacho generados por el cajero autenticado.';
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('ViewAny:Ticket') ?? false;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getTicketsProperty(): Collection
    {
        $this->loadError = null;
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        try {
            return app(MorfeusTicketService::class)->dispatchTicketsForCashier($user, [
                'search' => filled($this->search) ? $this->search : null,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'status' => filled($this->status) ? $this->status : null,
                'limit' => $this->limit,
                'mode' => $this->mode,
            ]);
        } catch (Throwable $exception) {
            Log::error('No se pudieron consultar tickets Morfeus del cajero.', [
                'user_id' => $user->id,
                'morfeus_user_id' => $user->morfeus_user_id,
                'exception' => $exception,
            ]);

            $this->loadError = 'No se pudo conectar con Morfeus. Revisa la conexion SQL Server.';

            return collect();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo', 'status');
        $this->limit = '100';
    }

    public function openTicketDetail(string $sourceType, int $detailId, ?int $warehouseId = null): void
    {
        $this->selectedTicket = null;
        $this->detailError = null;
        $user = Auth::user();

        if (! $user) {
            return;
        }

        try {
            $this->selectedTicket = $sourceType === 'pending_invoice'
                ? app(MorfeusTicketService::class)->pendingInvoiceDetailForCashier(
                    user: $user,
                    invoiceId: $detailId,
                    warehouseId: (int) $warehouseId,
                )
                : app(MorfeusTicketService::class)->dispatchTicketDetailForCashier(
                    user: $user,
                    externalDispatchId: $detailId,
                );

            if (! $this->selectedTicket) {
                $this->detailError = 'No se encontro el ticket Morfeus para este cajero.';
            }
        } catch (Throwable $exception) {
            Log::error('No se pudo consultar el detalle del ticket Morfeus.', [
                'user_id' => $user->id,
                'morfeus_user_id' => $user->morfeus_user_id,
                'source_type' => $sourceType,
                'detail_id' => $detailId,
                'warehouse_id' => $warehouseId,
                'exception' => $exception,
            ]);

            $this->detailError = 'No se pudo cargar el detalle del ticket Morfeus.';
        }
    }

    public function closeTicketDetail(): void
    {
        $this->selectedTicket = null;
        $this->detailError = null;
    }
}
