<?php

namespace App\Filament\Resources\LogisticOperations\Pages;

use App\Filament\Resources\LogisticOperations\LogisticOperationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLogisticOperations extends ListRecords
{
    protected static string $resource = LogisticOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear abastecimiento')];
    }
}
