<?php

namespace Tests\Feature;

use App\Enums\ClarificationStatus;
use App\Enums\EligibilityReviewStatus;
use App\Mail\AdmissibilityUpdate;
use App\Models\Category;
use App\Models\ClarificationRequest;
use App\Models\EligibilityReview;
use App\Models\ResidencyDocumentRequest;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class AdmissibilityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.submissions' => true,
            'flowerflow.flags.admissibility_review' => true,
            'flowerflow.flags.panel' => true,
        ]);
        Storage::fake('local');
        Storage::fake('residency');
        Storage::fake('clarifications');
        Mail::fake();
        $this->seedFlowerFlow();
    }

    public function test_final_submission_creates_one_review_for_the_immutable_version(): void
    {
        $participant = $this->participant();
        $category = Category::query()->firstOrFail();
        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $participant->id,
            'participation_type' => 'individual',
            'title' => 'Expediente automático',
            'summary' => 'Resumen sintético',
            'description_html' => '<p>Descripción</p>',
            'description_text' => 'Descripción',
            'status' => 'draft',
        ]);
        $submission->files()->create([
            'actor_user_id' => $participant->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/test/proposal.pdf',
            'original_name' => 'proposal.pdf',
            'stored_name' => 'proposal.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 20,
            'sha256' => str_repeat('a', 64),
        ]);
        $payload = ['accept_call_rules' => '1', 'accept_terms' => '1', 'accept_privacy' => '1'];

        $this->actingAs($participant)->post(route('submissions.submit', $submission), $payload)->assertRedirect();
        $this->actingAs($participant)->post(route('submissions.submit', $submission), $payload)->assertRedirect();

        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('eligibility_reviews', 1);
        $review = EligibilityReview::query()->firstOrFail();
        $this->assertSame($submission->versions()->value('id'), $review->submission_version_id);
        $this->assertSame(EligibilityReviewStatus::Pending, $review->status);
        $this->assertDatabaseCount('eligibility_review_events', 1);
    }

    public function test_backfill_is_idempotent_and_supports_dry_run(): void
    {
        [, $submission, $review] = $this->submittedReview();
        $review->events()->delete();
        $review->delete();

        $this->artisan('flowerflow:admissibility-backfill', ['--dry-run' => true])
            ->expectsOutputToContain('Expedientes faltantes: 1')
            ->assertSuccessful();
        $this->assertDatabaseCount('eligibility_reviews', 0);

        $this->artisan('flowerflow:admissibility-backfill')->assertSuccessful();
        $this->artisan('flowerflow:admissibility-backfill')->assertSuccessful();
        $this->assertDatabaseCount('eligibility_reviews', 1);
        $this->assertSame($submission->id, EligibilityReview::query()->value('submission_id'));
    }

    public function test_clarification_is_append_only_blocks_decision_and_preserves_snapshot(): void
    {
        [$participant, $submission, $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $snapshot = $review->submissionVersion->snapshot;

        $this->actingAs($reviewer)->post(route('panel.admissibility.start', $review))->assertRedirect();
        $this->actingAs($reviewer)->post(route('panel.admissibility.clarifications.store', $review), [
            'message' => 'Aclara el alcance de una frase sin modificar el proyecto.',
            'due_at' => '2026-08-10T18:30',
        ])->assertRedirect();
        $clarification = ClarificationRequest::query()->firstOrFail();
        $this->assertSame('2026-08-11 01:30:00', $clarification->due_at->utc()->format('Y-m-d H:i:s'));
        $this->actingAs($reviewer)->get(route('panel.admissibility.show', $review))
            ->assertOk()
            ->assertSee('Aclaración solicitada')
            ->assertDontSee('clarification requested');

        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), [
            'decision' => 'admitted',
            'participant_reason' => 'Cumple la revisión administrativa.',
            'confirm_resolution' => '1',
        ])->assertSessionHasErrors('decision');

        $this->actingAs($participant)->post(route('admissibility.clarifications.respond', $clarification), [
            'response' => 'La frase se refiere únicamente al alcance descrito en la versión enviada.',
        ])->assertRedirect();
        $this->actingAs($participant)->post(route('admissibility.clarifications.respond', $clarification), [
            'response' => 'Intento de sobrescribir.',
        ])->assertForbidden();

        $this->assertDatabaseCount('clarification_responses', 1);
        $this->assertSame(ClarificationStatus::Answered, $clarification->fresh()->status);
        $this->assertSame($snapshot, $review->submissionVersion->fresh()->snapshot);
        $this->expectException(LogicException::class);
        $review->submissionVersion->update(['snapshot' => ['changed' => true]]);
    }

    public function test_final_decision_is_transactional_idempotent_and_separates_internal_notes(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $payload = [
            'decision' => 'admitted',
            'participant_reason' => 'La información mínima está completa y el archivo es legible.',
            'internal_notes' => 'Nota sintética sólo para personal autorizado.',
            'confirm_resolution' => '1',
        ];

        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), $payload)->assertRedirect();
        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), $payload)->assertRedirect();

        $review->refresh();
        $this->assertSame(EligibilityReviewStatus::Admitted, $review->status);
        $this->assertSame('Nota sintética sólo para personal autorizado.', $review->internal_notes);
        $this->assertSame(1, $review->events()->where('event', 'review_admitted')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'admissibility.admitted', 'actor_user_id' => $reviewer->id]);
        Mail::assertQueued(AdmissibilityUpdate::class, 1);

        $this->actingAs($participant)->get(route('submissions.show', $review->submission))
            ->assertOk()
            ->assertSee('La información mínima está completa')
            ->assertDontSee('Nota sintética sólo para personal autorizado');
    }

    public function test_clarification_response_limit_and_optional_due_date_are_enforced(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.clarifications.store', $review), [
            'message' => 'Solicitud sin fecha límite.',
        ])->assertRedirect();
        $clarification = ClarificationRequest::query()->firstOrFail();
        $this->assertNull($clarification->due_at);

        $this->actingAs($participant)->post(route('admissibility.clarifications.respond', $clarification), [
            'response' => str_repeat('x', 2001),
        ])->assertSessionHasErrors('response');
        $this->assertDatabaseCount('clarification_responses', 0);
    }

    public function test_active_or_rejected_residency_requires_a_separate_human_decision(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.store', $review), [
            'subject_type' => 'representative',
        ])->assertRedirect();
        $residencyRequest = ResidencyDocumentRequest::query()->firstOrFail();

        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), [
            'decision' => 'admitted',
            'participant_reason' => 'Intento prematuro.',
            'confirm_resolution' => '1',
        ])->assertSessionHasErrors('decision');

        $residencyRequest->documents()->create([
            'uploader_user_id' => $participant->id,
            'document_type' => 'address_proof',
            'disk' => 'residency',
            'path' => 'synthetic/rejected.pdf',
            'original_name' => 'rejected.pdf',
            'stored_name' => 'REJECTED.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('e', 64),
            'created_at' => now('UTC'),
        ]);
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.resolve', [$review, $residencyRequest]), [
            'residency_status' => 'rejected',
            'review_reason' => 'El archivo no permite identificar el domicilio.',
            'confirm_resolution' => '1',
        ])->assertRedirect();

        $this->assertSame(EligibilityReviewStatus::InReview, $review->fresh()->status);
        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), [
            'decision' => 'not_admitted',
            'participant_reason' => 'No fue posible verificar el requisito de residencia.',
            'confirm_resolution' => '1',
        ])->assertRedirect();
        $this->assertSame(EligibilityReviewStatus::NotAdmitted, $review->fresh()->status);
    }

    public function test_clarification_attachments_are_private_hashed_and_authorized(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $other = $this->participant();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.clarifications.store', $review), [
            'message' => 'Adjunta una evidencia sintética si resulta necesaria.',
        ])->assertRedirect();
        $clarification = ClarificationRequest::query()->firstOrFail();

        $this->actingAs($participant)->post(route('admissibility.clarifications.respond', $clarification), [
            'response' => 'Respuesta con un archivo privado.',
            'files' => [UploadedFile::fake()->createWithContent('aclaracion.pdf', "%PDF-1.4\n%%EOF")],
        ])->assertRedirect();

        $file = $clarification->responses()->firstOrFail()->files()->firstOrFail();
        $this->assertSame('clarifications', $file->disk);
        $this->assertSame(64, strlen($file->sha256));
        Storage::disk('clarifications')->assertExists($file->path);
        $this->actingAs($other)->get(route('admissibility.clarification-files.download', $file))->assertForbidden();
        $this->actingAs($participant)->get(route('admissibility.clarification-files.download', $file))->assertOk();
    }
}
