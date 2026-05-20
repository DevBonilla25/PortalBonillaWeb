<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = array_merge([
            'companies.view',
            'companies.update',
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',
            'contacts.view',
            'contacts.create',
            'contacts.update',
            'contacts.delete',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
        ], $this->resourcePermissions());

        foreach (array_unique($permissions) as $name) {
            Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        $this->createOperationalRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function resourcePermissions(): array
    {
        $resources = [
            'Company',
            'Branch',
            'Warehouse',
            'Contact',
            'Employee',
            'User',
            'Role',
            'Zone',
            'Vehicle',
            'DriverProfile',
            'Ticket',
        ];

        $actions = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'Restore',
            'RestoreAny',
            'ForceDelete',
            'ForceDeleteAny',
            'Replicate',
            'Reorder',
        ];

        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}:{$resource}";
            }
        }

        return $permissions;
    }

    private function createOperationalRoles(): void
    {
        $roles = [
            'admin' => $this->resourcePermissions(),
            'cashier' => [
                'ViewAny:Ticket',
                'View:Ticket',
                'Create:Ticket',
                'Update:Ticket',
                'ViewAny:Zone',
                'View:Zone',
                'ViewAny:Contact',
                'View:Contact',
                'Create:Contact',
                'Update:Contact',
            ],
            'warehouse_operator' => [
                'ViewAny:Ticket',
                'View:Ticket',
                'Update:Ticket',
                'ViewAny:Zone',
                'View:Zone',
                'ViewAny:Vehicle',
                'View:Vehicle',
                'ViewAny:DriverProfile',
                'View:DriverProfile',
                'ViewAny:Warehouse',
                'View:Warehouse',
                'ViewAny:Branch',
                'View:Branch',
            ],
            'warehouse_assistant' => [
                'ViewAny:Ticket',
                'View:Ticket',
                'Update:Ticket',
            ],
            'driver' => [
                'ViewAny:Ticket',
                'View:Ticket',
                'Update:Ticket',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
