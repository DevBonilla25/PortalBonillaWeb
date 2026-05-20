<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\Actions\TicketRecordActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TicketRecordActions::sendToWarehouse(),
            TicketRecordActions::assignResources(),
            TicketRecordActions::changeStatus(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
