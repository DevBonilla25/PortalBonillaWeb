<?php

namespace App\Filament\Resources\NoveltyReasons;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\NoveltyReasons\Pages\CreateNoveltyReason;
use App\Filament\Resources\NoveltyReasons\Pages\EditNoveltyReason;
use App\Filament\Resources\NoveltyReasons\Pages\ListNoveltyReasons;
use App\Filament\Resources\NoveltyReasons\Pages\ViewNoveltyReason;
use App\Filament\Resources\NoveltyReasons\Schemas\NoveltyReasonForm;
use App\Filament\Resources\NoveltyReasons\Schemas\NoveltyReasonInfolist;
use App\Filament\Resources\NoveltyReasons\Tables\NoveltyReasonsTable;
use App\Models\NoveltyReason;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NoveltyReasonResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = NoveltyReason::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Motivos de novedad';

    protected static ?string $modelLabel = 'motivo de novedad';

    protected static ?string $pluralModelLabel = 'motivos de novedad';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return NoveltyReasonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NoveltyReasonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NoveltyReasonsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNoveltyReasons::route('/'),
            'create' => CreateNoveltyReason::route('/create'),
            'view' => ViewNoveltyReason::route('/{record}'),
            'edit' => EditNoveltyReason::route('/{record}/edit'),
        ];
    }
}
