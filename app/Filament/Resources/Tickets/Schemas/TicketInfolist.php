<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Resumen del ticket y Operaciones')
                    ->icon('heroicon-o-ticket')
                    ->columnSpanFull()
                    ->columns(10)
                    ->schema([
                        TextEntry::make('ticket_code')
                            ->label('Documento')
                            ->size('lg')
                            ->weight('bold')
                            ->columnSpan(2)
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->columnSpan(1)
                            ->formatStateUsing(fn (TicketStatus $state): string => $state->label())
                            ->color(fn (TicketStatus $state): string => self::statusColor($state))
                            ->badge(),  
                        TextEntry::make('cashier.name')
                            ->label('Cajero')
                            ->placeholder('-')
                            ->columnSpan(1),
                        TextEntry::make('warehouse.name')
                            ->label('Bodega')
                            ->placeholder('-')
                            ->badge()
                            ->color('gray')
                            ->columnSpan(2),
                        TextEntry::make('branch.name')
                            ->label('Sucursal')
                            ->placeholder('-'),
                        TextEntry::make('zone.name')
                            ->label('Zona')
                            ->placeholder('-')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('guide_number')
                            ->label('Guia')
                            ->placeholder('-')
                            ->columnSpan(1)
                            ->copyable(),
                        TextEntry::make('priority')
                            ->label('Prioridad')
                            ->columnSpan(1)
                            ->formatStateUsing(fn (?TicketPriority $state): string => self::priorityLabel($state))
                            ->color(fn (?TicketPriority $state): string => self::priorityColor($state))
                            ->badge(),
                    ]),

                Section::make('Cliente y destino')
                    ->icon('heroicon-o-map-pin')
                    ->columnSpanFull()
                    ->columns(9)
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Cliente')
                            ->size('lg')
                            ->weight('semibold')
                            ->columnSpan(3),
                        TextEntry::make('customer_phone')
                            ->label('Telefono')
                            ->columnSpan(1)
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('customer_phone_2')
                            ->label('Telefono 2')
                            ->columnSpan(1)
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('delivery_address')
                            ->label('Direccion')
                            ->placeholder('Pendiente de completar')
                            ->columnSpan(2),
                        TextEntry::make('delivery_reference')
                            ->label('Referencia')
                            ->placeholder('-')
                            ->columnSpan(2),
                    ]),

                

                Grid::make([
                    'default' => 1,
                    'xl' => 2,
                ])
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Asignacion actual')
                            ->icon('heroicon-o-truck')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('currentDriver.user.name')
                                    ->label('Chofer')
                                    ->placeholder('Sin asignar'),
                                TextEntry::make('currentVehicle.plate')
                                    ->label('Vehiculo')
                                    ->placeholder('Sin asignar'),
                            ]),

                        Section::make('Documento')
                            ->icon('heroicon-o-document-text')
                            ->columns(1)
                            ->schema([
                                ImageEntry::make('source_image_path')
                                    ->label('Imagen de guia')
                                    ->placeholder('-'),
                                TextEntry::make('observations')
                                    ->label('Observaciones')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }

    private static function priorityLabel(?TicketPriority $priority): string
    {
        if (! $priority) {
            return 'Sin prioridad';
        }

        return match ($priority) {
            TicketPriority::Low => 'Baja',
            TicketPriority::Normal => 'Media',
            TicketPriority::High => 'Alta',
            TicketPriority::Urgent => 'Urgente',
        };
    }

    private static function priorityColor(?TicketPriority $priority): string
    {
        return match ($priority) {
            TicketPriority::High, TicketPriority::Urgent => 'danger',
            TicketPriority::Normal => 'warning',
            TicketPriority::Low => 'gray',
            default => 'gray',
        };
    }

    private static function statusColor(TicketStatus $status): string
    {
        return match ($status) {
            TicketStatus::Created,
            TicketStatus::SentToWarehouse,
            TicketStatus::AssignedToWarehouse,
            TicketStatus::Picking,
            TicketStatus::Loading,
            TicketStatus::Loaded => 'warning',

            TicketStatus::Dispatched,
            TicketStatus::InRoute,
            TicketStatus::Returning => 'info',

            TicketStatus::Delivered,
            TicketStatus::ArrivedBack => 'success',

            TicketStatus::DeliveryFailed,
            TicketStatus::Cancelled => 'danger',
        };
    }
}
