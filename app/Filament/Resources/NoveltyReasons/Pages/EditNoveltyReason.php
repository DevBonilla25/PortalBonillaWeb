<?php

namespace App\Filament\Resources\NoveltyReasons\Pages;

use App\Filament\Resources\NoveltyReasons\NoveltyReasonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNoveltyReason extends EditRecord
{
    protected static string $resource = NoveltyReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
