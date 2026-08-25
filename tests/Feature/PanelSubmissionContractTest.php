<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PanelSubmissionContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.panel' => true]);
        Storage::fake('local');
        $this->seedFlowerFlow();
    }

    public function test_admin_and_reviewer_can_list_view_and_download_submission_files(): void
    {
        $owner = $this->participant();
        $submission = $this->submissionFor($owner, 'Contrato visible del panel');
        $file = $this->fileFor($submission, $owner, 'contrato.pdf');
        Storage::disk('local')->put($file->path, '%PDF-1.4');

        $this->actingAs($owner)->get(route('submissions.files.download', [$submission, $file]))
            ->assertOk()
            ->assertDownload($file->original_name);

        foreach ([$this->admin(), $this->reviewer()] as $staff) {
            $this->actingAs($staff)->get(route('panel.submissions.index'))
                ->assertOk()
                ->assertSee('Contrato visible del panel');
            $this->actingAs($staff)->get(route('panel.submissions.show', $submission))
                ->assertOk()
                ->assertSee($file->original_name);
            $this->actingAs($staff)->get(route('submissions.files.download', [$submission, $file]))
                ->assertOk()
                ->assertDownload($file->original_name);
        }

        $this->assertSame(3, AuditLog::query()->where('action', 'submission.file_downloaded')->count());
    }

    public function test_cross_owner_permission_and_direct_url_downloads_are_rejected(): void
    {
        $owner = $this->participant();
        $other = $this->participant();
        $submission = $this->submissionFor($owner, 'Propuesta privada A');
        $otherSubmission = $this->submissionFor($other, 'Propuesta privada B');
        $file = $this->fileFor($submission, $owner, 'owner.pdf');
        $otherFile = $this->fileFor($otherSubmission, $other, 'other.pdf');
        Storage::disk('local')->put($file->path, '%PDF-1.4');
        Storage::disk('local')->put($otherFile->path, '%PDF-1.4');

        $limited = User::factory()->create();
        $limited->givePermissionTo('view panel');
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(['view panel', 'view submissions']);

        $this->actingAs($other)->get(route('submissions.files.download', [$submission, $file]))
            ->assertForbidden();
        $this->actingAs($limited)->get(route('submissions.files.download', [$submission, $file]))
            ->assertForbidden();
        $this->actingAs($limited)->get(route('panel.submissions.index'))
            ->assertForbidden();
        $this->actingAs($viewOnly)->get(route('submissions.files.download', [$submission, $file]))
            ->assertForbidden();
        $this->actingAs($this->admin())->get(route('submissions.files.download', [$submission, $otherFile]))
            ->assertNotFound();
    }

    public function test_actions_column_and_mutation_buttons_are_visible_only_to_authorized_admin_for_drafts(): void
    {
        config([
            'flowerflow.flags.submission_reminders' => true,
            'flowerflow.flags.administrative_finalization' => true,
        ]);
        $draft = $this->submissionFor($this->participant(), 'Borrador con acciones');
        $submitted = $this->submissionFor(
            $this->participant(),
            'Enviada sin acciones',
            'submitted',
        );

        $this->actingAs($this->admin())->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee('Acciones')
            ->assertSee(route('panel.submissions.reminders.submissions.store', $draft), false)
            ->assertSee(route('panel.submissions.administrative-finalization.show', $draft), false)
            ->assertDontSee(route('panel.submissions.reminders.submissions.store', $submitted), false)
            ->assertDontSee(route('panel.submissions.administrative-finalization.show', $submitted), false);

        $this->actingAs($this->reviewer())->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee('Acciones')
            ->assertDontSee(route('panel.submissions.reminders.submissions.store', $draft), false)
            ->assertDontSee(route('panel.submissions.administrative-finalization.show', $draft), false);
    }

    public function test_panel_reference_filter_matches_partial_folio_or_public_id_with_other_filters(): void
    {
        $admin = $this->admin();
        $target = $this->submissionFor($this->participant(), 'Objetivo por referencia', 'submitted');
        $other = $this->submissionFor($this->participant(), 'Otra propuesta enviada', 'submitted');
        $draft = $this->submissionFor($this->participant(), 'Borrador excluido');

        $this->actingAs($admin)->get(route('panel.submissions.index', [
            'folio' => substr((string) $target->folio, 4, 7),
            'status' => 'submitted',
            'category' => $target->category->slug,
        ]))->assertOk()
            ->assertSee('Objetivo por referencia')
            ->assertDontSee('Otra propuesta enviada')
            ->assertDontSee('Borrador excluido');

        $this->actingAs($admin)->get(route('panel.submissions.index', [
            'folio' => substr($target->public_id, 8, 10),
        ]))->assertOk()
            ->assertSee('Objetivo por referencia')
            ->assertDontSee('Otra propuesta enviada')
            ->assertSee('Folio o ID de propuesta');

        $this->actingAs($admin)->get(route('panel.submissions.index', ['folio' => '%_\\']))
            ->assertOk()
            ->assertDontSee($target->folio)
            ->assertDontSee($other->folio);
        $this->actingAs($admin)->get(route('panel.submissions.index', ['folio' => str_repeat('B', 65)]))
            ->assertRedirect()
            ->assertSessionHasErrors('folio');
    }

    private function submissionFor(User $user, string $title, string $status = 'draft'): Submission
    {
        $category = Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => $title,
            'summary' => 'Resumen sintético del contrato de panel.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética.',
            'status' => $status,
            'submitted_at' => $status === 'submitted' ? now('UTC') : null,
            'folio' => $status === 'submitted' ? 'HMO26-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT) : null,
        ]);
    }

    private function fileFor(Submission $submission, User $user, string $name): SubmissionFile
    {
        return $submission->files()->create([
            'actor_user_id' => $user->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/'.$submission->public_id.'/'.$name,
            'original_name' => $name,
            'stored_name' => $name,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('b', 64),
        ]);
    }
}
