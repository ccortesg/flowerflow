<?php

namespace Tests\Feature;

use App\Enums\ResidencyVerificationStatus;
use App\Models\ResidencyDocument;
use App\Models\ResidencyDocumentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdmissibilityFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.admissibility_review' => true, 'flowerflow.flags.panel' => true]);
        Storage::fake('residency');
        Storage::fake('clarifications');
        Mail::fake();
        $this->seedFlowerFlow();
    }

    public function test_residency_requests_are_separated_by_team_member(): void
    {
        [$participant, $submission, $review] = $this->submittedReview(true);
        $reviewer = $this->reviewer();
        $member = $submission->team->members()->where('is_representative', false)->firstOrFail();

        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.store', $review), [
            'subject_type' => 'representative',
            'instructions' => 'Carga un comprobante de la persona representante.',
        ])->assertRedirect();
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.store', $review), [
            'subject_type' => 'team_member',
            'subject_team_member_id' => $member->id,
        ])->assertRedirect();

        $this->assertDatabaseCount('residency_document_requests', 2);
        $this->assertDatabaseHas('residency_document_requests', ['subject_user_id' => $participant->id, 'subject_team_member_id' => null]);
        $this->assertDatabaseHas('residency_document_requests', ['subject_user_id' => null, 'subject_team_member_id' => $member->id]);
    }

    public function test_valid_private_file_uses_random_name_hash_and_dedicated_disk(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $residencyRequest = $this->requestForRepresentative($review);
        $file = UploadedFile::fake()->createWithContent('comprobante.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");

        $this->actingAs($participant)->post(route('admissibility.residency.upload', $residencyRequest), [
            'document_type' => 'address_proof',
            'documents' => [$file],
        ])->assertRedirect()->assertSessionHas('status');

        $document = ResidencyDocument::query()->firstOrFail();
        $this->assertSame('residency', $document->disk);
        $this->assertNotSame('comprobante.pdf', $document->stored_name);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}\.pdf$/', $document->stored_name);
        $this->assertSame(64, strlen($document->sha256));
        Storage::disk('residency')->assertExists($document->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'residency.document_uploaded', 'actor_user_id' => $participant->id]);
    }

    public function test_false_signature_hostile_name_encrypted_and_active_pdf_are_rejected(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $residencyRequest = $this->requestForRepresentative($review);

        foreach ([
            UploadedFile::fake()->createWithContent('falso.jpg', 'MZ executable'),
            UploadedFile::fake()->createWithContent('.oculto.pdf', "%PDF-1.4\n%%EOF"),
            UploadedFile::fake()->createWithContent('cifrado.pdf', "%PDF-1.4\n/Encrypt 4 0 R\n%%EOF"),
            UploadedFile::fake()->createWithContent('activo.pdf', "%PDF-1.4\n/JavaScript 4 0 R\n%%EOF"),
        ] as $file) {
            $this->actingAs($participant)->post(route('admissibility.residency.upload', $residencyRequest), [
                'document_type' => 'address_proof',
                'documents' => [$file],
            ])->assertSessionHasErrors('documents.0');
        }

        $this->assertDatabaseCount('residency_documents', 0);
    }

    public function test_file_count_and_accumulated_quota_are_enforced(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $residencyRequest = $this->requestForRepresentative($review);
        for ($index = 1; $index <= 3; $index++) {
            $residencyRequest->documents()->create([
                'uploader_user_id' => $participant->id,
                'document_type' => 'address_proof',
                'disk' => 'residency',
                'path' => "existing/$index.pdf",
                'original_name' => "$index.pdf",
                'stored_name' => "$index.pdf",
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'size_bytes' => $index === 1 ? (10 * 1024 * 1024) - 10 : 1,
                'sha256' => str_repeat((string) $index, 64),
                'created_at' => now('UTC'),
            ]);
        }

        $this->actingAs($participant)->post(route('admissibility.residency.upload', $residencyRequest), [
            'document_type' => 'address_proof',
            'documents' => [UploadedFile::fake()->createWithContent('extra.pdf', "%PDF-1.4\n1234567890\n%%EOF")],
        ])->assertSessionHasErrors('documents');
        $this->assertDatabaseCount('residency_documents', 3);
    }

    public function test_authorized_download_is_audited_and_cross_participant_is_denied(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $other = $this->participant();
        $residencyRequest = $this->requestForRepresentative($review);
        $document = $residencyRequest->documents()->create([
            'uploader_user_id' => $participant->id,
            'document_type' => 'address_proof',
            'disk' => 'residency',
            'path' => 'requests/private/document.pdf',
            'original_name' => 'document.pdf',
            'stored_name' => '01TESTDOCUMENT.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 20,
            'sha256' => str_repeat('d', 64),
            'created_at' => now('UTC'),
        ]);
        Storage::disk('residency')->put($document->path, "%PDF-1.4\n%%EOF");

        $this->actingAs($other)->get(route('admissibility.residency-documents.download', $document))->assertForbidden();
        $this->actingAs($participant)->get(route('admissibility.residency-documents.download', $document))->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'residency.document_downloaded', 'actor_user_id' => $participant->id]);
    }

    public function test_equivalent_document_requires_manual_justification_and_age_is_not_auto_rejected(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $residencyRequest = $this->requestForRepresentative($review);

        $this->actingAs($participant)->post(route('admissibility.residency.upload', $residencyRequest), [
            'document_type' => 'equivalent',
            'equivalent_description' => 'Documento equivalente con una fecha histórica; requiere valoración humana.',
            'documents' => [UploadedFile::fake()->createWithContent('equivalente.pdf', "%PDF-1.4\nFecha 2020-01-01\n%%EOF")],
        ])->assertRedirect();
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.review', [$review, $residencyRequest]))->assertRedirect();

        $payload = ['residency_status' => 'verified', 'review_reason' => '', 'confirm_resolution' => '1'];
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.resolve', [$review, $residencyRequest]), $payload)
            ->assertSessionHasErrors('review_reason');
        $this->assertSame(ResidencyVerificationStatus::UnderReview, $residencyRequest->fresh()->status);

        $payload['review_reason'] = 'Se valoró manualmente como equivalente y conserva nombre, domicilio y fecha aplicables.';
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.resolve', [$review, $residencyRequest]), $payload)->assertRedirect();
        $residencyRequest->refresh();
        $this->assertSame(ResidencyVerificationStatus::Verified, $residencyRequest->status);
        $this->assertNotNull($residencyRequest->validated_at);
        $this->assertEquals(90, $residencyRequest->validated_at->diffInDays($residencyRequest->retention_due_at));
        $this->assertDatabaseHas('audit_logs', ['action' => 'residency.verified', 'actor_user_id' => $reviewer->id]);

        $this->artisan('flowerflow:residency-retention-report', ['--as-of' => $residencyRequest->retention_due_at->addDay()->toIso8601String()])
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();
        Storage::disk('residency')->assertExists(ResidencyDocument::query()->firstOrFail()->path);
    }

    private function requestForRepresentative($review): ResidencyDocumentRequest
    {
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.store', $review), [
            'subject_type' => 'representative',
        ])->assertRedirect();

        return ResidencyDocumentRequest::query()->latest('id')->firstOrFail();
    }
}
