<?php

namespace App\Filament\Resources\PickupOrders\Pages;

use App\Filament\Resources\PickupOrders\PickupOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreatePickupOrder extends CreateRecord
{
    protected static string $resource = PickupOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'company_id' => Auth::user()->company_id, 'created_by' => Auth::id(), 'code' => 'RET-'.now()->format('Ymd').'-'.Str::upper(Str::random(6))];
    }
}
