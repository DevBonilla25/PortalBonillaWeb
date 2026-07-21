<?php

namespace App\Filament\Resources\LogisticOperations;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\LogisticOperations\Pages\CreateLogisticOperation;
use App\Filament\Resources\LogisticOperations\Pages\EditLogisticOperation;
use App\Filament\Resources\LogisticOperations\Pages\ListLogisticOperations;
use App\Filament\Resources\LogisticOperations\Pages\ViewLogisticOperation;
use App\Filament\Resources\LogisticOperations\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\LogisticOperations\RelationManagers\IncidentsRelationManager;
use App\Filament\Resources\LogisticOperations\RelationManagers\StopsRelationManager;
use App\Filament\Resources\LogisticOperations\RelationManagers\TransitionsRelationManager;
use App\Filament\Resources\LogisticOperations\Schemas\LogisticOperationForm;
use App\Filament\Resources\LogisticOperations\Schemas\LogisticOperationInfolist;
use App\Filament\Resources\LogisticOperations\Tables\LogisticOperationsTable;
use App\Models\LogisticOperation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LogisticOperationResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = LogisticOperation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Abastecimientos';

    protected static ?string $modelLabel = 'abastecimiento';

    protected static ?string $pluralModelLabel = 'abastecimientos';

    protected static ?string $slug = 'abastecimientos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return LogisticOperationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LogisticOperationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LogisticOperationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id);
    }

    public static function getRelations(): array
    {
        return [TransitionsRelationManager::class, StopsRelationManager::class, IncidentsRelationManager::class, AttachmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListLogisticOperations::route('/'), 'create' => CreateLogisticOperation::route('/create'), 'view' => ViewLogisticOperation::route('/{record}'), 'edit' => EditLogisticOperation::route('/{record}/edit')];
    }
}
