<?php

use App\Enums\JudgeConflictStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ADMIN_PERMISSIONS = [
        'view evaluation assignments',
        'manage evaluation assignments',
        'resolve evaluation conflicts',
    ];

    private const JUDGE_PERMISSIONS = [
        'declare own evaluation conflicts',
    ];

    public function up(): void
    {
        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->foreignId('submission_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('judge_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('rubric_version_id')->constrained()->restrictOnDelete();
            $table->string('type', 24);
            $table->string('status', 32)->index();
            $table->unsignedTinyInteger('current_slot')->nullable();
            $table->timestamp('due_at')->index();
            $table->foreignId('replaces_assignment_id')->nullable()->constrained('judge_assignments')->restrictOnDelete();
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('assignment_reason', 1000);
            $table->timestamp('assigned_at');
            $table->foreignId('voided_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 1000)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('cancellation_reason', 1000)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['submission_version_id', 'judge_profile_id', 'current_slot'], 'judge_assignments_current_judge_unique');
            $table->unique('replaces_assignment_id', 'judge_assignments_replacement_unique');
            $table->index(['competition_id', 'submission_version_id'], 'judge_assignments_coverage_index');
            $table->index(['judge_profile_id', 'status', 'type'], 'judge_assignments_judge_capacity_index');
        });

        Schema::create('judge_conflicts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('judge_assignment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('declared_by_judge_profile_id')->constrained('judge_profiles')->restrictOnDelete();
            $table->string('type', 64);
            $table->string('explanation', 1000)->nullable();
            $table->string('status', 32)->default(JudgeConflictStatus::Declared->value)->index();
            $table->timestamp('declared_at');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('resolution_reason', 1000)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('replacement_assignment_id')->nullable()->unique()->constrained('judge_assignments')->restrictOnDelete();
            $table->timestamps();

            $table->index(['declared_by_judge_profile_id', 'status'], 'judge_conflicts_owner_status_index');
        });

        DB::statement("ALTER TABLE judge_assignments ADD CONSTRAINT judge_assignments_type_check CHECK (type IN ('initial', 'replacement'))");
        DB::statement("ALTER TABLE judge_assignments ADD CONSTRAINT judge_assignments_status_check CHECK (status IN ('active', 'conflict_declared', 'voided', 'cancelled'))");
        DB::statement("ALTER TABLE judge_assignments ADD CONSTRAINT judge_assignments_type_replacement_check CHECK ((type = 'initial' AND replaces_assignment_id IS NULL) OR (type = 'replacement' AND replaces_assignment_id IS NOT NULL))");
        DB::statement('ALTER TABLE judge_assignments ADD CONSTRAINT judge_assignments_reason_check CHECK (CHAR_LENGTH(assignment_reason) BETWEEN 20 AND 1000)');
        DB::statement("ALTER TABLE judge_assignments ADD CONSTRAINT judge_assignments_lifecycle_check CHECK ((status IN ('active', 'conflict_declared') AND current_slot = 1 AND voided_by_user_id IS NULL AND void_reason IS NULL AND voided_at IS NULL AND cancelled_by_user_id IS NULL AND cancellation_reason IS NULL AND cancelled_at IS NULL) OR (status = 'voided' AND current_slot IS NULL AND voided_by_user_id IS NOT NULL AND CHAR_LENGTH(void_reason) BETWEEN 20 AND 1000 AND voided_at IS NOT NULL AND cancelled_by_user_id IS NULL AND cancellation_reason IS NULL AND cancelled_at IS NULL) OR (status = 'cancelled' AND current_slot IS NULL AND cancelled_by_user_id IS NOT NULL AND CHAR_LENGTH(cancellation_reason) BETWEEN 20 AND 1000 AND cancelled_at IS NOT NULL AND voided_by_user_id IS NULL AND void_reason IS NULL AND voided_at IS NULL))");
        DB::statement("ALTER TABLE judge_conflicts ADD CONSTRAINT judge_conflicts_type_check CHECK (type IN ('personal_or_family_relationship', 'professional_or_economic_relationship', 'participation_in_submission', 'other'))");
        DB::statement("ALTER TABLE judge_conflicts ADD CONSTRAINT judge_conflicts_explanation_check CHECK ((type = 'other' AND CHAR_LENGTH(explanation) BETWEEN 20 AND 1000) OR (type <> 'other' AND explanation IS NULL))");
        DB::statement("ALTER TABLE judge_conflicts ADD CONSTRAINT judge_conflicts_status_check CHECK (status IN ('declared', 'resolved_reassigned'))");
        DB::statement("ALTER TABLE judge_conflicts ADD CONSTRAINT judge_conflicts_lifecycle_check CHECK ((status = 'declared' AND resolved_by_user_id IS NULL AND resolution_reason IS NULL AND resolved_at IS NULL AND replacement_assignment_id IS NULL) OR (status = 'resolved_reassigned' AND resolved_by_user_id IS NOT NULL AND CHAR_LENGTH(resolution_reason) BETWEEN 20 AND 1000 AND resolved_at IS NOT NULL AND replacement_assignment_id IS NOT NULL))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([...self::ADMIN_PERMISSIONS, ...self::JUDGE_PERMISSIONS] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('admin', 'web')->givePermissionTo(self::ADMIN_PERMISSIONS);
        Role::findOrCreate('judge', 'web')->givePermissionTo(self::JUDGE_PERMISSIONS);
        Role::query()->where('guard_name', 'web')->whereIn('name', ['participant', 'reviewer'])->each(
            fn (Role $role) => $role->revokePermissionTo([...self::ADMIN_PERMISSIONS, ...self::JUDGE_PERMISSIONS])
        );
        Role::findOrCreate('admin', 'web')->revokePermissionTo(self::JUDGE_PERMISSIONS);
        Role::findOrCreate('judge', 'web')->revokePermissionTo(self::ADMIN_PERMISSIONS);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if ((Schema::hasTable('judge_assignments') && DB::table('judge_assignments')->exists())
            || (Schema::hasTable('judge_conflicts') && DB::table('judge_conflicts')->exists())) {
            throw new RuntimeException('Cannot remove M4 assignment tables while assignment or conflict records exist.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [...self::ADMIN_PERMISSIONS, ...self::JUDGE_PERMISSIONS])
            ->get();
        $permissionIds = $permissions->pluck('id');

        if ($permissionIds->isNotEmpty()
            && DB::table(config('permission.table_names.model_has_permissions'))
                ->whereIn('permission_id', $permissionIds)
                ->exists()) {
            throw new RuntimeException('Cannot remove M4 permissions while one is assigned directly.');
        }

        Schema::dropIfExists('judge_conflicts');
        Schema::dropIfExists('judge_assignments');

        foreach ($permissions as $permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
