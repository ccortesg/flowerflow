<?php

use App\Enums\EvaluationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const JUDGE_PERMISSION = 'manage own evaluation drafts';

    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('judge_assignment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('rubric_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('blind_review_package_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('current_revision_id')->nullable()->index();
            $table->string('status', 24)->default(EvaluationStatus::Draft->value)->index();
            $table->unsignedInteger('lock_version')->default(0);
            $table->foreignId('started_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamps();
        });

        Schema::create('evaluation_revisions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('evaluation_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('status', 24)->default(EvaluationStatus::Draft->value)->index();
            $table->text('general_comment')->nullable();
            $table->decimal('total_raw', 7, 4)->nullable();
            $table->foreignId('source_revision_id')->nullable()->constrained('evaluation_revisions')->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('last_saved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['evaluation_id', 'revision_number'], 'evaluation_revision_number_unique');
        });

        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('rubric_criterion_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 6, 4)->nullable();
            $table->decimal('calculated_component', 7, 4)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_revision_id', 'rubric_criterion_id'], 'evaluation_revision_criterion_unique');
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('current_revision_id', 'evaluations_current_revision_foreign')
                ->references('id')->on('evaluation_revisions')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE evaluations ADD CONSTRAINT evaluations_status_check CHECK (status = 'draft')");
        DB::statement("ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_status_check CHECK (status = 'draft')");
        DB::statement('ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_number_check CHECK (revision_number > 0)');
        DB::statement('ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_total_check CHECK (total_raw IS NULL OR (total_raw >= 0.0000 AND total_raw <= 100.0000))');
        DB::statement('ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_comment_check CHECK (general_comment IS NULL OR CHAR_LENGTH(general_comment) <= 2000)');
        DB::statement('ALTER TABLE evaluation_scores ADD CONSTRAINT evaluation_scores_score_check CHECK (score IS NULL OR (score >= 0.0000 AND score <= 10.0000 AND MOD(score * 10000, 5000) = 0))');
        DB::statement('ALTER TABLE evaluation_scores ADD CONSTRAINT evaluation_scores_component_check CHECK (calculated_component IS NULL OR (calculated_component >= 0.0000 AND calculated_component <= 100.0000))');
        DB::statement('ALTER TABLE evaluation_scores ADD CONSTRAINT evaluation_scores_pair_check CHECK ((score IS NULL AND calculated_component IS NULL) OR (score IS NOT NULL AND calculated_component IS NOT NULL))');
        DB::statement('ALTER TABLE evaluation_scores ADD CONSTRAINT evaluation_scores_comment_check CHECK (comment IS NULL OR CHAR_LENGTH(comment) <= 1000)');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate(self::JUDGE_PERMISSION, 'web');
        Role::findOrCreate('judge', 'web')->givePermissionTo($permission);
        Role::query()->where('guard_name', 'web')->whereIn('name', ['participant', 'reviewer', 'admin'])->each(
            fn (Role $role) => $role->revokePermissionTo($permission)
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if ((Schema::hasTable('evaluations') && DB::table('evaluations')->exists())
            || (Schema::hasTable('evaluation_revisions') && DB::table('evaluation_revisions')->exists())
            || (Schema::hasTable('evaluation_scores') && DB::table('evaluation_scores')->exists())
            || (Schema::hasTable('audit_logs') && DB::table('audit_logs')->where('action', 'like', 'evaluation.%')->exists())) {
            throw new RuntimeException('Cannot remove M6 evaluation tables while draft evaluation evidence exists.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', self::JUDGE_PERMISSION)
            ->first();

        if ($permission && DB::table(config('permission.table_names.model_has_permissions'))
            ->where('permission_id', $permission->id)->exists()) {
            throw new RuntimeException('Cannot remove the M6 permission while it is assigned directly.');
        }

        if (Schema::hasTable('evaluations')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropForeign('evaluations_current_revision_foreign');
            });
        }
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluation_revisions');
        Schema::dropIfExists('evaluations');

        if ($permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
