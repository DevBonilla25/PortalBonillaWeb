# Mega Ferretería Bonilla — Portal Web

Portal administrativo multi-sucursal para **Mega Ferretería Bonilla**. Centraliza la operación de ventas en sucursales y prepara la logística de entregas (tickets, bodega general, choferes y vehículos) sin mezclar conceptos de negocio en una sola tabla.

## Stack tecnológico

| Componente | Versión / paquete |
|------------|-------------------|
| PHP | ^8.3 |
| Laravel | ^13.8 |
| Filament | ^5.6 (panel admin) |
| [Filament Shield](https://github.com/bezhanSalleh/filament-shield) | ^4.2 (roles y permisos) |
| [Spatie Laravel Permission](https://github.com/spatie/laravel-permission) | (vía Shield) |
| Base de datos | PostgreSQL (configurable en `.env`) |

## Arquitectura del dominio

- **Empresa única preparada para crecer:** todo se relaciona con `company_id` (sin multitenancy SaaS completo por ahora).
- **Sucursal vende; bodega general logística:** las sucursales son puntos de venta; la **Bodega General** concentrará tickets, preparación y asignación de entregas.
- **Entidades separadas (no mezclar):**
  - **Contacto** — cliente, proveedor o referencia externa.
  - **Empleado** — personal laboral (puede o no tener usuario).
  - **Usuario** — cuenta de acceso al panel/app.
  - **Chofer / vehículo** — fases posteriores (especialización operativa).

## Estado actual del proyecto

### Implementado (Fase 1 — base administrativa y personas)

**Modelos y migraciones**

- `companies`, `branches`, `warehouses`
- `contacts`, `employees`
- `users` (con `company_id`, `employee_id`, `phone`, `is_active`, `last_login_at`)
- Tablas de Spatie Permission (`roles`, `permissions`, …)

**Enums**

- `WarehouseType` — GENERAL, BRANCH, TEMPORARY, EXTERNAL
- `ContactType`, `PersonType`, `IdentificationType`
- `EmploymentStatus`

**Panel Filament** (`/admin`)

| Grupo | Recursos |
|-------|----------|
| Administración | Empresa, Sucursales, Bodegas, **Usuarios** |
| Contactos | Contactos |
| Talento humano | Empleados |
| Filament Shield | Roles (permisos por rol) |

**Autenticación y permisos**

- Filament Shield configurado en el panel `admin`.
- Rol principal: `super_admin` (convención de Shield).
- Policies generadas para recursos del panel (`Company`, `Branch`, `Warehouse`, `Contact`, `Employee`, `User`, `Role`).
- Permisos custom en seeder (`companies.view`, `branches.*`, etc.) + permisos Shield (`ViewAny:User`, …).

**Seeders**

1. `OrganizationalStructureSeeder` — empresa, 4 sucursales, Bodega General (`is_general = true`).
2. `RoleAndPermissionSeeder` — permisos base y rol `super_admin`.
3. `AdminUserSeeder` — usuario administrador con rol `super_admin`.

### Pendiente (roadmap)

- Choferes (`drivers`) y vehículos (`vehicles`)
- Tickets y detalle (`tickets`, `ticket_items`)
- Entregas e historial de estados (`deliveries`, `*_status_histories`)
- Servicios/actions de logística y reportes para gerencia
- API / app móvil para choferes

## Requisitos

- PHP 8.3+
- Composer
- Node.js y npm (assets del panel)
- PostgreSQL (o ajustar `DB_*` en `.env`)

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bonilla_portal_web
DB_USERNAME=...
DB_PASSWORD=...
```

Opcional — usuario administrador inicial:

```env
ADMIN_NAME=Administrador
ADMIN_EMAIL=admin@megaferreteriabonilla.com
ADMIN_PASSWORD=123456
```

```bash
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

Acceso al panel: **http://localhost:8000/admin**

## Despliegue en Windows Server / IIS

Para pruebas de campo en Windows Server se recomienda publicar Laravel mediante IIS, no con `php artisan serve`.

El sitio de IIS debe apuntar a:

```text
C:\sites\bonilla-portal-web\public
```

No debe apuntar a la raíz del proyecto:

```text
C:\sites\bonilla-portal-web
```

Flujo recomendado después de actualizar desde Git:

```powershell
cd C:\sites\bonilla-portal-web
git fetch origin
git checkout dev
git pull origin dev
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
iisreset
```

Si el puerto ya está asignado a IIS, por ejemplo `5000`, no se debe levantar Laravel con:

```powershell
php artisan serve --host=0.0.0.0 --port=5000
```

Ese puerto queda administrado por IIS/HTTP.sys. El acceso interno sería, por ejemplo:

```text
http://10.10.10.8:5000/admin
```

### Error 403 después del login

En producción, Filament exige que el modelo `User` implemente `FilamentUser` y defina `canAccessPanel()`. Si falta esa configuración, el login puede aceptar las credenciales pero el panel responde `403 Forbidden`.

El acceso al panel queda limitado a usuarios activos con roles autorizados, como:

- `super_admin`
- `admin`
- `cashier` / `vendedor`
- `warehouse_operator` / `jefe_bodega`
- `warehouse_assistant` / `auxiliar_bodega`
- `driver` / `chofer`

Para ambiente de pruebas reales usar:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://10.10.10.8:5000
```

Cuando se publique por Cloudflare Tunnel o dominio HTTPS, actualizar `APP_URL`:

```env
APP_URL=https://logistica.tudominio.com
```

## Comandos útiles

```bash
# Desarrollo (servidor, cola y Vite)
composer dev

# Tests
composer test

# Permisos Shield (tras crear un nuevo Resource)
php artisan shield:generate --resource=NombreResource --panel=admin --option=policies_and_permissions
php artisan permission:cache-reset

# Instalar / reconfigurar Shield en el panel
php artisan shield:setup
php artisan shield:install admin
```

## Estructura relevante

```
app/
├── Enums/                 # Tipos y estados del dominio
├── Filament/
│   ├── Concerns/          # Grupos de navegación (Administración, Contactos, …)
│   └── Resources/         # CRUD por entidad (Companies, Users, …)
├── Models/
└── Policies/              # Autorización (Shield + permisos custom)

database/
├── migrations/
└── seeders/
    ├── OrganizationalStructureSeeder.php
    ├── RoleAndPermissionSeeder.php
    └── AdminUserSeeder.php

config/
└── filament-shield.php    # Rol super_admin, recursos gestionados por Shield
```

## Convenciones

- **MVC en Laravel:** modelos + policies/services (lógica) + Filament (presentación admin).
- **Filament:** recursos con formularios/tablas en `Schemas/` y `Tables/`; páginas List / Create / Edit / View.
- **Usuarios:** formulario con empresa, empleado opcional (sin duplicar cuenta), roles Spatie y contraseña solo al crear o si se cambia en edición.

## Licencia

Proyecto privado — Mega Ferretería Bonilla. El framework Laravel se distribuye bajo la [licencia MIT](https://opensource.org/licenses/MIT).
