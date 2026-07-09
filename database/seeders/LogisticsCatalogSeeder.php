<?php

namespace Database\Seeders;

use App\Enums\VehicleStatus;
use App\Models\Company;
use App\Models\NoveltyReason;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class LogisticsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('ruc', '1791234567001')->firstOrFail();

        $zones = [
            ['code' => 'NORTE', 'name' => 'Norte'],
            ['code' => 'SUR', 'name' => 'Sur'],
            ['code' => 'CENTRO', 'name' => 'Centro'],
            ['code' => 'VALLE', 'name' => 'Valle'],
            ['code' => 'RURAL', 'name' => 'Rural'],
        ];

        foreach ($zones as $zone) {
            Zone::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $zone['code'],
                ],
                [
                    'name' => $zone['name'],
                    'is_active' => true,
                ],
            );
        }

        $vehicles = [
            [
                'code' => 'VEH-001',
                'plate' => 'ABC-1234',
                'brand' => 'Chevrolet',
                'model' => 'NPR',
                'type' => 'Camion',
                'capacity_kg' => 3500,
            ],
            [
                'code' => 'VEH-002',
                'plate' => 'ABD-5678',
                'brand' => 'Hino',
                'model' => 'Dutro',
                'type' => 'Camion',
                'capacity_kg' => 4500,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $vehicle['code'],
                ],
                [
                    'plate' => $vehicle['plate'],
                    'brand' => $vehicle['brand'],
                    'model' => $vehicle['model'],
                    'type' => $vehicle['type'],
                    'capacity_kg' => $vehicle['capacity_kg'],
                    'status' => VehicleStatus::Available,
                    'is_active' => true,
                ],
            );
        }

        $noveltyReasons = [
            ['code' => 'client_unavailable', 'name' => 'Cliente no contesta', 'sort_order' => 10],
            ['code' => 'wrong_address', 'name' => 'Direccion incorrecta', 'sort_order' => 20],
            ['code' => 'product_rejected', 'name' => 'Producto rechazado', 'sort_order' => 30, 'requires_photo' => true],
            ['code' => 'vehicle_breakdown', 'name' => 'Vehiculo averiado', 'sort_order' => 40, 'requires_photo' => true],
            ['code' => 'other', 'name' => 'Otro', 'sort_order' => 50],
        ];

        foreach ($noveltyReasons as $reason) {
            NoveltyReason::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $reason['code'],
                ],
                [
                    'name' => $reason['name'],
                    'requires_photo' => $reason['requires_photo'] ?? false,
                    'is_active' => true,
                    'sort_order' => $reason['sort_order'],
                ],
            );
        }
    }
}
