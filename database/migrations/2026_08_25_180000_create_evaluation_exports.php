<?php

use App\Enums\EvaluationExportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSION = 'export evaluations';

    public function up(): void
    {
        Schema::create('evaluation_exports', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->default(EvaluationExportStatus::Queued->value);
            $table->string('scope_version', 48)->default('all_revisions_v1');
            $table->string('disk', 64);
            $table->string('path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('evaluation_count')->default(0);
            $table->unsignedInteger('revision_count')->default(0);
            $table->unsignedInteger('criterion_count')->default(0);
            $table->unsignedInteger('reopening_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_code', 128)->nullable();
            $table->timestamps();

            $table->index(['requested_by_user_id', 'status']);
        });
        DB::statement("ALTER TABLE evaluation_exports ADD CONSTRAINT evaluation_exports_status_check CHECK (status IN ('queued', 'processing', 'completed', 'failed', 'expired'))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate(self::PERMISSION, 'web');
        Role::findOrCreate('admin', 'web')->givePermissionTo($permission);
        Role::query()->where('guard_name', 'web')->whereIn('name', ['participant', 'reviewer', 'judge'])->each(
            fn (Role $role) => $role->revokePermissionTo($permission)
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if ((Schema::hasTable('evaluation_exports') && DB::table('evaluation_exports')->exists())
            || (Schema::hasTable('audit_logs') && DB::table('audit_logs')->where('action', 'like', 'evaluation_export.%')->exists())) {
            throw new RuntimeException('Cannot remove evaluation exports while export evidence exists.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', self::PERMISSION)
            ->first();
        if ($permission) {
            if (DB::table(config('permission.table_names.model_has_permissions'))
                ->where('permission_id', $permission->id)->exists()) {
                throw new RuntimeException('Cannot remove the evaluation export permission while it is assigned directly.');
            }
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('evaluation_exports');
    }
};
