<?php

use App\Enums\RubricVersionStatus;
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
        'view evaluation rubrics',
        'manage evaluation rubrics',
    ];

    public function up(): void
    {
        Schema::create('rubric_versions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('competition_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('status', 24)->default(RubricVersionStatus::Draft->value)->index();
            $table->decimal('criterion_score_min', 6, 4);
            $table->decimal('criterion_score_max', 6, 4);
            $table->decimal('criterion_score_step', 6, 4);
            $table->decimal('total_weight', 7, 4);
            $table->decimal('total_score_min', 7, 4);
            $table->decimal('total_score_max', 7, 4);
            $table->unsignedTinyInteger('internal_decimal_places');
            $table->unsignedTinyInteger('display_decimal_places');
            $table->string('rounding_mode', 24);
            $table->unsignedSmallInteger('general_comment_min_characters');
            $table->unsignedSmallInteger('general_comment_max_characters');
            $table->unsignedSmallInteger('criterion_comment_max_characters');
            $table->unsignedTinyInteger('active_slot')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('last_edited_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('activation_reason', 1000)->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->foreignId('superseded_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['competition_id', 'version'], 'rubric_versions_competition_version_unique');
            $table->unique(['competition_id', 'active_slot'], 'rubric_versions_one_active_unique');
            $table->index(['competition_id', 'status'], 'rubric_versions_competition_status_index');
        });

        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_version_id')->constrained()->restrictOnDelete();
            $table->string('code', 32);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->decimal('weight', 7, 4);
            $table->decimal('min_score', 6, 4);
            $table->decimal('max_score', 6, 4);
            $table->decimal('score_step', 6, 4);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->unique(['rubric_version_id', 'code'], 'rubric_criteria_version_code_unique');
            $table->unique(['rubric_version_id', 'sort_order'], 'rubric_criteria_version_order_unique');
        });

        DB::statement('ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_version_check CHECK (version > 0)');
        DB::statement("ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_status_check CHECK (status IN ('draft', 'active', 'superseded'))");
        DB::statement("ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_numeric_contract_check CHECK (criterion_score_min = 0.0000 AND criterion_score_max = 10.0000 AND criterion_score_step = 0.5000 AND total_weight = 100.0000 AND total_score_min = 0.0000 AND total_score_max = 100.0000 AND internal_decimal_places = 4 AND display_decimal_places = 2 AND rounding_mode = 'HALF_UP' AND general_comment_min_characters = 100 AND general_comment_max_characters = 2000 AND criterion_comment_max_characters = 1000)");
        DB::statement("ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_lifecycle_check CHECK ((status = 'draft' AND active_slot IS NULL AND activated_at IS NULL AND activated_by_user_id IS NULL AND activation_reason IS NULL AND superseded_at IS NULL AND superseded_by_user_id IS NULL) OR (status = 'active' AND active_slot = 1 AND activated_at IS NOT NULL AND activated_by_user_id IS NOT NULL AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NULL AND superseded_by_user_id IS NULL) OR (status = 'superseded' AND active_slot IS NULL AND activated_at IS NOT NULL AND activated_by_user_id IS NOT NULL AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NOT NULL AND superseded_by_user_id IS NOT NULL))");
        DB::statement('ALTER TABLE rubric_criteria ADD CONSTRAINT rubric_criteria_scale_check CHECK (description IS NULL AND min_score = 0.0000 AND max_score = 10.0000 AND score_step = 0.5000)');
        DB::statement("ALTER TABLE rubric_criteria ADD CONSTRAINT rubric_criteria_exact_contract_check CHECK ((code = 'pertinence' AND label = 'Pertinencia' AND weight = 20.0000 AND sort_order = 1) OR (code = 'clarity' AND label = 'Claridad' AND weight = 20.0000 AND sort_order = 2) OR (code = 'feasibility' AND label = 'Viabilidad' AND weight = 25.0000 AND sort_order = 3) OR (code = 'impact' AND label = 'Impacto' AND weight = 25.0000 AND sort_order = 4) OR (code = 'coherence' AND label = 'Coherencia' AND weight = 10.0000 AND sort_order = 5))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('admin', 'web')->givePermissionTo(self::PERMISSIONS);
        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['participant', 'reviewer', 'judge'])
            ->each(fn (Role $role) => $role->revokePermissionTo(self::PERMISSIONS));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('rubric_versions')
            && DB::table('rubric_versions')->where('status', '!=', RubricVersionStatus::Draft->value)->exists()) {
            throw new RuntimeException('Cannot remove M3 rubric tables while active or superseded rubric versions exist.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::PERMISSIONS)
            ->get();
        $permissionIds = $permissions->pluck('id');

        if ($permissionIds->isNotEmpty()
            && DB::table(config('permission.table_names.model_has_permissions'))
                ->whereIn('permission_id', $permissionIds)
                ->exists()) {
            throw new RuntimeException('Cannot remove M3 rubric permissions while one is assigned directly.');
        }

        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubric_versions');

        foreach ($permissions as $permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
