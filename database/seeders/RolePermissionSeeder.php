<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        DB::transaction(function (): void {
            $guard = config('rbac.guard');
            $roles = config('rbac.roles');

            foreach (array_unique(array_merge(...array_values($roles))) as $permission) {
                Permission::findOrCreate($permission, $guard);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach ($roles as $name => $permissions) {
                Role::findOrCreate($name, $guard)->syncPermissions($permissions);
            }
        });

        $registrar->forgetCachedPermissions();
    }
}
