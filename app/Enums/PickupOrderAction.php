<?php

namespace App\Enums;

enum PickupOrderAction: string
{
    case StartTrip = 'start_trip';
    case ArrivePickup = 'arrive_pickup';
    case StartLoading = 'start_loading';
    case CompletePickup = 'complete_pickup';
    case StartWarehouseTransfer = 'start_warehouse_transfer';
    case ReceiveAtWarehouse = 'receive_at_warehouse';
    case Close = 'close';
    case Fail = 'fail';
    case Cancel = 'cancel';

    public function label(): string
    {
        return match ($this) {
            self::StartTrip => 'Iniciar viaje al punto de retiro',
            self::ArrivePickup => 'Llegada al punto de retiro',
            self::StartLoading => 'Iniciar carga',
            self::CompletePickup => 'Retiro completado',
            self::StartWarehouseTransfer => 'Iniciar traslado a bodega',
            self::ReceiveAtWarehouse => 'Recepción en bodega',
            self::Close => 'Cerrar orden',
            self::Fail => 'Retiro no realizado',
            self::Cancel => 'Cancelar orden',
        };
    }
}
