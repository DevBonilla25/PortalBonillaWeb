<?php

namespace App\Enums;

enum LogisticOperationAction: string
{
    case StartTrip = 'start_trip';
    case ArrivePlant = 'arrive_plant';
    case StartQueue = 'start_queue';
    case EnterPlant = 'enter_plant';
    case StartLoading = 'start_loading';
    case FinishLoading = 'finish_loading';
    case StartReturn = 'start_return';
    case ArriveOrigin = 'arrive_origin';
    case StartUnloading = 'start_unloading';
    case FinishUnloading = 'finish_unloading';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::StartTrip => 'Iniciar viaje', self::ArrivePlant => 'Registrar llegada a planta',
            self::StartQueue => 'Iniciar cola', self::EnterPlant => 'Registrar ingreso a planta',
            self::StartLoading => 'Iniciar carga', self::FinishLoading => 'Finalizar carga',
            self::StartReturn => 'Iniciar retorno', self::ArriveOrigin => 'Registrar llegada a origen',
            self::StartUnloading => 'Iniciar descarga', self::FinishUnloading => 'Finalizar descarga',
            self::Cancel => 'Cancelar operación',
        };
    }
}
