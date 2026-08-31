<?php

namespace App\Filament\Resources\PickupOrders\Pages;

use App\Actions\Pickups\TransitionPickupOrderAction;
use App\Enums\PickupOrderAction;
use App\Enums\PickupOrderStatus;
use App\Filament\Resources\PickupOrders\PickupOrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPickupOrder extends ViewRecord
{
    protected static string $resource = PickupOrderResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [EditAction::make()->visible(fn () => $this->record->status === PickupOrderStatus::Assigned)];
        if ($this->record->status === PickupOrderStatus::PickedUp) {
            $actions[] = $this->transitionAction(PickupOrderAction::StartWarehouseTransfer, 'Registrar traslado a bodega');
        }
        if ($this->record->status === PickupOrderStatus::TransportingToWarehouse) {
            $actions[] = $this->transitionAction(PickupOrderAction::ReceiveAtWarehouse, 'Recibir en bodega');
        }
        if ($this->record->status === PickupOrderStatus::ReceivedAtWarehouse) {
            $actions[] = $this->transitionAction(PickupOrderAction::Close, 'Cerrar orden');
        }
        if (! $this->record->status->isFinal()) {
            $actions[] = $this->transitionAction(PickupOrderAction::Cancel, 'Cancelar')->color('danger');
        }

        return $actions;
    }

    private function transitionAction(PickupOrderAction $action, string $label): Action
    {
        return Action::make($action->value)->label($label)->requiresConfirmation()->schema($action === PickupOrderAction::Cancel ? [Textarea::make('notes')->label('Motivo')->required()] : [])->action(function (array $data) use ($action): void {
            app(TransitionPickupOrderAction::class)->execute($this->record, $action, Auth::user(), $data);
            Notification::make()->success()->title('Orden actualizada')->send();
            $this->redirect(PickupOrderResource::getUrl('view', ['record' => $this->record]), navigate: true);
        });
    }
}
