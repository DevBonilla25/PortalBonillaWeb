<?php

namespace App\Filament\Resources\Tickets;

use App\Filament\Concerns\HasLogisticsNavigation;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\Tickets\RelationManagers\DeliveryEvidencesRelationManager;
use App\Filament\Resources\Tickets\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Tickets\RelationManagers\EventsRelationManager;
use App\Filament\Resources\Tickets\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Tickets\RelationManagers\NoveltiesRelationManager;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TicketResource extends Resource
{
    use HasLogisticsNavigation;

    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $recordTitleAttribute = 'ticket_code';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'tickets';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            return $query;
        }

        if ($user->hasAnyRole(['warehouse_operator', 'warehouse_assistant'])) {
            $warehouseId = $user->employee?->warehouse_id;

            return $warehouseId
                ? $query->where('warehouse_id', $warehouseId)
                : $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('cashier')) {
            return $query->where('cashier_id', $user->id);
        }

        if ($user->hasAnyRole(['driver', 'chofer_externo'])) {
            return $query->where('current_driver_id', $user->driverProfile?->id ?? 0);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            AssignmentsRelationManager::class,
            DocumentsRelationManager::class,
            DeliveryEvidencesRelationManager::class,
            NoveltiesRelationManager::class,
            EventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
