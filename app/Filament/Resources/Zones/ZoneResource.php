<?php

namespace App\Filament\Resources\Zones;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\Zones\Pages\CreateZone;
use App\Filament\Resources\Zones\Pages\EditZone;
use App\Filament\Resources\Zones\Pages\ListZones;
use App\Filament\Resources\Zones\Pages\ViewZone;
use App\Filament\Resources\Zones\RelationManagers\SubzonesRelationManager;
use App\Filament\Resources\Zones\Schemas\ZoneForm;
use App\Filament\Resources\Zones\Schemas\ZoneInfolist;
use App\Filament\Resources\Zones\Tables\ZonesTable;
use App\Models\Zone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ZoneResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = Zone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Zonas';

    protected static ?string $modelLabel = 'zona';

    protected static ?string $pluralModelLabel = 'zonas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ZoneForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ZoneInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZonesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SubzonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZones::route('/'),
            'create' => CreateZone::route('/create'),
            'view' => ViewZone::route('/{record}'),
            'edit' => EditZone::route('/{record}/edit'),
        ];
    }
}
