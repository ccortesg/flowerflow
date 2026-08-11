<?php

namespace Tests\Feature;

use App\Enums\SubmissionExportStatus;
use App\Jobs\GenerateSubmissionExport;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Submission;
use App\Models\SubmissionExport;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class SubmissionExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.exports.queue_connection' => 'sync',
            'flowerflow.exports.disk' => 'exports',
            'flowerflow.exports.retention_hours' => 24,
        ]);
        Storage::fake('local');
        Storage::fake('exports');
        $this->seedFlowerFlow();
    }

    public function test_authorized_recently_confirmed_admin_can_generate_complete_private_workbook(): void
    {
        [$draft, $draftFile] = $this->draftProposal('=2+2');
        [$submitted, $submittedFile] = $this->submittedProposal();
        $withdrawn = $this->proposalFor($this->participant(), 'Propuesta retirada', 'withdrawn');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.submissions.exports.store'))
            ->assertRedirect(route('panel.submissions.index'));

        $export = SubmissionExport::query()->sole();
        $this->assertSame(SubmissionExportStatus::Completed, $export->status);
        $this->assertSame(2, $export->proposal_count);
        $this->assertSame(2, $export->contact_count);
        $this->assertSame(1, $export->team_member_count);
        $this->assertSame(2, $export->file_count);
        $this->assertSame(1, $export->external_link_count);
        $this->assertTrue($export->expires_at->isFuture());
        Storage::disk('exports')->assertExists($export->path);

        $workbook = $this->readWorkbook(Storage::disk('exports')->path($export->path));
        $proposalsXml = $this->xlsxEntry(Storage::disk('exports')->path($export->path), 'xl/worksheets/sheet1.xml');
        $stylesXml = $this->xlsxEntry(Storage::disk('exports')->path($export->path), 'xl/styles.xml');
        $this->assertSame(['Propuestas', 'Contactos', 'Integrantes', 'Archivos', 'Enlaces externos'], array_keys($workbook));
        $this->assertSame('=2+2', $workbook['Propuestas'][1][6]->getValue());
        $this->assertMatchesRegularExpression('/<c r="G2"[^>]*t="inlineStr"[^>]*>.*?<t>=2\+2<\/t>/s', $proposalsXml);
        $this->assertDoesNotMatchRegularExpression('/rgb="[0-9A-F]{10}"/', $stylesXml);
        $this->assertStringContainsString('rgb="FFFFFFFF"', $stylesXml);
        $this->assertStringContainsString('fgColor rgb="FF1B5E20"', $stylesXml);
        $this->assertSame('Título inmutable enviado', $workbook['Propuestas'][2][6]->getValue());
        $this->assertNotSame($submitted->title, $workbook['Propuestas'][2][6]->getValue());
        $this->assertSame('Contacto Enviado', $workbook['Contactos'][2][2]->getValue());
        $this->assertSame('Integrante de snapshot', $workbook['Integrantes'][1][3]->getValue());
        $this->assertInstanceOf(FormulaCell::class, $workbook['Archivos'][1][7]);
        $this->assertStringContainsString(route('submissions.files.download', [$draft, $draftFile], false), $workbook['Archivos'][1][7]->getValue());
        $this->assertStringContainsString(route('submissions.files.download', [$submitted, $submittedFile], false), $workbook['Archivos'][2][7]->getValue());

        $allValues = collect($workbook)->flatten(2)->map(fn ($cell) => (string) $cell->getValue());
        $this->assertFalse($allValues->contains($withdrawn->title));
        $this->assertFalse($allValues->contains('1990-01-01'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'submission_export.requested', 'actor_user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'submission_export.completed', 'actor_user_id' => $admin->id]);
    }

    public function test_export_and_attachment_downloads_require_authentication_permissions_and_ownership(): void
    {
        [$submission, $file] = $this->draftProposal('Propuesta privada');
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $reviewer = $this->reviewer();
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(['view panel', 'view submissions']);

        $this->get(route('panel.submissions.exports.create'))->assertRedirect(route('login'));
        $this->actingAs($reviewer)->get(route('panel.submissions.exports.create'))->assertForbidden();
        $this->actingAs($viewOnly)->get(route('panel.submissions.exports.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('panel.submissions.exports.create'))
            ->assertRedirect(route('password.confirm'));
        $this->actingAs($admin)->post(route('panel.submissions.exports.store'))
            ->assertRedirect(route('panel.submissions.exports.create'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.submissions.exports.store'))
            ->assertRedirect(route('panel.submissions.index'));
        $export = SubmissionExport::query()->sole();

        $this->app['auth']->logout();
        $this->get(route('panel.submissions.exports.download', $export))->assertRedirect(route('login'));
        $this->actingAs($otherAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.submissions.exports.download', $export))
            ->assertForbidden();
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.submissions.exports.download', $export))
            ->assertOk()
            ->assertDownload($export->file_name);

        $export->forceFill(['expires_at' => now('UTC')->subMinute()])->save();
        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.submissions.exports.download', $export))
            ->assertGone();

        $this->app['auth']->logout();
        $this->get(route('submissions.files.download', [$submission, $file]))->assertRedirect(route('login'));
        $this->actingAs($viewOnly)->get(route('submissions.files.download', [$submission, $file]))->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'submission_export.downloaded', 'actor_user_id' => $admin->id]);
    }

    public function test_expired_exports_are_deleted_by_scoped_retention_command(): void
    {
        $admin = $this->admin();
        $export = $admin->submissionExports()->create([
            'status' => SubmissionExportStatus::Completed,
            'filters' => ['statuses' => ['draft', 'submitted']],
            'disk' => 'exports',
            'path' => 'submission-exports/purge-test/export.xlsx',
            'file_name' => 'export.xlsx',
            'completed_at' => now('UTC')->subDays(2),
            'expires_at' => now('UTC')->subDay(),
        ]);
        $export->forceFill(['path' => "submission-exports/{$export->public_id}/export.xlsx"])->save();
        Storage::disk('exports')->put($export->path, 'xlsx');

        $this->artisan('flowerflow:exports-purge --dry-run')
            ->expectsOutput('Exportaciones vencidas: 1. No se eliminó ningún archivo.')
            ->assertSuccessful();
        Storage::disk('exports')->assertExists($export->path);

        $this->artisan('flowerflow:exports-purge')
            ->expectsOutput('Exportaciones depuradas: 1.')
            ->assertSuccessful();

        Storage::disk('exports')->assertMissing("submission-exports/{$export->public_id}/export.xlsx");
        $this->assertSame(SubmissionExportStatus::Expired, $export->fresh()->status);
        $this->assertNull($export->fresh()->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'submission_export.expired', 'actor_user_id' => $admin->id]);
    }

    public function test_export_job_payload_is_encrypted_and_runs_after_commit(): void
    {
        $job = new GenerateSubmissionExport(123);

        $this->assertInstanceOf(ShouldBeEncrypted::class, $job);
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $job);
    }

    public function test_failed_export_records_only_a_redacted_failure_code(): void
    {
        $admin = $this->admin();
        $export = $admin->submissionExports()->create([
            'status' => SubmissionExportStatus::Queued,
            'filters' => ['statuses' => ['draft', 'submitted']],
            'disk' => 'exports',
        ]);

        (new GenerateSubmissionExport($export->id))->failed(new RuntimeException('Synthetic secret must not persist'));

        $export->refresh();
        $this->assertSame(SubmissionExportStatus::Failed, $export->status);
        $this->assertSame('RuntimeException', $export->failure_code);
        $this->assertDatabaseMissing('submission_exports', ['failure_code' => 'Synthetic secret must not persist']);
        $audit = AuditLog::query()->where('action', 'submission_export.failed')->sole();
        $this->assertSame(['failure_code' => 'RuntimeException'], $audit->metadata);
    }

    public function test_queue_dispatch_failure_is_redacted_and_returns_an_actionable_warning(): void
    {
        $admin = $this->admin();
        $this->mock(Dispatcher::class)
            ->shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('Synthetic queue detail must not persist'));

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.submissions.exports.store'))
            ->assertRedirect(route('panel.submissions.index'))
            ->assertSessionHas('warning', 'No fue posible programar la exportación. Inténtalo nuevamente.');

        $export = SubmissionExport::query()->sole();
        $this->assertSame(SubmissionExportStatus::Failed, $export->status);
        $this->assertSame('RuntimeException', $export->failure_code);
        $this->assertDatabaseMissing('submission_exports', ['failure_code' => 'Synthetic queue detail must not persist']);
    }

    /** @return array{Submission, SubmissionFile} */
    private function draftProposal(string $title): array
    {
        $owner = $this->participant(['email' => 'draft@example.test']);
        $submission = $this->proposalFor($owner, $title, 'draft');
        $file = $this->fileFor($submission, $owner, 'borrador.pdf');
        $submission->externalLinks()->create([
            'kind' => 'youtube',
            'url' => 'https://youtube.com/watch?v=synthetic',
            'normalized_host' => 'youtube.com',
        ]);

        return [$submission, $file];
    }

    /** @return array{Submission, SubmissionFile} */
    private function submittedProposal(): array
    {
        $owner = $this->participant(['email' => 'submitted@example.test']);
        $teamModel = Team::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Equipo en vivo',
            'eligibility_declared_at' => now('UTC'),
        ]);
        $teamModel->members()->create([
            'full_name' => 'Integrante en vivo',
            'email' => 'miembro@example.test',
            'is_representative' => true,
        ]);
        $submission = $this->proposalFor($owner, 'Título vivo modificado', 'submitted', [
            'team_id' => $teamModel->id,
            'participation_type' => 'team',
            'folio' => 'HMO26-700001',
            'submitted_at' => now('UTC'),
        ]);
        $file = $this->fileFor($submission, $owner, 'enviada.pdf');
        $category = $submission->category;
        $competition = $submission->competition;
        $submission->versions()->create([
            'version' => 1,
            'snapshot' => [
                'schema_version' => 1,
                'submission' => [
                    'public_id' => $submission->public_id,
                    'folio' => $submission->folio,
                    'participation_type' => 'team',
                    'title' => 'Título inmutable enviado',
                    'summary' => 'Resumen inmutable enviado',
                    'description_text' => 'Descripción inmutable enviada',
                    'submitted_at' => $submission->submitted_at->toIso8601String(),
                ],
                'competition' => $competition->only(['public_id', 'slug', 'name']),
                'category' => $category->only(['public_id', 'slug', 'name']),
                'participant' => [
                    'public_id' => $owner->public_id,
                    'email' => 'contacto.snapshot@example.test',
                    'profile' => [
                        'first_names' => 'Contacto Enviado',
                        'last_names' => 'Snapshot',
                        'mobile_e164' => '+526621111111',
                        'whatsapp_opt_in' => true,
                        'birth_date' => '1990-01-01',
                        'neighborhood' => 'Centro snapshot',
                    ],
                ],
                'team' => [
                    'name' => 'Equipo de snapshot',
                    'members' => [[
                        'full_name' => 'Integrante de snapshot',
                        'email' => 'snapshot@example.test',
                        'is_representative' => true,
                    ]],
                ],
                'files' => [$file->only(['public_id', 'kind', 'original_name', 'mime_type', 'extension', 'size_bytes'])],
                'external_links' => [],
            ],
            'created_at' => now('UTC'),
        ]);

        return [$submission, $file];
    }

    private function proposalFor(User $owner, string $title, string $status, array $overrides = []): Submission
    {
        $category = Category::query()->firstOrFail();

        return Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => $title,
            'summary' => 'Resumen sintético para la exportación.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética para la exportación.',
            'status' => $status,
            ...$overrides,
        ]);
    }

    private function fileFor(Submission $submission, User $owner, string $name): SubmissionFile
    {
        $file = $submission->files()->create([
            'actor_user_id' => $owner->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => "submissions/{$submission->public_id}/{$name}",
            'original_name' => $name,
            'stored_name' => $name,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 12,
            'sha256' => str_repeat('a', 64),
        ]);
        Storage::disk('local')->put($file->path, "%PDF-1.4\n%%EOF");

        return $file;
    }

    /**
     * @return array<string, array<int, array<int, Cell>>>
     */
    private function readWorkbook(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $workbook = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $workbook[$sheet->getName()][] = $row->getCells();
                }
            }
        } finally {
            $reader->close();
        }

        return $workbook;
    }

    private function xlsxEntry(string $path, string $entry): string
    {
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path));

        try {
            $contents = $archive->getFromName($entry);
            $this->assertIsString($contents);

            return $contents;
        } finally {
            $archive->close();
        }
    }
}
