<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company.name')
                    ->label('Empresa'),
                TextEntry::make('employee_code')
                    ->label('Código'),
                TextEntry::make('display_name')
                    ->label('Nombre'),
                TextEntry::make('contact.display_name')
                    ->label('Contacto vinculado')
                    ->placeholder('—'),
                TextEntry::make('employment_status')
                    ->label('Estado laboral')
                    ->badge(),
                TextEntry::make('job_position')
                    ->label('Cargo')
                    ->placeholder('—'),
                TextEntry::make('branch.name')
                    ->label('Sucursal')
                    ->placeholder('—'),
                TextEntry::make('warehouse.name')
                    ->label('Bodega')
                    ->placeholder('—'),
                TextEntry::make('user.email')
                    ->label('Usuario del sistema')
                    ->placeholder('Sin usuario vinculado'),
                TextEntry::make('hire_date')
                    ->label('Fecha de ingreso')
                    ->date()
                    ->placeholder('—'),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ]);
    }
}
