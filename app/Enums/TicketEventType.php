<?php

namespace App\Enums;

enum TicketEventType: string
{
    case Created = 'created';
    case SentToWarehouse = 'sent_to_warehouse';
    case ResourcesAssigned = 'resources_assigned';
    case StatusChanged = 'status_changed';
    case DocumentUploaded = 'document_uploaded';
    case LoadingChecklistReviewed = 'loading_checklist_reviewed';
    case InternalNote = 'internal_note';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Ticket creado',
            self::SentToWarehouse => 'Enviado a bodega',
            self::ResourcesAssigned => 'Recursos asignados',
            self::StatusChanged => 'Cambio de estado',
            self::DocumentUploaded => 'Documento cargado',
            self::LoadingChecklistReviewed => 'Checklist de carga revisado',
            self::InternalNote => 'Nota interna',
        };
    }
}
