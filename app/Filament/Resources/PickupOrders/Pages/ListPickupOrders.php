<?php

namespace App\Filament\Resources\PickupOrders\Pages;

use App\Filament\Resources\PickupOrders\PickupOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPickupOrders extends ListRecords
{
    protected static string $resource = PickupOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Crear orden de retiro')];
    }
}
