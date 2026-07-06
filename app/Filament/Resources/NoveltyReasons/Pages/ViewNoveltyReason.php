<?php

namespace App\Filament\Resources\NoveltyReasons\Pages;

use App\Filament\Resources\NoveltyReasons\NoveltyReasonResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNoveltyReason extends ViewRecord
{
    protected static string $resource = NoveltyReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
