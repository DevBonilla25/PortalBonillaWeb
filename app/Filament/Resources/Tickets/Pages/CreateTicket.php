<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Actions\Tickets\AttachTicketDocumentAction;
use App\Enums\TicketDocumentType;
use App\Enums\TicketEventType;
use App\Filament\Resources\Tickets\TicketResource;
use App\Services\TicketEventService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterCreate(): void
    {
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
}
