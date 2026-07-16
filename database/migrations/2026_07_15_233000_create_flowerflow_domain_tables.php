<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at');
            $table->string('source_timezone', 64);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->ulid('public_id')->unique();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['competition_id', 'slug']);
        });

        Schema::create('participant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_names');
            $table->string('last_names');
            $table->string('mobile_e164', 20);
            $table->boolean('whatsapp_opt_in')->default(false);
            $table->date('birth_date');
            $table->string('neighborhood');
            $table->timestamp('adult_declared_at')->nullable();
            $table->timestamp('hermosillo_resident_declared_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('version', 32);
            $table->string('title');
            $table->string('public_path');
            $table->char('sha256', 64);
            $table->timestamp('effective_at')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('acceptance_required')->default(true);
            $table->timestamps();
            $table->unique(['code', 'version']);
        });

        Schema::create('legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->string('document_version', 32);
            $table->boolean('accepted');
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('eligibility_declared_at')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->boolean('is_representative')->default(false);
            $table->timestamps();
            $table->unique(['team_id', 'email']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('competition_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('participation_type', ['individual', 'team']);
            $table->string('title');
            $table->string('summary', 500);
            $table->json('description_delta')->nullable();
            $table->longText('description_html')->nullable();
            $table->longText('description_text')->nullable();
            $table->enum('status', ['draft', 'submitted', 'withdrawn'])->default('draft');
            $table->string('folio')->nullable()->unique();
            $table->timestamp('submitted_at')->nullable();
            $table->string('submission_idempotency_key')->nullable()->unique();
            $table->timestamps();
            $table->unique(['competition_id', 'user_id', 'category_id']);
            $table->index(['competition_id', 'status']);
        });

        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('kind', ['document', 'editor_image']);
            $table->string('format_category', 32);
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type', 150);
            $table->string('extension', 16);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->timestamps();
        });

        Schema::create('submission_external_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['youtube', 'public_folder']);
            $table->text('url');
            $table->string('normalized_host');
            $table->timestamps();
            $table->unique(['submission_id', 'kind']);
        });

        Schema::create('submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('created_at');
            $table->unique(['submission_id', 'version']);
        });

        Schema::create('submission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['submission_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_events');
        Schema::dropIfExists('submission_versions');
        Schema::dropIfExists('submission_external_links');
        Schema::dropIfExists('submission_files');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('legal_acceptances');
        Schema::dropIfExists('legal_documents');
        Schema::dropIfExists('participant_profiles');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('competitions');
    }
};
