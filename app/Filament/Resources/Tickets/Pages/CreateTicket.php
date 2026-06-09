<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Actions\Tickets\AttachTicketDocumentAction;
use App\Enums\TicketDocumentType;
use App\Enums\TicketEventType;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\Morfeus\MorfeusTicketService;
use App\Services\TicketEventService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Url;

use function Filament\Support\original_request;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    #[Url(as: 'morfeus_source_type')]
    public ?string $morfeusSourceType = null;

    #[Url(as: 'morfeus_invoice_id')]
    public ?string $morfeusInvoiceId = null;

    #[Url(as: 'morfeus_warehouse_id')]
    public ?string $morfeusWarehouseId = null;

    public function canCreateAnother(): bool
    {
        return $this->morfeusReference()['source_type'] === 'pending_invoice'
            ? false
            : parent::canCreateAnother();
    }

    public function mount(): void
    {
        parent::mount();

        $reference = $this->morfeusReference();

        if ($reference['source_type'] !== 'pending_invoice') {
            return;
        }

        Session::forget('morfeus_ticket_prefill');

        $user = Auth::user();
        $invoiceId = (int) $reference['invoice_id'];
        $warehouseId = (int) $reference['warehouse_id'];

        if (! $user || (int) $reference['user_id'] !== $user->id || $invoiceId < 1 || $warehouseId < 1) {
            Notification::make()
                ->danger()
                ->title('Referencia Morfeus invalida')
                ->body('No se pudo preparar el formulario del ticket.')
                ->send();

            return;
        }

        $payload = app(MorfeusTicketService::class)->pendingInvoiceTicketFormDataForCashier(
            user: $user,
            invoiceId: $invoiceId,
            warehouseId: $warehouseId,
        );

        if (! $payload) {
            Notification::make()
                ->danger()
                ->title('Pendiente Morfeus no disponible')
                ->body('No se encontro este documento pendiente para el cajero autenticado.')
                ->send();

            return;
        }

        if ($payload['existing_ticket_id']) {
            Notification::make()
                ->warning()
                ->title('Ticket ya creado')
                ->body('Este pendiente Morfeus ya tiene un ticket logistico en Laravel.')
                ->send();

            $this->redirect(TicketResource::getUrl('view', ['record' => $payload['existing_ticket_id']]));

            return;
        }

        if (blank($payload['form_data']['warehouse_id'] ?? null)) {
            Notification::make()
                ->danger()
                ->title('Bodega sin mapeo Laravel')
                ->body('Mapea la bodega Morfeus antes de crear el ticket logistico.')
                ->send();

            return;
        }

        $this->form->fill($payload['form_data']);
    }

    /**
     * @return array{source_type: ?string, invoice_id: ?string, warehouse_id: ?string, user_id: ?int}
     */
    private function morfeusReference(): array
    {
        $sessionReference = Session::get('morfeus_ticket_prefill', []);

        return [
            'source_type' => $this->morfeusSourceType() ?? $sessionReference['source_type'] ?? null,
            'invoice_id' => $this->morfeusInvoiceId() ?? $sessionReference['invoice_id'] ?? null,
            'warehouse_id' => $this->morfeusWarehouseId() ?? $sessionReference['warehouse_id'] ?? null,
            'user_id' => $sessionReference['user_id'] ?? Auth::id(),
        ];
    }

    private function morfeusSourceType(): ?string
    {
        return $this->morfeusSourceType
            ?? request()->query('morfeus_source_type')
            ?? original_request()->query('morfeus_source_type');
    }

    private function morfeusInvoiceId(): ?string
    {
        return $this->morfeusInvoiceId
            ?? request()->query('morfeus_invoice_id')
            ?? original_request()->query('morfeus_invoice_id');
    }

    private function morfeusWarehouseId(): ?string
    {
        return $this->morfeusWarehouseId
            ?? request()->query('morfeus_warehouse_id')
            ?? original_request()->query('morfeus_warehouse_id');
    }

    protected function afterCreate(): void
    {
        $this->createMorfeusItemsFromSnapshot();

        app(TicketEventService::class)->record(
            ticket: $this->record,
            eventType: TicketEventType::Created,
            user: Auth::user(),
            newStatus: $this->record->status,
            description: 'Ticket creado desde panel.',
        );

        if (filled($this->record->source_image_path) && Auth::user()) {
            app(AttachTicketDocumentAction::class)->execute(
                ticket: $this->record,
                filePath: $this->record->source_image_path,
                uploadedBy: Auth::user(),
                documentType: TicketDocumentType::GuideImage,
            );
        }
    }

    private function createMorfeusItemsFromSnapshot(): void
    {
        if ($this->record->external_source !== 'morfeus') {
            return;
        }

        if ($this->record->items()->exists()) {
            return;
        }

        $items = $this->record->external_snapshot['items'] ?? [];

        if ($items === []) {
            return;
        }

        $this->record->items()->createMany(
            collect($items)
                ->map(fn (array $item): array => [
                    'product_code' => $item['barcode'] ?? $item['alternative_code'] ?? null,
                    'external_line' => $item['line'] ?? null,
                    'external_item_id' => $item['external_item_id'] ?? null,
                    'external_unit_id' => $item['unit']['external_id'] ?? null,
                    'external_snapshot' => $item,
                    'product_name' => $item['description'] ?? 'Item Morfeus',
                    'quantity' => $item['pending_quantity'] ?? 0,
                    'unit' => $item['unit']['name'] ?? null,
                    'observations' => null,
                ])
                ->values()
                ->all()
        );
    }
}
