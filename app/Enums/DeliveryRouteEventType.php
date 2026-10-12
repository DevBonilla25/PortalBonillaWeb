<?php

namespace App\Enums;

enum DeliveryRouteEventType: string
{
    case Start = 'start';
    case StartReturn = 'start_return';
    case ArriveWarehouse = 'arrive_warehouse';
    case Complete = 'complete';
    case Cancel = 'cancel';
}
