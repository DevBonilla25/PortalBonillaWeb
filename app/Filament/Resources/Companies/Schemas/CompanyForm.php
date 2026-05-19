<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la empresa')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Razón social')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('commercial_name')
                            ->label('Nombre comercial')
                            ->maxLength(255),
                        TextInput::make('ruc')
                            ->label('RUC')
                            ->required()
                            ->length(13)
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(50),
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
