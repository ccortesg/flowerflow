<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_reviews', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('submission_version_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('pending')->index();
            $table->text('participant_reason')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('eligibility_review_events', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('eligibility_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->text('participant_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['eligibility_review_id', 'event']);
        });

        Schema::create('clarification_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('eligibility_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('open')->index();
            $table->text('message');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clarification_responses', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('clarification_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responder_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('created_at');
        });

        Schema::create('clarification_response_files', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('clarification_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_user_id')->constrained('users')->restrictOnDelete();
            $table->string('disk', 40);
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 150);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamp('created_at');
        });

        Schema::create('residency_document_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('eligibility_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_team_member_id')->nullable()->constrained('team_members')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('clarification_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('requested')->index();
            $table->text('instructions')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('retention_basis_at')->nullable();
            $table->timestamp('retention_due_at')->nullable()->index();
            $table->string('retention_reason')->nullable();
            $table->timestamps();
            $table->index(['eligibility_review_id', 'subject_user_id'], 'residency_request_user_index');
            $table->index(['eligibility_review_id', 'subject_team_member_id'], 'residency_request_member_index');
        });

        Schema::create('residency_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('residency_document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploader_user_id')->constrained('users')->restrictOnDelete();
            $table->string('document_type', 48);
            $table->text('equivalent_description')->nullable();
            $table->string('disk', 40);
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 150);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamp('created_at');
            $table->index(['residency_document_request_id', 'document_type'], 'residency_document_type_index');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('residency_documents');
        Schema::dropIfExists('residency_document_requests');
        Schema::dropIfExists('clarification_response_files');
        Schema::dropIfExists('clarification_responses');
        Schema::dropIfExists('clarification_requests');
        Schema::dropIfExists('eligibility_review_events');
        Schema::dropIfExists('eligibility_reviews');
    }
};
