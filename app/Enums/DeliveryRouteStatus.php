<?php

namespace App\Enums;

enum DeliveryRouteStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case ReturningToWarehouse = 'returning_to_warehouse';
    case AtWarehouse = 'at_warehouse';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
