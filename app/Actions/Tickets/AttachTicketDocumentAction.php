<?php

namespace App\Actions\Tickets;

use App\Enums\TicketDocumentType;
use App\Enums\TicketEventType;
use App\Models\Ticket;
use App\Models\TicketDocument;
use App\Models\User;
use App\Services\TicketEventService;

class AttachTicketDocumentAction
{
    public function __construct(
        private readonly TicketEventService $events,
    ) {}

    public function execute(
        Ticket $ticket,
        string $filePath,
        User $uploadedBy,
        TicketDocumentType $documentType = TicketDocumentType::AdditionalDocument,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): TicketDocument {
        $document = $ticket->documents()->create([
            'uploaded_by' => $uploadedBy->id,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $size,
        ]);

        $this->events->record(
            ticket: $ticket,
            eventType: TicketEventType::DocumentUploaded,
            user: $uploadedBy,
            description: "Documento cargado: {$documentType->label()}.",
            metadata: [
                'document_id' => $document->id,
                'document_type' => $documentType->value,
                'file_path' => $filePath,
            ],
        );

        return $document;
    }
}
