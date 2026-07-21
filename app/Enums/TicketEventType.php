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
    case DeliveryEvidenceRegistered = 'delivery_evidence_registered';
    case NoveltyReported = 'novelty_reported';
    case LocationRecorded = 'location_recorded';
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
            self::DeliveryEvidenceRegistered => 'Evidencia de entrega registrada',
            self::NoveltyReported => 'Novedad reportada',
            self::LocationRecorded => 'Ubicacion registrada',
            self::InternalNote => 'Nota interna',
        };
    }
}
