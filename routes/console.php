<?php

use App\Enums\TicketEventType;
use App\Models\Ticket;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tickets:backfill-morfeus-invoice-events {--dry-run : Mostrar cambios sin guardar}', function (): int {
    $dryRun = (bool) $this->option('dry-run');
    $created = 0;
    $updatedUsers = 0;
    $skippedWithoutIssuedAt = 0;

    Ticket::query()
        ->with(['events' => fn ($query) => $query->where('event_type', TicketEventType::MorfeusInvoiceIssued->value)])
        ->where('external_source', 'morfeus')
        ->orderBy('id')
        ->chunkById(100, function ($tickets) use ($dryRun, &$created, &$updatedUsers, &$skippedWithoutIssuedAt): void {
            foreach ($tickets as $ticket) {
                $issuedAt = $ticket->external_snapshot['issued_at'] ?? null;

                if (blank($issuedAt)) {
                    $skippedWithoutIssuedAt++;

                    continue;
                }

                $event = $ticket->events->first();

                if (! $event) {
                    if (! $dryRun) {
                        $ticket->events()->create([
                            'user_id' => $ticket->cashier_id,
                            'event_type' => TicketEventType::MorfeusInvoiceIssued,
                            'occurred_at' => Carbon::parse($issuedAt, 'America/Guayaquil'),
                            'received_at' => now(),
                            'source' => 'morfeus',
                            'local_event_id' => 'morfeus-invoice-'.$ticket->external_invoice_id,
                            'description' => 'Factura emitida en Morfeus.',
                            'metadata' => [
                                'issued_at' => $issuedAt,
                                'external_invoice_id' => $ticket->external_invoice_id,
                                'external_document_number' => $ticket->external_document_number,
                                'external_warehouse_id' => $ticket->external_warehouse_id,
                            ],
                        ]);
                    }

                    $created++;

                    continue;
                }

                if (blank($event->user_id) && filled($ticket->cashier_id)) {
                    if (! $dryRun) {
                        $event->forceFill(['user_id' => $ticket->cashier_id])->save();
                    }

                    $updatedUsers++;
                }
            }
        });

    $mode = $dryRun ? 'se aplicarían' : 'aplicados';

    echo "{$created} eventos creados {$mode}.".PHP_EOL;
    echo "{$updatedUsers} eventos actualizados con cajero {$mode}.".PHP_EOL;

    if ($skippedWithoutIssuedAt > 0) {
        echo "{$skippedWithoutIssuedAt} tickets Morfeus omitidos porque no tienen issued_at en external_snapshot.".PHP_EOL;
    }

    return 0;
})->purpose('Crear eventos historicos de factura Morfeus emitida y asignar cajero a eventos existentes');
