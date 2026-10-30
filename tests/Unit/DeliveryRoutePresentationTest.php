<?php

use App\Enums\DeliveryRouteEventType;
use App\Enums\DeliveryRouteStatus;

it('provides Spanish labels for delivery route statuses', function () {
    expect(DeliveryRouteStatus::Planned->label())->toBe('Planificada')
        ->and(DeliveryRouteStatus::InProgress->label())->toBe('En progreso')
        ->and(DeliveryRouteStatus::ReturningToWarehouse->label())->toBe('Retornando a bodega')
        ->and(DeliveryRouteStatus::AtWarehouse->label())->toBe('En bodega')
        ->and(DeliveryRouteStatus::Completed->label())->toBe('Completada')
        ->and(DeliveryRouteStatus::Cancelled->label())->toBe('Cancelada');
});

it('provides Spanish labels for delivery route events', function () {
    expect(DeliveryRouteEventType::Start->label())->toBe('Inicio de ruta')
        ->and(DeliveryRouteEventType::StartReturn->label())->toBe('Inicio de retorno')
        ->and(DeliveryRouteEventType::ArriveWarehouse->label())->toBe('Llegada a bodega')
        ->and(DeliveryRouteEventType::Complete->label())->toBe('Ruta completada')
        ->and(DeliveryRouteEventType::Cancel->label())->toBe('Ruta cancelada');
});
