<?php

namespace App\Filament\Resources\DeliveryRoutes\Pages;

use App\Filament\Resources\DeliveryRoutes\DeliveryRouteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDeliveryRoute extends CreateRecord
{
    protected static string $resource = DeliveryRouteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Auth::user()->company_id, 'created_by' => Auth::id()];
    }
}
