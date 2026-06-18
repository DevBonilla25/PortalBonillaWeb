<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Gestión de tickets';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Panel administrativo · '.now()->translatedFormat('j \d\e F \d\e Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo ticket'),
        ];
    }
}
