<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cuenta')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('email')
                            ->label('Correo'),
                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('—'),
                        IconEntry::make('is_active')
                            ->label('Activo')
                            ->boolean(),
                        TextEntry::make('last_login_at')
                            ->label('Último acceso')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ]),
                Section::make('Organización')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('Empresa')
                            ->placeholder('—'),
                        TextEntry::make('employee.display_name')
                            ->label('Empleado vinculado')
                            ->placeholder('—'),
                    ]),
                Section::make('Acceso')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
