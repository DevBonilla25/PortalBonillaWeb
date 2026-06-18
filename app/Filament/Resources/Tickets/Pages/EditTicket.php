<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\Actions\TicketRecordActions;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Guardar cambios')
            ->keyBindings(null);
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ticket actualizado correctamente';
    }

    protected function getHeaderActions(): array
    {
        return [
            TicketRecordActions::sendToWarehouse(),
            TicketRecordActions::reviewLoadingChecklist(),
            ViewAction::make()
                ->label('Ver'),
        ];
    }
}
