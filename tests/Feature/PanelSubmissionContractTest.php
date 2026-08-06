<?php

namespace Tests\Feature;

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

        $this->actingAs($other)->get(route('submissions.files.download', [$submission, $file]))
            ->assertForbidden();
        $this->actingAs($limited)->get(route('submissions.files.download', [$submission, $file]))
            ->assertForbidden();
        $this->actingAs($limited)->get(route('panel.submissions.index'))
            ->assertForbidden();
        $this->actingAs($this->admin())->get(route('submissions.files.download', [$submission, $otherFile]))
            ->assertNotFound();
    }

    private function submissionFor(User $user, string $title): Submission
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
