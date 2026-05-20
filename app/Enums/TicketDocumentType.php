<?php

namespace App\Enums;

enum TicketDocumentType: string
{
    case GuideImage = 'GUIDE_IMAGE';
    case AdditionalDocument = 'ADDITIONAL_DOCUMENT';
    case InternalEvidence = 'INTERNAL_EVIDENCE';

    public function label(): string
    {
        return match ($this) {
            self::GuideImage => 'Imagen de guia',
            self::AdditionalDocument => 'Documento adicional',
            self::InternalEvidence => 'Evidencia interna',
        };
    }
}
