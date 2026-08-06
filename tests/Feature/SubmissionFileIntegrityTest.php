<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Services\SubmissionFileStore;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SubmissionFileIntegrityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.submissions' => true]);
        Storage::fake('local');
        $this->seedFlowerFlow();
    }

    public function test_store_returns_the_record_normalizes_windows_names_and_compensates_persistence_failure(): void
    {
        $user = $this->participant();
        $submission = $this->submissionFor($user);
        $this->actingAs($user);
        $store = app(SubmissionFileStore::class);

        $stored = $store->store(
            $submission,
            UploadedFile::fake()->createWithContent('C:\\fakepath\\propuesta.pdf', "%PDF-1.4\n%%EOF")
        );

        $this->assertInstanceOf(SubmissionFile::class, $stored);
        $this->assertSame('propuesta.pdf', $stored->original_name);
        Storage::disk('local')->assertExists($stored->path);

        $unsaved = new Submission(['public_id' => (string) Str::ulid()]);
        try {
            $store->store(
                $unsaved,
                UploadedFile::fake()->createWithContent('C:\\fakepath\\fallo.pdf', "%PDF-1.4\n%%EOF")
            );
            $this->fail('La persistencia sin propuesta debió fallar.');
        } catch (QueryException) {
            $this->assertSame([], Storage::disk('local')->allFiles('submissions/'.$unsaved->public_id));
        }
    }

    public function test_multiple_uploads_are_removed_when_a_later_database_step_rolls_back(): void
    {
        $user = $this->participant();
        $submission = $this->submissionFor($user);

        DB::listen(function (QueryExecuted $query): void {
            if (str_contains($query->sql, 'insert into `submission_events`')) {
                throw new RuntimeException('Fallo sintético posterior a los uploads.');
            }
        });

        try {
            $this->withoutExceptionHandling()->actingAs($user)->put(route('submissions.update', $submission), [
                'wizard_step' => 3,
                'wizard_action' => 'save',
                'documents' => [
                    UploadedFile::fake()->createWithContent('uno.pdf', "%PDF-1.4\n%%EOF"),
                    UploadedFile::fake()->createWithContent('dos.pdf', "%PDF-1.4\n%%EOF"),
                ],
            ]);
            $this->fail('La transacción debió revertirse.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Fallo sintético posterior a los uploads.', $exception->getMessage());
        }

        $this->assertDatabaseCount('submission_files', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('submissions/'.$submission->public_id));
    }

    public function test_database_deletion_succeeds_and_storage_failure_is_recorded_as_an_orphan(): void
    {
        $user = $this->participant();
        $submission = $this->submissionFor($user);
        $file = $this->fileFor($submission, $user, 'submissions/'.$submission->public_id.'/orphan.pdf');
        Storage::disk('local')->put($file->path, '%PDF-1.4');

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('delete')->once()->with($file->path)->andReturnFalse();
        $disk->shouldReceive('exists')->once()->with($file->path)->andReturnTrue();
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        Log::shouldReceive('warning')->once()->with(
            'Archivo huérfano de propuesta pendiente de conciliación.',
            Mockery::on(fn (array $context) => $context['file_public_id'] === $file->public_id)
        );

        $this->actingAs($user)->delete(route('submissions.files.destroy', [$submission, $file]))
            ->assertRedirect();

        $this->assertDatabaseMissing('submission_files', ['id' => $file->id]);
        $this->assertDatabaseHas('submission_events', [
            'submission_id' => $submission->id,
            'event' => 'file_deleted',
        ]);
    }

    public function test_storage_audit_reports_missing_and_orphaned_paths_without_mutating_them(): void
    {
        $user = $this->participant();
        $submission = $this->submissionFor($user);
        $missing = $this->fileFor($submission, $user, 'submissions/'.$submission->public_id.'/missing.pdf');
        $orphanedPath = 'submissions/'.$submission->public_id.'/orphaned.pdf';
        Storage::disk('local')->put($orphanedPath, '%PDF-1.4');

        Artisan::call('flowerflow:storage-audit', ['--disk' => 'local', '--json' => true]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([$missing->path], $report['missing']);
        $this->assertSame([$orphanedPath], $report['orphaned']);
        $this->assertDatabaseHas('submission_files', ['id' => $missing->id]);
        Storage::disk('local')->assertExists($orphanedPath);
    }

    private function submissionFor(User $user): Submission
    {
        $category = Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'participation_type' => 'individual',
            'title' => 'Propuesta sintética de archivos',
            'summary' => 'Resumen sintético para probar integridad de archivos.',
        ]);
    }

    private function fileFor(Submission $submission, User $user, string $path): SubmissionFile
    {
        return $submission->files()->create([
            'actor_user_id' => $user->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => $path,
            'original_name' => basename($path),
            'stored_name' => basename($path),
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('a', 64),
        ]);
    }
}
