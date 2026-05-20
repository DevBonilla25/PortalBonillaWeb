<?php

namespace Database\Seeders;

use App\Enums\VehicleStatus;
use App\Models\Company;
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
    }
}
