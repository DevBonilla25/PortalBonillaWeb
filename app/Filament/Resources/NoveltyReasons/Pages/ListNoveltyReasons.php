<?php

namespace App\Filament\Resources\NoveltyReasons\Pages;

use App\Filament\Resources\NoveltyReasons\NoveltyReasonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNoveltyReasons extends ListRecords
{
    protected static string $resource = NoveltyReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
