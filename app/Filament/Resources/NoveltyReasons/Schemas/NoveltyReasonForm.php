<?php

namespace App\Filament\Resources\NoveltyReasons\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class NoveltyReasonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del motivo')
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
                            ->maxLength(80)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('company_id', $get('company_id')),
                            )
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtolower(trim($state)) : null),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Toggle::make('requires_photo')
                            ->label('Requiere foto')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
