<?php

use App\Enums\BlindReviewPackageStatus;
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
        'view blind review packages',
        'manage blind review packages',
    ];

    public function up(): void
    {
        Schema::create('blind_review_packages', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('submission_version_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('schema_version');
            $table->string('status', 24)->default(BlindReviewPackageStatus::Draft->value)->index();
            $table->json('payload');
            $table->char('payload_sha256', 64);
            $table->foreignId('generated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('generation_reason', 1000);
            $table->timestamp('generated_at');
            $table->foreignId('activated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('activation_reason', 1000)->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('invalidated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('invalidation_reason', 1000)->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blind_review_package_files', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('blind_review_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('submission_file_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('display_order');
            $table->string('file_class', 24);
            $table->string('neutral_label', 120);
            $table->string('expected_mime', 150);
            $table->string('expected_extension', 16);
            $table->unsignedBigInteger('expected_size_bytes');
            $table->char('expected_sha256', 64);
            $table->string('status', 24)->default(BlindReviewPackageStatus::Draft->value)->index();
            $table->timestamps();

            $table->unique(['blind_review_package_id', 'submission_file_id'], 'blind_package_file_unique');
            $table->unique(['blind_review_package_id', 'display_order'], 'blind_package_order_unique');
            $table->unique(['blind_review_package_id', 'neutral_label'], 'blind_package_label_unique');
        });

        DB::statement("ALTER TABLE blind_review_packages ADD CONSTRAINT blind_review_packages_status_check CHECK (status IN ('draft', 'active', 'invalidated'))");
        DB::statement('ALTER TABLE blind_review_packages ADD CONSTRAINT blind_review_packages_schema_check CHECK (schema_version = 1)');
        DB::statement("ALTER TABLE blind_review_packages ADD CONSTRAINT blind_review_packages_hash_check CHECK (payload_sha256 REGEXP '^[0-9a-f]{64}$')");
        DB::statement('ALTER TABLE blind_review_packages ADD CONSTRAINT blind_review_packages_generation_reason_check CHECK (CHAR_LENGTH(generation_reason) BETWEEN 20 AND 1000)');
        DB::statement("ALTER TABLE blind_review_packages ADD CONSTRAINT blind_review_packages_lifecycle_check CHECK ((status = 'draft' AND activated_by_user_id IS NULL AND activation_reason IS NULL AND activated_at IS NULL AND invalidated_by_user_id IS NULL AND invalidation_reason IS NULL AND invalidated_at IS NULL) OR (status = 'active' AND activated_by_user_id IS NOT NULL AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND activated_at IS NOT NULL AND invalidated_by_user_id IS NULL AND invalidation_reason IS NULL AND invalidated_at IS NULL) OR (status = 'invalidated' AND invalidated_by_user_id IS NOT NULL AND CHAR_LENGTH(invalidation_reason) BETWEEN 20 AND 1000 AND invalidated_at IS NOT NULL))");
        DB::statement("ALTER TABLE blind_review_package_files ADD CONSTRAINT blind_review_package_files_class_check CHECK (file_class IN ('document', 'editor_image'))");
        DB::statement("ALTER TABLE blind_review_package_files ADD CONSTRAINT blind_review_package_files_status_check CHECK (status IN ('draft', 'active', 'invalidated'))");
        DB::statement('ALTER TABLE blind_review_package_files ADD CONSTRAINT blind_review_package_files_order_check CHECK (display_order > 0)');
        DB::statement("ALTER TABLE blind_review_package_files ADD CONSTRAINT blind_review_package_files_hash_check CHECK (expected_sha256 REGEXP '^[0-9a-f]{64}$')");

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (self::ADMIN_PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('admin', 'web')->givePermissionTo(self::ADMIN_PERMISSIONS);
        Role::query()->where('guard_name', 'web')->whereIn('name', ['participant', 'reviewer', 'judge'])->each(
            fn (Role $role) => $role->revokePermissionTo(self::ADMIN_PERMISSIONS)
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if ((Schema::hasTable('blind_review_packages') && DB::table('blind_review_packages')->exists())
            || (Schema::hasTable('blind_review_package_files') && DB::table('blind_review_package_files')->exists())) {
            throw new RuntimeException('Cannot remove M5 package tables while blind review evidence exists.');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::ADMIN_PERMISSIONS)
            ->get();
        $permissionIds = $permissions->pluck('id');

        if ($permissionIds->isNotEmpty()
            && DB::table(config('permission.table_names.model_has_permissions'))
                ->whereIn('permission_id', $permissionIds)
                ->exists()) {
            throw new RuntimeException('Cannot remove M5 permissions while one is assigned directly.');
        }

        Schema::dropIfExists('blind_review_package_files');
        Schema::dropIfExists('blind_review_packages');

        foreach ($permissions as $permission) {
            Role::query()->where('guard_name', 'web')->each(
                fn (Role $role) => $role->revokePermissionTo($permission)
            );
            $permission->delete();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
