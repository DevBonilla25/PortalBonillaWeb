<?php

namespace App\Filament\Resources\PickupOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaAttachments';

    protected static ?string $title = 'Evidencias del retiro';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('collection')->label('Evento')->badge(),
            TextColumn::make('original_name')->label('Archivo')->default(fn ($record) => basename($record->path))->url(fn ($record) => Storage::disk($record->disk)->url($record->path), true),
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc');
    }
}
