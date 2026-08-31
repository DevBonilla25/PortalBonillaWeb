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
}
