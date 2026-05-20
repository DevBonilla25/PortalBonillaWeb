<?php

namespace App\Filament\Resources\Zones\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la zona')
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
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
