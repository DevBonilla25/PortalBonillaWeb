<?php

namespace Database\Seeders;

use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class OrganizationalStructureSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['ruc' => '1791234567001'],
            [
                'name' => 'Bonilla Pilataxi Christian Alejandro',
                'commercial_name' => 'Mega Ferretería Bonilla',
                'email' => 'info@megaferreteriabonilla.com',
                'phone' => null,
                'address' => null,
                'is_active' => true,
            ],
        );

        $branches = [
            ['code' => 'MATRIZ', 'name' => 'Matriz', 'is_main' => true],
            ['code' => 'SUCURSAL_PLAZA', 'name' => 'Sucursal Plaza', 'is_main' => false],
            ['code' => 'SUCURSAL_CEMENTERIO', 'name' => 'Sucursal Cementerio', 'is_main' => false],
            ['code' => 'SUCURSAL_CARMEN', 'name' => 'Sucursal Carmen', 'is_main' => false],
        ];

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $branch['code'],
                ],
                [
                    'name' => $branch['name'],
                    'is_main' => $branch['is_main'],
                    'is_active' => true,
                ],
            );
        }

        Warehouse::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'BOD-GEN',
            ],
            [
                'branch_id' => null,
                'name' => 'Bodega General',
                'type' => WarehouseType::General,
                'is_general' => true,
                'is_active' => true,
            ],
        );
    }
}
