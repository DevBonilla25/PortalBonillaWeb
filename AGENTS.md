# Repository Guidelines

## Estructura del proyecto y organización de módulos

Esta aplicación utiliza Laravel 13 y Filament 5 para el panel administrativo. El código de dominio está en `app/`: los modelos y enums definen los datos, `Actions/` contiene operaciones de flujo, `Services/` coordina la lógica y `Filament/` define los recursos administrativos. Las rutas están en `routes/`; las migraciones, factories y seeders, en `database/`. Los recursos frontend viven en `resources/` y los archivos compilados, en `public/`. Las pruebas se dividen entre `tests/Feature` y `tests/Unit`.

## Comandos de compilación, pruebas y desarrollo

- `composer setup`: instala dependencias, prepara `.env`, ejecuta migraciones y compila los recursos.
- `composer dev`: inicia Laravel, el procesador de colas y Vite simultáneamente.
- `composer test`: limpia la configuración almacenada y ejecuta todas las pruebas.
- `php artisan test --filter=WarehousePanelTest`: ejecuta una prueba específica.
- `npm run dev`: inicia Vite con recarga automática; `npm run build` genera recursos para producción.
- `vendor/bin/pint`: formatea el código PHP con Laravel Pint.

## Estilo de código y convenciones de nombres

Respeta `.editorconfig`: UTF-8, finales de línea LF, cuatro espacios de sangría (dos en YAML) y nueva línea final. Usa namespaces PSR-4 bajo `App\` y las convenciones de Laravel: modelos en PascalCase (`DeliveryRoute`), tablas en plural y snake_case, migraciones con marca temporal y acciones descriptivas (`RegisterDeliveryEvidenceAction`). Mantén delgadas las capas de interfaz; coloca las transiciones de negocio en acciones o servicios. JavaScript usa módulos ES y punto y coma. No reformatees código ajeno al cambio.

## Pautas para pruebas

Las pruebas usan Pest 4. Nombra cada archivo según el comportamiento probado y termina con `Test.php`, por ejemplo, `PickupOrderFlowTest.php`. Cubre rutas, permisos, flujos de base de datos y comportamiento de Filament con pruebas feature; reserva las unitarias para validaciones o clases de soporte aisladas. No existe un umbral numérico de cobertura, pero cada corrección debe incluir una prueba de regresión. Ejecuta `composer test` antes de abrir un pull request.

## Commits y pull requests

El historial usa asuntos breves en español, a veces con los prefijos `feat:` o `fix:`. Escribe un resumen imperativo y específico, por ejemplo: `fix: validar bodega al asignar ruta`. Mantén cada commit enfocado. Los pull requests deben explicar el impacto funcional, indicar migraciones o cambios de configuración, enlazar incidencias e incluir resultados de pruebas. Agrega capturas para cambios visuales y documenta nuevas variables de `.env`.

## Seguridad y configuración

Copia `.env.example` para trabajar localmente; nunca confirmes credenciales, tokens ni datos de producción. Revisa las policies y los permisos de Shield al agregar recursos o endpoints. Trata los archivos de `public/vendor` como generados, salvo que una actualización de dependencias requiera modificarlos.
