<?php

namespace App\Filament\Resources\DriverProfiles;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\DriverProfiles\Pages\CreateDriverProfile;
use App\Filament\Resources\DriverProfiles\Pages\EditDriverProfile;
use App\Filament\Resources\DriverProfiles\Pages\ListDriverProfiles;
use App\Filament\Resources\DriverProfiles\Pages\ViewDriverProfile;
use App\Filament\Resources\DriverProfiles\Schemas\DriverProfileForm;
use App\Filament\Resources\DriverProfiles\Schemas\DriverProfileInfolist;
use App\Filament\Resources\DriverProfiles\Tables\DriverProfilesTable;
use App\Models\DriverProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DriverProfileResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = DriverProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'license_number';

    protected static ?string $navigationLabel = 'Choferes';

    protected static ?string $modelLabel = 'chofer';

    protected static ?string $pluralModelLabel = 'choferes';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DriverProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DriverProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DriverProfilesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverProfiles::route('/'),
            'create' => CreateDriverProfile::route('/create'),
            'view' => ViewDriverProfile::route('/{record}'),
            'edit' => EditDriverProfile::route('/{record}/edit'),
        ];
    }
}
