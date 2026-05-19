<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use App\Enums\WarehouseType;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la bodega')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->label('Empresa')
                            ->relationship('company', 'name')
                            ->default(fn () => Auth::user()?->company_id)
                            ->required()
                            ->live()
                            ->disabled(fn () => Company::query()->count() === 1)
                            ->dehydrated(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(WarehouseType::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($state === WarehouseType::General->value) {
                                    $set('is_general', true);
                                    $set('branch_id', null);
                                }
                            }),
                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(50)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->relationship(
                                'branch',
                                'name',
                                fn ($query, Get $get) => $query->when(
                                    $get('company_id'),
                                    fn ($query, int $companyId) => $query->where('company_id', $companyId),
                                ),
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->hidden(fn (Get $get): bool => $get('is_general') || $get('type') === WarehouseType::General->value)
                            ->dehydrated(fn (Get $get): bool => ! $get('is_general') && $get('type') !== WarehouseType::General->value),
                        Toggle::make('is_general')
                            ->label('Bodega general')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (bool $state, Set $set): void {
                                if ($state) {
                                    $set('type', WarehouseType::General->value);
                                    $set('branch_id', null);
                                }
                            }),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                        Textarea::make('address')
                            ->label('Dirección')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
