<?php

namespace App\Filament\Resources\DriverProfiles\Schemas;

use App\Enums\DriverStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DriverProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cuenta y datos operativos')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('employee_id')
                            ->label('Empleado vinculado')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                            ->searchable(['first_name', 'last_name', 'identification_number'])
                            ->preload()
                            ->nullable()
                            ->unique(ignoreRecord: true),
                        Select::make('default_vehicle_id')
                            ->label('Vehiculo predeterminado')
                            ->relationship('defaultVehicle', 'plate')
                            ->searchable(['plate', 'code', 'brand', 'model'])
                            ->preload()
                            ->nullable(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(DriverStatus::class)
                            ->required()
                            ->default(DriverStatus::Available->value),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
                Section::make('Licencia')
                    ->columns(3)
                    ->schema([
                        TextInput::make('license_number')
                            ->label('Numero')
                            ->maxLength(100),
                        TextInput::make('license_type')
                            ->label('Tipo')
                            ->maxLength(50),
                        DatePicker::make('license_expires_at')
                            ->label('Vence')
                            ->native(false),
                    ]),
                Section::make('Seguimiento')
                    ->columns(2)
                    ->schema([
                        TextInput::make('last_latitude')
                            ->label('Ultima latitud')
                            ->numeric(),
                        TextInput::make('last_longitude')
                            ->label('Ultima longitud')
                            ->numeric(),
                        Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
