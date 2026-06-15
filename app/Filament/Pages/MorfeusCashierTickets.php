<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\Morfeus\MorfeusTicketService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Throwable;

class MorfeusCashierTickets extends Page
{
    use HasLogisticsNavigation;
    use WithPagination;

    private const CASHIER_ROLES = ['cashier', 'vendedor'];

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

    public int $perPage = 10;

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
        $user = Auth::user();

        return $user !== null
            && $user->can('View:MorfeusCashierTickets')
            && $user->hasAnyRole(['super_admin', 'admin', ...self::CASHIER_ROLES]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getTicketsProperty(): LengthAwarePaginator
    {
        $this->loadError = null;
        $user = Auth::user();

        if (! $user) {
            return new LengthAwarePaginator([], 0, (int) $this->perPage);
        }

        try {
            return app(MorfeusTicketService::class)->dispatchTicketsForCashier($user, [
                'search' => filled($this->search) ? $this->search : null,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'status' => filled($this->status) ? $this->status : null,
                'per_page' => $this->perPage,
                'page' => $this->getPage(),
                'mode' => $this->mode,
            ]);
        } catch (Throwable $exception) {
            Log::error('No se pudieron consultar tickets Morfeus del cajero.', [
                'user_id' => $user->id,
                'morfeus_user_id' => $user->morfeus_user_id,
                'exception' => $exception,
            ]);

            $this->loadError = 'No se pudo conectar con Morfeus. Revisa la conexion SQL Server.';

            return new LengthAwarePaginator([], 0, (int) $this->perPage);
        }
    }

    public function mount(): void
    {
        $today = Carbon::today('America/Guayaquil')->toDateString();

        $this->dateFrom ??= $today;
        $this->dateTo ??= $today;
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'dateFrom', 'dateTo', 'status');
        $today = Carbon::today('America/Guayaquil')->toDateString();
        $this->dateFrom = $today;
        $this->dateTo = $today;
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if ($property === 'perPage') {
            $this->perPage = in_array((int) $this->perPage, [10, 25, 50], true)
                ? (int) $this->perPage
                : 10;
        }

        if (in_array($property, ['search', 'dateFrom', 'dateTo', 'status', 'mode', 'perPage'], true)) {
            $this->resetPage();
        }
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

    public function createTicketFromMorfeus(int $invoiceId, int $warehouseId): void
    {
        Session::put('morfeus_ticket_prefill', [
            'source_type' => 'pending_invoice',
            'invoice_id' => $invoiceId,
            'warehouse_id' => $warehouseId,
            'user_id' => Auth::id(),
        ]);

        $this->redirect(TicketResource::getUrl('create'), navigate: false);
    }

    public function closeTicketDetail(): void
    {
        $this->selectedTicket = null;
        $this->detailError = null;
    }
}
