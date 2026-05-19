<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('ruc', '1791234567001')->firstOrFail();

        $user = User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@megaferreteriabonilla.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => env('ADMIN_PASSWORD', '123456'),
                'company_id' => $company->id,
                'employee_id' => null,
                'phone' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['super_admin']);
    }
}
