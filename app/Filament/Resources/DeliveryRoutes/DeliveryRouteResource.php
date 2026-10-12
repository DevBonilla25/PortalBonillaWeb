<?php

namespace App\Filament\Resources\DeliveryRoutes;

use App\Enums\DeliveryRouteStatus;
use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\DeliveryRoutes\Pages\ListDeliveryRoutes;
use App\Filament\Resources\DeliveryRoutes\Pages\ViewDeliveryRoute;
use App\Filament\Resources\DeliveryRoutes\RelationManagers\EventsRelationManager;
use App\Filament\Resources\DeliveryRoutes\RelationManagers\NoveltiesRelationManager;
use App\Filament\Resources\DeliveryRoutes\RelationManagers\TasksRelationManager;
use App\Models\DeliveryRoute;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DeliveryRouteResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = DeliveryRoute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Rutas de entrega';

    protected static ?string $modelLabel = 'ruta de entrega';

    protected static ?string $pluralModelLabel = 'rutas de entrega';

    protected static ?string $slug = 'rutas-entrega';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')->columns(4)->schema([
                TextEntry::make('id')->label('Ruta'), TextEntry::make('status')->label('Estado')->formatStateUsing(fn (DeliveryRouteStatus $state) => $state->value)->badge(),
                TextEntry::make('driver.user.name')->label('Chofer'), TextEntry::make('vehicle.plate')->label('Vehiculo'), TextEntry::make('warehouse.name')->label('Bodega')->placeholder('-'),
                TextEntry::make('tasks_count')->label('Actividades'), TextEntry::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')->placeholder('-'),
            ]),
            Section::make('Analisis de tiempos')->columns(4)->schema([
                TextEntry::make('started_at')->label('Inicio de ruta')->dateTime('d/m/Y H:i')->placeholder('-'), TextEntry::make('returning_at')->label('Inicio de retorno')->dateTime('d/m/Y H:i')->placeholder('-'),
                TextEntry::make('arrived_warehouse_at')->label('Llegada a bodega')->dateTime('d/m/Y H:i')->placeholder('-'), TextEntry::make('completed_at')->label('Cierre')->dateTime('d/m/Y H:i')->placeholder('-'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('Ruta')->sortable(), TextColumn::make('vehicle.plate')->label('Vehiculo')->searchable(), TextColumn::make('driver.user.name')->label('Chofer')->searchable(),
            TextColumn::make('warehouse.name')->label('Bodega')->placeholder('-'), TextColumn::make('tasks_count')->counts('tasks')->label('Actividades'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (DeliveryRouteStatus $state) => $state->value)->badge()->sortable(),
            TextColumn::make('started_at')->label('Inicio')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('updated_at')->label('Ultima actividad')->since()->sortable(),
        ])->filters([SelectFilter::make('status')->options(collect(DeliveryRouteStatus::cases())->mapWithKeys(fn ($item) => [$item->value => $item->value])->all())])->recordActions([ViewAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id)->withCount('tasks');
    }

    public static function getRelations(): array
    {
        return [TasksRelationManager::class, EventsRelationManager::class, NoveltiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListDeliveryRoutes::route('/'), 'view' => ViewDeliveryRoute::route('/{record}')];
    }
}
