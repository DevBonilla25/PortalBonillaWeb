<?php

namespace App\Filament\Resources\LogisticOperations\Pages;

use App\Actions\Logistics\TransitionLogisticOperationAction;
use App\Enums\LogisticOperationAction;
use App\Enums\LogisticOperationStatus;
use App\Filament\Resources\LogisticOperations\LogisticOperationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewLogisticOperation extends ViewRecord
{
    protected static string $resource = LogisticOperationResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [EditAction::make()->label('Editar')];
        if ($next = $this->nextAction()) {
            $actions[] = $this->transitionAction($next);
        }
        if (! in_array($this->record->status, [LogisticOperationStatus::Completed, LogisticOperationStatus::Cancelled], true)) {
            $actions[] = $this->stopAction();
            $actions[] = $this->transitionAction(LogisticOperationAction::Cancel)->color('danger');
        }
        $actions[] = Action::make('uploadGuide')->label('Subir guía')->icon('heroicon-o-paper-clip')->schema([
            FileUpload::make('path')->label('Guía')->disk(config('filesystems.logistics_media_disk', 'public'))->directory("logistic-operations/{$this->record->id}")->required(),
        ])->action(function (array $data): void {
            $this->record->mediaAttachments()->create(['collection' => 'guide', 'disk' => config('filesystems.logistics_media_disk', 'public'), 'path' => $data['path']]);
            Notification::make()->success()->title('Guía subida')->send();
        });

        return $actions;
    }

    private function transitionAction(LogisticOperationAction $operationAction): Action
    {
        $schema = [Textarea::make('notes')->label('Notas')->maxLength(3000)];
        if ($operationAction === LogisticOperationAction::StartReturn) {
            array_unshift($schema, FileUpload::make('plant_exit_document')
                ->label('Guía o documento entregado en planta')
                ->helperText('Obligatorio para registrar la salida de planta.')
                ->disk(config('filesystems.logistics_media_disk', 'public'))
                ->directory("logistic-operations/{$this->record->id}/plant-exit")
                ->acceptedFileTypes(['image/*', 'application/pdf'])
                ->maxSize(10240)
                ->required());
        }

        return Action::make($operationAction->value)->label($operationAction->label())->requiresConfirmation()->schema($schema)->action(function (array $data) use ($operationAction): void {
            if ($operationAction === LogisticOperationAction::StartReturn) {
                $this->record->mediaAttachments()->create([
                    'collection' => 'plant_exit_document',
                    'disk' => config('filesystems.logistics_media_disk', 'public'),
                    'path' => $data['plant_exit_document'],
                ]);
            }
            app(TransitionLogisticOperationAction::class)->execute($this->record, $operationAction, Auth::user(), $data);
            Notification::make()->success()->title('Estado actualizado')->send();
            $this->redirect(LogisticOperationResource::getUrl('view', ['record' => $this->record]), navigate: true);
        });
    }

    private function stopAction(): Action
    {
        $activeStop = $this->record->stops()->whereNull('finished_at')->latest('started_at')->first();

        if ($activeStop) {
            return Action::make('finishStop')
                ->label('Finalizar parada')
                ->color('success')
                ->icon('heroicon-o-play')
                ->requiresConfirmation()
                ->action(function () use ($activeStop): void {
                    $activeStop->forceFill(['finished_by' => Auth::id(), 'finished_at' => now()])->save();
                    Notification::make()->success()->title('Parada finalizada')->send();
                    $this->redirect(LogisticOperationResource::getUrl('view', ['record' => $this->record]), navigate: true);
                });
        }

        return Action::make('startStop')
            ->label('PARADA')
            ->color('warning')
            ->icon('heroicon-o-pause')
            ->schema([
                Select::make('reason')->label('Motivo')->options([
                    'sleep' => 'Descanso / dormir',
                    'food' => 'Alimentación',
                    'personal' => 'Actividad personal',
                    'mechanical' => 'Revisión mecánica',
                    'other' => 'Otro',
                ])->required(),
                Textarea::make('notes')->label('Detalle')->maxLength(1500),
            ])
            ->action(function (array $data): void {
                $this->record->stops()->create([...$data, 'started_by' => Auth::id(), 'started_at' => now()]);
                Notification::make()->warning()->title('Parada iniciada')->send();
                $this->redirect(LogisticOperationResource::getUrl('view', ['record' => $this->record]), navigate: true);
            });
    }

    private function nextAction(): ?LogisticOperationAction
    {
        return match ($this->record->status) {
            LogisticOperationStatus::Planned => LogisticOperationAction::StartTrip,
            LogisticOperationStatus::TravelingToPlant => LogisticOperationAction::ArrivePlant,
            LogisticOperationStatus::ArrivedAtPlant => LogisticOperationAction::StartQueue,
            LogisticOperationStatus::InQueue => LogisticOperationAction::EnterPlant,
            LogisticOperationStatus::EnteredPlant => LogisticOperationAction::StartLoading,
            LogisticOperationStatus::Loading => LogisticOperationAction::FinishLoading,
            LogisticOperationStatus::Loaded => LogisticOperationAction::StartReturn,
            LogisticOperationStatus::ReturningToOrigin => LogisticOperationAction::ArriveOrigin,
            LogisticOperationStatus::ArrivedAtOrigin => LogisticOperationAction::StartUnloading,
            LogisticOperationStatus::Unloading => LogisticOperationAction::FinishUnloading,
            default => null,
        };
    }
}
