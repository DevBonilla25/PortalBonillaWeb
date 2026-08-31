<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\Actions\TicketRecordActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Ticket '.$this->record->ticket_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            TicketRecordActions::sendToWarehouse(),
            TicketRecordActions::receiveReturn(),
            TicketRecordActions::assignResources(),
            TicketRecordActions::rescheduleTicket(),
            TicketRecordActions::cancelTicket(),
            EditAction::make()
                ->label('Editar'),
        ];
    }
}
