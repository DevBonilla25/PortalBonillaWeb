<?php

namespace App\Filament\Resources\LogisticOperations\Schemas;

use App\Enums\LogisticOperationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LogisticOperationInfolist
{
    private const DISPLAY_TIMEZONE = 'America/Guayaquil';

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(12)->components([
            Section::make('Resumen')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('id')->label('Operación'),
                TextEntry::make('status')->label('Estado')->formatStateUsing(fn (LogisticOperationStatus $state) => $state->label())->color(fn (LogisticOperationStatus $state) => $state->color())->badge(),
                TextEntry::make('driver.user.name')->label('Chofer')->placeholder('Sin asignar'),
                TextEntry::make('vehicle.plate')->label('Vehículo')->placeholder('Sin asignar'),
                TextEntry::make('origin')->label('Origen'),
                TextEntry::make('destination')->label('Destino'),
                TextEntry::make('plant_name')->label('Planta')->placeholder('-'),
                TextEntry::make('scheduled_start_at')->label('Salida programada')->dateTime('d/m/Y H:i')->placeholder('-'),
                TextEntry::make('scheduled_arrival_at')->label('Llegada programada')->dateTime('d/m/Y H:i')->placeholder('-'),
                TextEntry::make('notes')->label('Observaciones')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Tiempos del recorrido')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('started_at')->label('Inicio de viaje')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('arrived_plant_at')->label('Llegada a planta')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('queue_started_at')->label('Inicio de cola')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('plant_entry_at')->label('Ingreso a planta')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('loading_started_at')->label('Inicio de carga')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('loading_finished_at')->label('Fin de carga')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('left_plant_at')->label('Salida de planta')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('arrived_origin_at')->label('Llegada a origen')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('unloading_started_at')->label('Inicio de descarga')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('unloading_finished_at')->label('Fin de descarga')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('finished_at')->label('Finalizada')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
                TextEntry::make('cancelled_at')->label('Cancelada')->dateTime('d/m/Y H:i', timezone: self::DISPLAY_TIMEZONE)->placeholder('-'),
            ]),
        ]);
    }
}
