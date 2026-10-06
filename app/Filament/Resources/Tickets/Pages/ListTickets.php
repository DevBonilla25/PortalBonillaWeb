<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    private const TABLE_POLLING_INTERVAL = '5s';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getTablePollingInterval(): ?string
    {
        return self::TABLE_POLLING_INTERVAL;
    }

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
