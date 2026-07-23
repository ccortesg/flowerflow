<?php

namespace Tests\Feature;

use App\Models\ClarificationRequest;
use App\Models\ResidencyDocumentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdmissibilityAuthorizationTest extends TestCase
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

    public function test_participants_are_isolated_and_cannot_enter_panel(): void
    {
        [$owner, $submission, $review] = $this->submittedReview();
        $other = $this->participant();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.clarifications.store', $review), [
            'message' => 'Aclaración sintética privada.',
        ])->assertRedirect();
        $clarification = ClarificationRequest::query()->firstOrFail();

        $this->actingAs($other)->get(route('submissions.show', $submission))->assertForbidden();
        $this->actingAs($other)->post(route('admissibility.clarifications.respond', $clarification), [
            'response' => 'Acceso cruzado.',
        ])->assertForbidden();
        $this->actingAs($owner)->get(route('panel.admissibility.index'))->assertForbidden();
        $this->assertDatabaseCount('clarification_responses', 0);
    }

    public function test_reviewer_admin_unassigned_user_and_future_judge_have_explicit_boundaries(): void
    {
        [, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $admin = $this->admin();
        $unassigned = User::factory()->create();
        $judge = User::factory()->create();
        Role::findOrCreate('judge', 'web');
        $judge->assignRole('judge');

        $this->actingAs($reviewer)->get(route('panel.admissibility.index'))->assertOk();
        $this->actingAs($admin)->get(route('panel.admissibility.show', $review))->assertOk();
        $this->actingAs($unassigned)->get(route('panel.admissibility.index'))
            ->assertForbidden()
            ->assertSee('No tienes permiso para entrar a esta sección')
            ->assertDontSee('User does not have the right permissions');
        $this->actingAs($judge)->get(route('panel.admissibility.show', $review))
            ->assertForbidden()
            ->assertSee('No tienes permiso para entrar a esta sección');
        $this->actingAs($judge)->get(route('panel.submissions.show', $review->submission))->assertForbidden();
    }

    public function test_staff_without_download_permission_cannot_download_residency(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.residency.store', $review), [
            'subject_type' => 'representative',
        ])->assertRedirect();
        $request = ResidencyDocumentRequest::query()->firstOrFail();
        $document = $request->documents()->create([
            'uploader_user_id' => $participant->id,
            'document_type' => 'address_proof',
            'disk' => 'residency',
            'path' => 'private/residency.pdf',
            'original_name' => 'residency.pdf',
            'stored_name' => 'TEST.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('f', 64),
            'created_at' => now('UTC'),
        ]);
        Storage::disk('residency')->put($document->path, '%PDF-1.4');

        $limited = User::factory()->create();
        $limited->givePermissionTo(['view panel', 'view admissibility reviews', 'view residency documents']);
        $this->actingAs($limited)->get(route('admissibility.residency-documents.download', $document))->assertForbidden();
        $this->actingAs($reviewer)->get(route('admissibility.residency-documents.download', $document))->assertOk();
    }

    public function test_internal_notes_and_sensitive_events_never_appear_to_participant(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), [
            'decision' => 'not_admitted',
            'participant_reason' => 'Falta información esencial para acreditar el cumplimiento.',
            'internal_notes' => 'No compartir esta nota interna sintética.',
            'confirm_resolution' => '1',
        ])->assertRedirect();

        $this->actingAs($participant)->get(route('submissions.show', $review->submission))
            ->assertOk()
            ->assertSee('Falta información esencial')
            ->assertDontSee('No compartir esta nota interna')
            ->assertDontSee($reviewer->email);
        $this->actingAs($reviewer)->get(route('panel.admissibility.show', $review))
            ->assertOk()
            ->assertSee('No compartir esta nota interna');
    }

    public function test_feature_flag_hides_routes_and_menu_when_disabled(): void
    {
        [, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        config(['flowerflow.flags.admissibility_review' => false]);

        $this->actingAs($reviewer)->get(route('panel.admissibility.index'))->assertNotFound();
        $this->actingAs($reviewer)->get(route('panel.dashboard'))->assertOk()->assertDontSee('Admisibilidad');
        $this->actingAs($review->submission->user)->get(route('submissions.show', $review->submission))
            ->assertOk()
            ->assertDontSee('Revisión de participación');

        config(['flowerflow.flags.admissibility_review' => true]);
        $this->actingAs($reviewer)->get(route('panel.dashboard'))->assertOk()->assertSee('Admisibilidad');
    }
}
