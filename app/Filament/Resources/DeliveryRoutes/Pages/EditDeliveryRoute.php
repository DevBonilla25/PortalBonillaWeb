<?php

namespace App\Filament\Resources\DeliveryRoutes\Pages;

use App\Enums\DeliveryRouteStatus;
use App\Filament\Resources\DeliveryRoutes\DeliveryRouteResource;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryRoute extends EditRecord
{
    protected static string $resource = DeliveryRouteResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();
        abort_unless($this->record->status === DeliveryRouteStatus::Planned, 403);
    }
}
