<?php

namespace App\Models;

use App\Enums\TicketDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id',
    'uploaded_by',
    'document_type',
    'file_path',
    'original_name',
    'mime_type',
    'size',
    'metadata',
])]
class TicketDocument extends Model
{
    protected function casts(): array
    {
        return [
            'document_type' => TicketDocumentType::class,
            'metadata' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
