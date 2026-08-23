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
        'send submission reminders',
        'administratively finalize submissions',
    ];

    public function up(): void
    {
        Schema::create('submission_reminder_batches', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('scope', 24);
            $table->string('status', 32)->index();
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['requested_by_user_id', 'created_at'], 'reminder_batches_requester_created_index');
        });

        Schema::create('submission_reminders', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('submission_reminder_batch_id')->constrained('submission_reminder_batches')->restrictOnDelete();
            $table->foreignId('submission_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 24)->index();
            $table->string('failure_code', 64)->nullable();
            $table->timestamp('link_expires_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['submission_reminder_batch_id', 'submission_id', 'recipient_user_id'],
                'submission_reminders_batch_submission_recipient_unique'
            );
            $table->index(['submission_id', 'recipient_user_id', 'created_at'], 'submission_reminders_cooldown_index');
        });

        DB::statement("ALTER TABLE submission_reminder_batches ADD CONSTRAINT submission_reminder_batches_scope_check CHECK (scope IN ('single', 'all_drafts'))");
        DB::statement("ALTER TABLE submission_reminder_batches ADD CONSTRAINT submission_reminder_batches_status_check CHECK (status IN ('queued', 'processing', 'completed', 'completed_with_failures', 'failed'))");
        DB::statement("ALTER TABLE submission_reminders ADD CONSTRAINT submission_reminders_status_check CHECK (status IN ('queued', 'processing', 'sent', 'failed', 'skipped'))");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::findOrCreate('admin', 'web');
        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            $admin->givePermissionTo($permission);
            Role::query()->where('guard_name', 'web')->whereIn('name', ['participant', 'reviewer', 'judge'])->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $hasEvidence = (Schema::hasTable('submission_reminder_batches') && DB::table('submission_reminder_batches')->exists())
            || (Schema::hasTable('submission_reminders') && DB::table('submission_reminders')->exists())
            || (Schema::hasTable('submission_events') && DB::table('submission_events')->whereIn('event', [
                'submission.submitted_from_reminder',
                'submission.submitted_administratively',
            ])->exists())
            || (Schema::hasTable('audit_logs') && DB::table('audit_logs')->where(function ($query): void {
                $query->where('action', 'like', 'submission_reminder.%')
                    ->orWhereIn('action', [
                        'submission.submitted_from_reminder',
                        'submission.submitted_administratively',
                    ]);
            })->exists());

        if ($hasEvidence) {
            throw new RuntimeException('Cannot remove submission reminder tables or permissions while operational evidence exists. Disable the feature flags instead.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::query()->where('guard_name', 'web')->where('name', $name)->first();
            if (! $permission) {
                continue;
            }
            if (DB::table(config('permission.table_names.model_has_permissions'))->where('permission_id', $permission->id)->exists()) {
                throw new RuntimeException("Cannot remove permission {$name} while it is assigned directly.");
            }
            Role::query()->where('guard_name', 'web')->each(fn (Role $role) => $role->revokePermissionTo($permission));
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('submission_reminders');
        Schema::dropIfExists('submission_reminder_batches');
    }
};
