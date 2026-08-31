<?php

namespace App\Filament\Resources\Zones\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubzonesRelationManager extends RelationManager
{
    protected static string $relationship = 'subzones';

    protected static ?string $title = 'Subzonas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'subzones',
                        column: 'name',
                        ignoreRecord: true,
                        modifyRuleUsing: fn ($rule) => $rule->where('zone_id', $this->getOwnerRecord()->getKey()),
                    ),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make()
                    ->label('Crear subzona'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
