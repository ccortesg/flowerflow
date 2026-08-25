<?php

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
        'submit own evaluations' => 'judge',
        'view evaluations' => 'admin',
        'reopen evaluations' => 'admin',
        'manage reopened evaluations' => 'admin',
    ];

    private const AUDIT_ACTIONS = [
        'evaluation.submitted',
        'evaluation.submission_rejected',
        'evaluation.submission_rejected_stale',
        'evaluation.reopened',
        'evaluation.reopen_rejected',
        'evaluation.reopen_rejected_stale',
        'evaluation.reopened_draft_saved',
        'evaluation.reopened_draft_saved_administratively',
        'evaluation.reopened_draft_save_rejected',
        'evaluation.reopened_draft_save_rejected_stale',
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE evaluations DROP CHECK evaluations_status_check');
        DB::statement('ALTER TABLE evaluation_revisions DROP CHECK evaluation_revisions_status_check');

        Schema::table('evaluation_revisions', function (Blueprint $table) {
            $table->foreignId('subject_judge_profile_id')->nullable()->after('source_revision_id')
                ->constrained('judge_profiles')->restrictOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->after('last_saved_by_user_id')
                ->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by_user_id');
            $table->string('submission_mode', 24)->nullable()->after('submitted_at');
        });

        DB::statement(<<<'SQL'
            UPDATE evaluation_revisions er
            INNER JOIN evaluations e ON e.id = er.evaluation_id
            INNER JOIN judge_assignments ja ON ja.id = e.judge_assignment_id
            SET er.subject_judge_profile_id = ja.judge_profile_id
            WHERE er.subject_judge_profile_id IS NULL
        SQL);
        if (DB::table('evaluation_revisions')->whereNull('subject_judge_profile_id')->exists()) {
            throw new RuntimeException('Cannot backfill the subject judge for every M6 evaluation revision.');
        }
        DB::statement('ALTER TABLE evaluation_revisions MODIFY subject_judge_profile_id BIGINT UNSIGNED NOT NULL');

        Schema::create('evaluation_reopenings', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('evaluation_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_revision_id')->unique()->constrained('evaluation_revisions')->restrictOnDelete();
            $table->foreignId('target_revision_id')->unique()->constrained('evaluation_revisions')->restrictOnDelete();
            $table->foreignId('subject_judge_profile_id')->constrained('judge_profiles')->restrictOnDelete();
            $table->foreignId('reopened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->unsignedSmallInteger('reason_plaintext_length');
            $table->timestamp('reopened_at');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE evaluations ADD CONSTRAINT evaluations_status_check CHECK (status IN ('draft', 'reopened', 'submitted'))");
        DB::statement("ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_status_check CHECK (status IN ('draft', 'submitted'))");
        DB::statement("ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_submission_mode_check CHECK (submission_mode IS NULL OR submission_mode IN ('judge', 'administrative'))");
        DB::statement("ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_submission_coherence_check CHECK ((status = 'draft' AND submitted_by_user_id IS NULL AND submitted_at IS NULL AND submission_mode IS NULL) OR (status = 'submitted' AND submitted_by_user_id IS NOT NULL AND submitted_at IS NOT NULL AND submission_mode IS NOT NULL AND total_raw IS NOT NULL AND general_comment IS NOT NULL AND CHAR_LENGTH(TRIM(general_comment)) BETWEEN 100 AND 2000))");
        DB::statement('ALTER TABLE evaluation_reopenings ADD CONSTRAINT evaluation_reopenings_reason_length_check CHECK (reason_plaintext_length BETWEEN 20 AND 1000)');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name => $roleName) {
            $permission = Permission::findOrCreate($name, 'web');
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permission);
            Role::query()->where('guard_name', 'web')->where('name', '<>', $roleName)->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if ($this->hasM7Evidence()) {
            throw new RuntimeException('Cannot remove M7 while submitted or reopened evaluation evidence exists.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (array_keys(self::PERMISSIONS) as $name) {
            $permission = Permission::query()->where('guard_name', 'web')->where('name', $name)->first();
            if (! $permission) {
                continue;
            }
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            if (DB::table(config('permission.table_names.model_has_permissions'))
                ->where('permission_id', $permission->id)->exists()) {
                throw new RuntimeException("Cannot remove directly assigned M7 permission: {$name}.");
            }
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::statement('ALTER TABLE evaluation_revisions DROP CHECK evaluation_revisions_submission_coherence_check');
        DB::statement('ALTER TABLE evaluation_revisions DROP CHECK evaluation_revisions_submission_mode_check');
        DB::statement('ALTER TABLE evaluation_revisions DROP CHECK evaluation_revisions_status_check');
        DB::statement('ALTER TABLE evaluations DROP CHECK evaluations_status_check');
        Schema::dropIfExists('evaluation_reopenings');

        Schema::table('evaluation_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitted_by_user_id');
            $table->dropConstrainedForeignId('subject_judge_profile_id');
            $table->dropColumn(['submitted_at', 'submission_mode']);
        });

        DB::statement("ALTER TABLE evaluations ADD CONSTRAINT evaluations_status_check CHECK (status = 'draft')");
        DB::statement("ALTER TABLE evaluation_revisions ADD CONSTRAINT evaluation_revisions_status_check CHECK (status = 'draft')");
    }

    private function hasM7Evidence(): bool
    {
        return (Schema::hasTable('evaluation_reopenings') && DB::table('evaluation_reopenings')->exists())
            || DB::table('evaluations')->whereIn('status', ['submitted', 'reopened'])->exists()
            || DB::table('evaluation_revisions')->where(function ($query): void {
                $query->where('status', 'submitted')
                    ->orWhere('revision_number', '>', 1)
                    ->orWhereNotNull('source_revision_id')
                    ->orWhereNotNull('submitted_by_user_id')
                    ->orWhereNotNull('submitted_at')
                    ->orWhereNotNull('submission_mode');
            })->exists()
            || (Schema::hasTable('audit_logs') && DB::table('audit_logs')->whereIn('action', self::AUDIT_ACTIONS)->exists());
    }
};
