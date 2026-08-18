<?php

use App\Enums\JudgeProfileStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view judges',
        'manage judges',
        'recover judge two factor',
    ];

    public function up(): void
    {
        Schema::create('judge_profiles', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('assignment_role', 24)->index();
            $table->string('status', 24)->default(JudgeProfileStatus::PendingSetup->value)->index();
            $table->unsignedSmallInteger('max_active_assignments')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('password_initialized_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('suspension_reason', 1000)->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->foreignId('reactivated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE judge_profiles ADD CONSTRAINT judge_profiles_assignment_role_check CHECK (assignment_role IN ('primary', 'substitute'))");
        DB::statement("ALTER TABLE judge_profiles ADD CONSTRAINT judge_profiles_status_check CHECK (status IN ('pending_setup', 'active', 'suspended'))");
        DB::statement("ALTER TABLE judge_profiles ADD CONSTRAINT judge_profiles_capacity_check CHECK ((assignment_role = 'primary' AND max_active_assignments IS NULL) OR (assignment_role = 'substitute' AND max_active_assignments = 10))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo(self::PERMISSIONS);

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['participant', 'reviewer', 'judge'])
            ->each(fn (Role $role) => $role->revokePermissionTo(self::PERMISSIONS));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('judge_profiles') && DB::table('judge_profiles')->exists()) {
            throw new RuntimeException('Cannot remove judge profiles while profile records exist.');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->get();
        $directPermissionIds = $permissions->pluck('id');
        if ($directPermissionIds->isNotEmpty()
            && DB::table(config('permission.table_names.model_has_permissions'))
                ->whereIn('permission_id', $directPermissionIds)
                ->exists()) {
            throw new RuntimeException('Cannot remove M2 judge permissions while one is assigned directly.');
        }

        Schema::dropIfExists('judge_profiles');

        foreach ($permissions as $permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
