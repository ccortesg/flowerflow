<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'access judge workspace';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate(self::PERMISSION, 'web');
        Role::findOrCreate('judge', 'web')->syncPermissions([$permission]);

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['participant', 'reviewer', 'admin'])
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->where('name', 'judge')->where('guard_name', 'web')->first();
        $permission = Permission::query()->where('name', self::PERMISSION)->where('guard_name', 'web')->first();
        $modelType = config('auth.providers.users.model');
        $rolePivot = config('permission.table_names.model_has_roles');
        $permissionPivot = config('permission.table_names.model_has_permissions');

        if ($role && DB::table($rolePivot)->where('role_id', $role->getKey())->where('model_type', $modelType)->exists()) {
            throw new RuntimeException('Cannot remove the judge role while it is assigned to users.');
        }

        if ($permission && DB::table($permissionPivot)->where('permission_id', $permission->getKey())->exists()) {
            throw new RuntimeException('Cannot remove the judge workspace permission while it is assigned directly.');
        }

        if ($permission) {
            Role::query()
                ->where('guard_name', 'web')
                ->each(fn (Role $assignedRole) => $assignedRole->revokePermissionTo($permission));
        }

        $role?->delete();
        $permission?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
