<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ticket_code')->label('Ticket'),
                TextEntry::make('guide_number')->label('Guia')->placeholder('-'),
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('priority')->label('Prioridad')->badge(),
                TextEntry::make('branch.name')->label('Sucursal')->placeholder('-'),
                TextEntry::make('warehouse.name')->label('Bodega')->placeholder('-'),
                TextEntry::make('zone.name')->label('Zona')->placeholder('-'),
                TextEntry::make('cashier.name')->label('Cajero')->placeholder('-'),
                TextEntry::make('customer_name')->label('Cliente'),
                TextEntry::make('customer_phone')->label('Telefono')->placeholder('-'),
                TextEntry::make('delivery_address')->label('Direccion')->columnSpanFull(),
                TextEntry::make('delivery_reference')->label('Referencia')->placeholder('-')->columnSpanFull(),
                TextEntry::make('currentDriver.user.name')->label('Chofer')->placeholder('-'),
                TextEntry::make('currentVehicle.plate')->label('Vehiculo')->placeholder('-'),
                ImageEntry::make('source_image_path')->label('Imagen de guia')->placeholder('-'),
                TextEntry::make('observations')->label('Observaciones')->placeholder('-')->columnSpanFull(),
            ]);
    }
}
