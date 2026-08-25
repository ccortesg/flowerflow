<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const REVIEWER_PERMISSIONS = [
        'view admissibility reviews',
        'review admissibility',
        'request clarification',
        'decide admissibility',
        'view residency documents',
        'download residency documents',
    ];

    private const ADMIN_PERMISSIONS = [
        ...self::REVIEWER_PERMISSIONS,
        'manage admissibility reviews',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ADMIN_PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('reviewer', 'web')->givePermissionTo(self::REVIEWER_PERMISSIONS);
        Role::findOrCreate('admin', 'web')->givePermissionTo(self::ADMIN_PERMISSIONS);

        Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['reviewer', 'admin'])
            ->each(fn (Role $role) => $role->revokePermissionTo(self::ADMIN_PERMISSIONS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::ADMIN_PERMISSIONS)
            ->get();

        if ($permissions->isNotEmpty()
            && DB::table(config('permission.table_names.model_has_permissions'))
                ->whereIn('permission_id', $permissions->pluck('id'))
                ->exists()) {
            throw new RuntimeException('Cannot remove directly assigned admissibility permissions.');
        }

        foreach ($permissions as $permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
