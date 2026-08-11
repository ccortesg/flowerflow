<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_exports', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->index();
            $table->json('filters');
            $table->string('disk', 64);
            $table->string('path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedInteger('proposal_count')->default(0);
            $table->unsignedInteger('contact_count')->default(0);
            $table->unsignedInteger('team_member_count')->default(0);
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedInteger('external_link_count')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->timestamps();

            $table->index(['requested_by_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_exports');
    }
};
