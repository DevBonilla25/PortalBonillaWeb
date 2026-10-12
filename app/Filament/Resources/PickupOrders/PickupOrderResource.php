<?php

namespace App\Filament\Resources\PickupOrders;

use App\Enums\PickupOrderStatus;
use App\Enums\TicketPriority;
use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\PickupOrders\Pages\CreatePickupOrder;
use App\Filament\Resources\PickupOrders\Pages\EditPickupOrder;
use App\Filament\Resources\PickupOrders\Pages\ListPickupOrders;
use App\Filament\Resources\PickupOrders\Pages\ViewPickupOrder;
use App\Filament\Resources\PickupOrders\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\PickupOrders\RelationManagers\EventsRelationManager;
use App\Models\DriverProfile;
use App\Models\PickupOrder;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PickupOrderResource extends Resource
{
    use HasLogisticsNavigation;

    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    protected static ?string $model = PickupOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static ?string $navigationLabel = 'Ordenes de retiro';

    protected static ?string $modelLabel = 'orden de retiro';

    protected static ?string $pluralModelLabel = 'ordenes de retiro';

    protected static ?string $slug = 'ordenes-retiro';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Asignacion')->columns(3)->schema([
                Select::make('warehouse_id')->label('Bodega de recepcion')->relationship('warehouse', 'name', fn ($query) => $query->where('company_id', Auth::user()?->company_id))->required(),
                Select::make('driver_id')->label('Chofer')->relationship('driver', 'id', fn ($query) => $query->where('is_active', true)->whereHas('user', fn ($query) => $query->where('company_id', Auth::user()?->company_id)))->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Chofer #{$record->id}")->searchable()->preload()->live()->afterStateHydrated(function (?string $state, Set $set): void {
                    if (filled($state)) {
                        $set('vehicle_id', DriverProfile::query()->whereKey($state)->value('default_vehicle_id'));
                    }
                })->afterStateUpdated(function (?string $state, Set $set): void {
                    $set('vehicle_id', filled($state)
                        ? DriverProfile::query()->whereKey($state)->value('default_vehicle_id')
                        : null);
                })->required(),
                Select::make('vehicle_id')
                    ->label('Vehiculo')
                    ->relationship('vehicle', 'plate', fn ($query) => $query
                        ->where('company_id', Auth::user()?->company_id)
                        ->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                DateTimePicker::make('scheduled_at')->label('Fecha programada')->seconds(false),
                Select::make('priority')->label('Prioridad')->options(collect(TicketPriority::cases())->mapWithKeys(fn ($item) => [$item->value => $item->value])->all())->default(TicketPriority::Normal->value)->required(),
            ]),
            Section::make('Punto de retiro')->columns(2)->schema([
                TextInput::make('pickup_name')->label('Proveedor o lugar')->required()->maxLength(255), TextInput::make('contact_name')->label('Contacto')->maxLength(255),
                TextInput::make('pickup_address')->label('Direccion')->required()->maxLength(255)->columnSpanFull(), TextInput::make('pickup_reference')->label('Referencia')->maxLength(255),
                TextInput::make('contact_phone')->label('Telefono')->tel()->maxLength(50), TextInput::make('google_maps_url')->label('Enlace de Google Maps')->url()->maxLength(255)->columnSpanFull(),
                Textarea::make('item_description')->label('Materiales por retirar')->required()->maxLength(3000)->columnSpanFull(), Textarea::make('notes')->label('Observaciones')->maxLength(3000)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')->columns(4)->schema([
                TextEntry::make('code')->label('Orden')->copyable(), TextEntry::make('status')->label('Estado')->formatStateUsing(fn (PickupOrderStatus $state) => $state->label())->color(fn (PickupOrderStatus $state) => $state->color())->badge(),
                TextEntry::make('driver.user.name')->label('Chofer'), TextEntry::make('vehicle.plate')->label('Vehiculo'), TextEntry::make('warehouse.name')->label('Bodega'),
                TextEntry::make('pickup_name')->label('Punto de retiro'), TextEntry::make('pickup_address')->label('Direccion')->columnSpan(2), TextEntry::make('item_description')->label('Materiales')->columnSpanFull(),
            ]),
            Section::make('Analisis de tiempos')->columns(4)->schema([
                TextEntry::make('scheduled_at')->label('Programado')->dateTime('d/m/Y H:i')->placeholder('-'), TextEntry::make('en_route_at')->label('Salida al retiro')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('arrived_at')->label('Llegada')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'), TextEntry::make('loading_at')->label('Inicio de carga')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('picked_up_at')->label('Retiro completado')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'), TextEntry::make('received_at')->label('Recepcion en bodega')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('completed_at')->label('Cierre')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Orden')->searchable()->sortable(), TextColumn::make('pickup_name')->label('Punto de retiro')->searchable(),
            TextColumn::make('driver.user.name')->label('Chofer'), TextColumn::make('vehicle.plate')->label('Vehiculo'),
            TextColumn::make('status')->label('Estado')->formatStateUsing(fn (PickupOrderStatus $state) => $state->label())->color(fn (PickupOrderStatus $state) => $state->color())->badge(),
            TextColumn::make('scheduled_at')->label('Programado')->dateTime('d/m/Y H:i')->sortable(), TextColumn::make('updated_at')->label('Ultima actividad')->since()->sortable(),
        ])->filters([SelectFilter::make('status')->options(collect(PickupOrderStatus::cases())->mapWithKeys(fn ($item) => [$item->value => $item->label()])->all())])->recordActions([ViewAction::make(), EditAction::make()])->defaultSort('updated_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('company_id', Auth::user()?->company_id);
    }

    public static function getRelations(): array
    {
        return [EventsRelationManager::class, AttachmentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListPickupOrders::route('/'), 'create' => CreatePickupOrder::route('/create'), 'view' => ViewPickupOrder::route('/{record}'), 'edit' => EditPickupOrder::route('/{record}/edit')];
    }
}
