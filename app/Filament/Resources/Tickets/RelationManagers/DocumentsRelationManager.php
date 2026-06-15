<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Enums\TicketDocumentType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DocumentsRelationManager extends RelationManager
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uploaded_by')
                    ->default(fn () => Auth::id()),
                Select::make('document_type')
                    ->label('Tipo')
                    ->options(TicketDocumentType::class)
                    ->default(TicketDocumentType::AdditionalDocument->value)
                    ->required(),
                FileUpload::make('file_path')
                    ->label('Archivo')
                    ->directory('ticket-documents')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_path')
            ->columns([
                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('uploadedBy.name')
                    ->label('Subido por')
                    ->placeholder('-'),
                TextColumn::make('file_path')
                    ->label('Archivo')
                    ->limit(45)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime(timezone: self::DISPLAY_TIMEZONE)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
