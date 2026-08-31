<?php

namespace App\Filament\Resources\LogisticOperations\Pages;

use App\Filament\Resources\LogisticOperations\LogisticOperationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLogisticOperation extends CreateRecord
{
    protected static string $resource = LogisticOperationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['created_by'] = Auth::id();
        $data['type'] = 'cement_supply';

        return $data;
    }
}
