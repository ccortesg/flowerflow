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
        'view communication deliveries',
        'manage communication deliveries',
    ];

    public function up(): void
    {
        Schema::create('communication_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->char('idempotency_key', 64)->unique();
            $table->string('notification_type', 64)->index();
            $table->string('variant', 64)->nullable();
            $table->string('template_version', 32)->default('v1');
            $table->string('source_event_key', 191);
            $table->string('channel', 16)->default('email');
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('recipient_address')->nullable();
            $table->string('recipient_mask', 191)->nullable();
            $table->char('recipient_fingerprint', 64);
            $table->longText('context')->nullable();
            $table->nullableMorphs('related');
            $table->string('status', 24)->index();
            $table->string('queue_connection', 32);
            $table->string('queue', 64);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('failure_stage', 48)->nullable();
            $table->string('failure_code', 96)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('unknown_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('context_expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'queued_at'], 'communication_deliveries_status_queued_index');
            $table->index(['notification_type', 'created_at'], 'communication_deliveries_type_created_index');
            $table->index(['recipient_user_id', 'created_at'], 'communication_deliveries_recipient_created_index');
        });

        Schema::create('communication_delivery_attempts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('communication_delivery_id');
            $table->foreign('communication_delivery_id', 'communication_attempts_delivery_fk')
                ->references('id')->on('communication_deliveries')->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('source', 24);
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 24)->index();
            $table->string('queue', 64);
            $table->uuid('worker_job_uuid')->nullable();
            $table->string('failure_stage', 48)->nullable();
            $table->string('failure_code', 96)->nullable();
            $table->boolean('duplicate_risk_acknowledged')->default(false);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['communication_delivery_id', 'attempt_number'], 'communication_delivery_attempt_number_unique');
            $table->index(['requested_by_user_id', 'created_at'], 'communication_attempts_requester_created_index');
        });

        Schema::table('submission_reminders', function (Blueprint $table): void {
            $table->foreignId('communication_delivery_id')->nullable()->after('recipient_user_id')
                ->unique()->constrained('communication_deliveries')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE communication_deliveries ADD CONSTRAINT communication_deliveries_channel_check CHECK (channel = 'email')");
        DB::statement("ALTER TABLE communication_deliveries ADD CONSTRAINT communication_deliveries_status_check CHECK (status IN ('queued', 'processing', 'sent', 'failed', 'unknown', 'cancelled'))");
        DB::statement('ALTER TABLE communication_deliveries ADD CONSTRAINT communication_deliveries_attempts_check CHECK (attempts_count >= 0)');
        DB::statement('ALTER TABLE communication_deliveries ADD CONSTRAINT communication_deliveries_lock_check CHECK (lock_version >= 0)');
        DB::statement("ALTER TABLE communication_delivery_attempts ADD CONSTRAINT communication_attempts_source_check CHECK (source IN ('automatic', 'admin_forced'))");
        DB::statement("ALTER TABLE communication_delivery_attempts ADD CONSTRAINT communication_attempts_status_check CHECK (status IN ('queued', 'processing', 'sent', 'failed', 'unknown', 'cancelled'))");
        DB::statement('ALTER TABLE communication_delivery_attempts ADD CONSTRAINT communication_attempts_number_check CHECK (attempt_number > 0)');

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
        $hasEvidence = (Schema::hasTable('communication_deliveries') && DB::table('communication_deliveries')->exists())
            || (Schema::hasTable('communication_delivery_attempts') && DB::table('communication_delivery_attempts')->exists())
            || (Schema::hasTable('audit_logs') && DB::table('audit_logs')->where('action', 'like', 'communication_delivery.%')->exists());

        if ($hasEvidence) {
            throw new RuntimeException('Cannot remove the communication ledger while operational evidence exists. Disable FLOWERFLOW_COMMUNICATION_LEDGER_ENABLED instead.');
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

        Schema::table('submission_reminders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('communication_delivery_id');
        });
        Schema::dropIfExists('communication_delivery_attempts');
        Schema::dropIfExists('communication_deliveries');
    }
};
