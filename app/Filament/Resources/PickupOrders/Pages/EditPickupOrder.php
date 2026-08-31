<?php

namespace App\Filament\Resources\PickupOrders\Pages;

use App\Enums\PickupOrderStatus;
use App\Filament\Resources\PickupOrders\PickupOrderResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPickupOrder extends EditRecord
{
    protected static string $resource = PickupOrderResource::class;

    protected function authorizeAccess(): void
    {
        $user = Auth::user();

        abort_unless(
            $user?->hasAnyRole(['super_admin', 'admin', 'supervisor', 'warehouse_operator'])
            && (int) $this->record->company_id === (int) $user->company_id
            && $this->record->status === PickupOrderStatus::Assigned,
            403,
        );
    }
}
