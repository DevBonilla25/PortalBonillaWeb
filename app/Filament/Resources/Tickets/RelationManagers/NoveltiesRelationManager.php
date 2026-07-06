<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class NoveltiesRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'novelties';

    protected static ?string $title = 'Novedades';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->visibility('public')
                    ->imageSize(56)
                    ->square(),
                TextColumn::make('reason.name')
                    ->label('Motivo')
                    ->placeholder(fn ($record): string => $record->novelty_type ?? '-')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripcion')
                    ->limit(55)
                    ->searchable(),
                TextColumn::make('driver.user.name')
                    ->label('Chofer')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime(timezone: self::DISPLAY_TIMEZONE)
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Lat.')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->label('Lng.')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([
                Action::make('open_photo')
                    ->label('Ver foto')
                    ->icon('heroicon-o-photo')
                    ->url(fn ($record): ?string => $record->photo_path ? Storage::disk('public')->url($record->photo_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => filled($record->photo_path)),
            ]);
    }
}
