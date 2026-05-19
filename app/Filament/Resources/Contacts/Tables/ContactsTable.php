<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Enums\ContactType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Razón social')
                    ->searchable(['first_name', 'last_name', 'business_name', 'commercial_name'])
                    ->sortable(['first_name', 'business_name']),
                TextColumn::make('commercial_name')
                    ->label('Nombre comercial')
                    ->searchable(),
                TextColumn::make('contact_type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('identification_number')
                    ->label('Identificación')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Correo')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_customer')
                    ->label('Cliente')
                    ->boolean(),
                IconColumn::make('is_supplier')
                    ->label('Proveedor')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('first_name')
            ->filters([
                SelectFilter::make('contact_type')
                    ->label('Tipo')
                    ->options(ContactType::class),
                TernaryFilter::make('is_customer')
                    ->label('Cliente'),
                TernaryFilter::make('is_supplier')
                    ->label('Proveedor'),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
