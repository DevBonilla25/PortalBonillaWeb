<?php

namespace App\Filament\Resources\Zones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color')
                    ->label('Color')
                    ->formatStateUsing(fn (?string $state): HtmlString|string => blank($state)
                        ? '-'
                        : new HtmlString(
                            '<span style="display: inline-flex; align-items: center; gap: 0.5rem;">'
                                .'<span style="width: 1rem; height: 1rem; border-radius: 9999px; background: '.e($state).'; border: 1px solid rgb(0 0 0 / 0.12); box-shadow: inset 0 0 0 1px rgb(255 255 255 / 0.35);"></span>'
                                .'<span>'.e($state).'</span>'
                            .'</span>'
                        ))
                    ->badge()
                    ->color('gray')
                    ->html(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('company.name')
                    ->label('Empresa')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code')
            ->filters([
                TernaryFilter::make('is_active')->label('Activa'),
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
