<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate('export submissions', 'web');
        Role::findOrCreate('admin', 'web')->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', 'export submissions')
            ->where('guard_name', 'web')
            ->first();
        if ($permission) {
            Role::query()
                ->where('name', 'admin')
                ->where('guard_name', 'web')
                ->each(fn (Role $role) => $role->revokePermissionTo($permission));
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
