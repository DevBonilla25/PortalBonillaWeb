<?php

namespace App\Filament\Resources\LogisticOperations\Schemas;

use App\Models\Company;
use App\Models\DriverProfile;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LogisticOperationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Planificación del abastecimiento')->columns(2)->schema([
                Select::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->default(fn () => Auth::user()?->company_id)
                    ->required()
                    ->disabled(fn () => Company::query()->count() === 1)
                    ->dehydrated()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('driver_id', null);
                        $set('vehicle_id', null);
                    }),
                DateTimePicker::make('scheduled_start_at')
                    ->label('Salida programada')
                    ->seconds(false)
                    ->required()
                    ->live(),
                DateTimePicker::make('scheduled_arrival_at')
                    ->label('Llegada programada')
                    ->seconds(false)
                    ->required()
                    ->after('scheduled_start_at'),
                Select::make('driver_id')
                    ->label('Chofer')
                    ->relationship(
                        'driver',
                        'id',
                        fn ($query, Get $get) => $query
                            ->whereHas('user', fn ($query) => $query->where('company_id', $get('company_id')))
                            ->where('is_active', true),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Chofer #{$record->id}")
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (blank($state)) {
                            return;
                        }

                        $vehicleId = DriverProfile::query()->whereKey($state)->value('default_vehicle_id');

                        if ($vehicleId) {
                            $set('vehicle_id', $vehicleId);
                        }
                    }),
                Select::make('vehicle_id')
                    ->label('Vehículo')
                    ->relationship(
                        'vehicle',
                        'plate',
                        fn ($query, Get $get) => $query
                            ->where('company_id', $get('company_id'))
                            ->where('is_active', true),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => trim("{$record->plate} · {$record->code}"))
                    ->searchable(['plate', 'code'])
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                        if (blank($state)) {
                            return;
                        }

                        $drivers = DriverProfile::query()
                            ->where('default_vehicle_id', $state)
                            ->where('is_active', true)
                            ->whereHas('user', fn ($query) => $query->where('company_id', $get('company_id')))
                            ->limit(2)
                            ->pluck('id');

                        if ($drivers->count() === 1) {
                            $set('driver_id', $drivers->first());
                        }
                    }),
                TextInput::make('origin')->label('Origen')->default('La Maná')->required()->maxLength(255),
                TextInput::make('destination')->label('Destino / planta')->required()->maxLength(255),
                TextInput::make('plant_name')->label('Nombre de la planta')->maxLength(255),
                Textarea::make('notes')->label('Observaciones')->columnSpanFull()->maxLength(3000),
            ]),
        ]);
    }
}
