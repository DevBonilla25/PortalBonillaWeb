<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\VehicleStatus;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del vehiculo')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->default(fn () => Auth::user()?->company_id)
                            ->required()
                            ->disabled(fn () => Company::query()->count() === 1)
                            ->dehydrated(),
                        TextInput::make('code')
                            ->label('Codigo')
                            ->required()
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        TextInput::make('plate')
                            ->label('Placa')
                            ->required()
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        Select::make('status')
                            ->label('Estado')
                            ->options(VehicleStatus::class)
                            ->required()
                            ->default(VehicleStatus::Available->value),
                        TextInput::make('brand')
                            ->label('Marca')
                            ->maxLength(255),
                        TextInput::make('model')
                            ->label('Modelo')
                            ->maxLength(255),
                        TextInput::make('type')
                            ->label('Tipo')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        TextInput::make('capacity_kg')
                            ->label('Capacidad kg')
                            ->numeric(),
                        TextInput::make('volume_m3')
                            ->label('Volumen m3')
                            ->numeric(),
                        Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
