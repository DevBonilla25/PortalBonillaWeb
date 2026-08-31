<?php

namespace App\Filament\Resources\LogisticOperations\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class IncidentsRelationManager extends RelationManager
{
    protected static string $relationship = 'incidents';

    protected static ?string $title = 'Novedades';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('reported_by')->default(fn () => Auth::id()),
            Select::make('type')->label('Tipo')->options(self::types())->required(),
            Textarea::make('description')->label('Descripción')->required()->maxLength(1500)->columnSpanFull(),
            TextInput::make('latitude')->label('Latitud')->numeric(),
            TextInput::make('longitude')->label('Longitud')->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('type')->label('Tipo')->formatStateUsing(fn ($state) => self::types()[$state] ?? $state)->badge(),
            TextColumn::make('description')->label('Descripción')->wrap(),
            TextColumn::make('reporter.name')->label('Reportado por')->placeholder('-'),
        ])->defaultSort('created_at', 'desc')->headerActions([CreateAction::make()->label('Registrar novedad')]);
    }

    private static function types(): array
    {
        return ['sleep_break' => 'Descanso', 'food_break' => 'Alimentación', 'plant_delay' => 'Demora en planta', 'queue_delay' => 'Demora en cola', 'mechanical_issue' => 'Problema mecánico', 'document_issue' => 'Problema documental', 'plant_no_dispatch' => 'Planta no despacha', 'accident' => 'Accidente', 'route_change' => 'Cambio de ruta', 'warehouse_closed' => 'Bodega cerrada', 'other' => 'Otro'];
    }
}
