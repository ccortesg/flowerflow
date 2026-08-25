<?php

namespace Tests\Feature;

use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\ReopenEvaluation;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\EvaluationExportStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Exceptions\EvaluationExportSourceInvalid;
use App\Jobs\GenerateEvaluationExport;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\EvaluationExport;
use App\Models\JudgeAssignment;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationWorkbookWriter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Reader\XLSX\Reader;
use PhpOffice\PhpSpreadsheet\Cell\DataType as PhpSpreadsheetDataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;
use ZipArchive;

class EvaluationExportTest extends TestCase
{
    use CreatesEvaluationScenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.evaluation_finalization' => true,
            'flowerflow.flags.evaluation_export' => true,
            'flowerflow.flags.evaluation_notifications' => false,
            'flowerflow.exports.queue_connection' => 'sync',
            'flowerflow.exports.disk' => 'exports',
            'flowerflow.exports.retention_hours' => 24,
            'flowerflow.exports.stalled_after_minutes' => 5,
        ]);
        Storage::fake('exports');
        $this->seedFlowerFlow();
    }

    public function test_authorized_admin_exports_every_revision_criterion_and_reopening_from_immutable_snapshot(): void
    {
        [$admin, $judges, $substitutes, $submission, $version] = $this->createEvaluationScenario();
        $this->makeSnapshotExportable($version->id, $submission->public_id, (string) $submission->folio);
        $submission->forceFill(['title' => 'Título vivo que no debe aparecer'])->save();

        $first = $this->assignmentFor($judges->get(0));
        $firstEvaluation = $this->complete($judges->get(0), $first, '8.0', '=COMENTARIO literal de criterio');
        app(SubmitEvaluation::class)->execute($first, $judges->get(0), [
            'lock_version' => $firstEvaluation->lock_version,
            'confirm_submission' => 1,
        ]);
        $firstEvaluation = $firstEvaluation->fresh();
        app(ReopenEvaluation::class)->execute(
            $firstEvaluation,
            $admin,
            $firstEvaluation->lock_version,
            'MOTIVO_SECRETO_REAPERTURA que nunca debe llegar al archivo exportado.',
        );

        $second = $this->assignmentFor($judges->get(1));
        app(OpenEvaluationDraft::class)->execute($second, $judges->get(1));

        $third = $this->assignmentFor($judges->get(2));
        $thirdEvaluation = $this->complete($judges->get(2), $third, '0', '@Comentario literal con Unicode áéíóú');
        app(SubmitEvaluation::class)->execute($third, $judges->get(2), [
            'lock_version' => $thirdEvaluation->lock_version,
            'confirm_submission' => 1,
        ]);

        $v2Rubric = RubricVersion::query()->where('version', 2)->sole();
        $v2Judge = $substitutes->first();
        $v2Assignment = new JudgeAssignment;
        $v2Assignment->forceFill([
            'competition_id' => $submission->competition_id,
            'submission_version_id' => $version->id,
            'judge_profile_id' => $v2Judge->judgeProfile->id,
            'rubric_version_id' => $v2Rubric->id,
            'type' => JudgeAssignmentType::Initial,
            'status' => JudgeAssignmentStatus::Active,
            'current_slot' => 1,
            'due_at' => CarbonImmutable::parse(config('flowerflow.evaluation_close_at'), config('flowerflow.timezone'))->utc(),
            'assigned_by_user_id' => $admin->id,
            'assignment_reason' => 'Asignación sintética v2 para exportación confidencial.',
            'assigned_at' => now('UTC'),
        ])->save();
        $v2Evaluation = $this->complete($v2Judge, $v2Assignment, '10', '+Comentario v2 literal');
        app(SubmitEvaluation::class)->execute($v2Assignment, $v2Judge, [
            'lock_version' => $v2Evaluation->lock_version,
            'confirm_submission' => 1,
        ]);

        $before = [Evaluation::query()->count(), DB::table('evaluation_revisions')->count(), DB::table('evaluation_scores')->count()];
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.evaluations.exports.create'))
            ->assertOk()
            ->assertSee('Exportación confidencial')
            ->assertSee('todas las revisiones');
        $this->assertSame($before, [Evaluation::query()->count(), DB::table('evaluation_revisions')->count(), DB::table('evaluation_scores')->count()]);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.evaluations.exports.store'))
            ->assertRedirect(route('panel.evaluations.index'));

        $export = EvaluationExport::query()->sole();
        $this->assertSame(EvaluationExportStatus::Completed, $export->status);
        $this->assertSame('all_revisions_v1', $export->scope_version);
        $this->assertSame(4, $export->evaluation_count);
        $this->assertSame(5, $export->revision_count);
        $this->assertSame(24, $export->criterion_count);
        $this->assertSame(1, $export->reopening_count);
        $this->assertMatchesRegularExpression('/^flower-flow-evaluaciones-\d{8}-\d{6}\.xlsx$/', $export->file_name);
        Storage::disk('exports')->assertExists($export->path);

        $path = Storage::disk('exports')->path($export->path);
        $workbook = $this->readWorkbook($path);
        $this->assertSame(['Evaluaciones', 'Criterios', 'Reaperturas'], array_keys($workbook));
        $this->assertCount(6, $workbook['Evaluaciones']);
        $this->assertCount(25, $workbook['Criterios']);
        $this->assertCount(2, $workbook['Reaperturas']);

        $allCells = collect($workbook)->flatten(2);
        $allValues = $allCells->map(fn (Cell $cell): string => (string) $cell->getValue());
        $this->assertTrue($allValues->contains('=Proyecto inmutable exportable'));
        $this->assertTrue($allValues->contains('=Persona Sintética'));
        $this->assertTrue($allValues->contains('=COMENTARIO literal de criterio'));
        $this->assertTrue($allValues->contains('0.0000'));
        $this->assertTrue($allValues->contains('100.0000') || $allValues->contains('80.0000'));
        $this->assertTrue($allValues->contains($v2Rubric->title));
        $this->assertTrue($allValues->contains('2'));
        $this->assertFalse($allValues->contains('Título vivo que no debe aparecer'));
        $this->assertFalse($allValues->contains('participante-export@example.test'));
        $this->assertFalse($allValues->contains('+526620000000'));
        $this->assertFalse($allValues->contains('MOTIVO_SECRETO_REAPERTURA que nunca debe llegar al archivo exportado.'));
        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml', 'xl/worksheets/sheet3.xml'] as $entry) {
            $xml = $this->xlsxEntry($path, $entry);
            $this->assertStringNotContainsString('<f>', $xml);
            $this->assertStringContainsString('<autoFilter', $xml);
        }
        $independentWorkbook = IOFactory::load($path);
        $this->assertSame(['Evaluaciones', 'Criterios', 'Reaperturas'], $independentWorkbook->getSheetNames());
        foreach ($independentWorkbook->getWorksheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $this->assertNotSame(PhpSpreadsheetDataType::TYPE_FORMULA, $cell->getDataType());
                }
            }
        }
        $independentWorkbook->disconnectWorksheets();
        $audit = AuditLog::query()->whereIn('action', ['evaluation_export.requested', 'evaluation_export.completed'])->get();
        $this->assertCount(2, $audit);
        $auditJson = $audit->pluck('metadata')->toJson(JSON_UNESCAPED_UNICODE);
        foreach (['Proyecto inmutable', 'Persona Sintética', 'COMENTARIO', 'MOTIVO_SECRETO', '80.0000'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $auditJson);
        }
    }

    public function test_export_routes_require_flag_exact_admin_permissions_recent_password_and_owner(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)->get(route('panel.evaluations.exports.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('panel.evaluations.exports.create'))
            ->assertRedirect(route('password.confirm'));

        Role::findByName('admin')->revokePermissionTo('export evaluations');
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.evaluations.exports.create'))->assertForbidden();
        Role::findByName('admin')->givePermissionTo('export evaluations');

        $export = new EvaluationExport;
        $export->forceFill([
            'requested_by_user_id' => $admin->id,
            'status' => EvaluationExportStatus::Completed,
            'scope_version' => 'all_revisions_v1',
            'disk' => 'exports',
            'path' => 'evaluation-exports/temporary/file.xlsx',
            'file_name' => 'file.xlsx',
            'completed_at' => now('UTC'),
            'expires_at' => now('UTC')->addHour(),
        ])->save();
        $export->forceFill(['path' => "evaluation-exports/{$export->public_id}/file.xlsx"])->save();
        Storage::disk('exports')->put($export->path, 'xlsx');

        $this->actingAs($otherAdmin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.evaluations.exports.download', $export))->assertForbidden();
        $download = $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.evaluations.exports.download', $export))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringContainsString('private', (string) $download->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $download->headers->get('cache-control'));

        config(['flowerflow.flags.evaluation_export' => false]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('panel.evaluations.exports.create'))->assertNotFound();
        $this->assertInstanceOf(ShouldBeEncrypted::class, new GenerateEvaluationExport(123));
        $this->assertInstanceOf(ShouldQueueAfterCommit::class, new GenerateEvaluationExport(123));
    }

    public function test_invalid_snapshot_fails_closed_and_expired_file_is_purged_while_diagnostic_stays_read_only(): void
    {
        [$admin, $judges, , $submission, $version] = $this->createEvaluationScenario();
        $this->makeSnapshotExportable($version->id, $submission->public_id, (string) $submission->folio, false);
        $assignment = $this->assignmentFor($judges->first());
        app(OpenEvaluationDraft::class)->execute($assignment, $judges->first());
        $profilelessExport = new EvaluationExport;
        $profilelessExport->forceFill([
            'requested_by_user_id' => $admin->id,
            'status' => EvaluationExportStatus::Queued,
            'scope_version' => 'all_revisions_v1',
            'disk' => 'exports',
        ])->save();
        (new GenerateEvaluationExport($profilelessExport->id))->handle(
            app(EvaluationWorkbookWriter::class),
            app(AuditLogger::class),
        );
        $profilelessExport->refresh();
        $profilelessPath = $profilelessExport->path;
        $this->assertSame(EvaluationExportStatus::Completed, $profilelessExport->status);
        $profilelessWorkbook = $this->readWorkbook(Storage::disk('exports')->path($profilelessPath));
        $this->assertSame('', $profilelessWorkbook['Evaluaciones'][1][3]->getValue());
        $this->assertSame('No disponible en versión enviada', $profilelessWorkbook['Evaluaciones'][1][4]->getValue());

        DB::table('submission_versions')->where('id', $version->id)->update([
            'snapshot' => json_encode(['schema_version' => 1], JSON_THROW_ON_ERROR),
        ]);
        $invalidExport = new EvaluationExport;
        $invalidExport->forceFill([
            'requested_by_user_id' => $admin->id,
            'status' => EvaluationExportStatus::Queued,
            'scope_version' => 'all_revisions_v1',
            'disk' => 'exports',
        ])->save();
        $job = new GenerateEvaluationExport($invalidExport->id);

        try {
            $job->handle(app(EvaluationWorkbookWriter::class), app(AuditLogger::class));
            $this->fail('An invalid snapshot must abort the whole workbook.');
        } catch (EvaluationExportSourceInvalid $exception) {
            $job->failed($exception);
        }
        $this->assertSame(EvaluationExportStatus::Failed, $invalidExport->fresh()->status);
        $this->assertSame('EvaluationExportSourceInvalid', $invalidExport->fresh()->failure_code);
        $this->assertNull($invalidExport->fresh()->path);
        Storage::disk('exports')->assertExists($profilelessPath);

        $profilelessExport->forceFill([
            'status' => EvaluationExportStatus::Completed,
            'completed_at' => now('UTC')->subDays(2),
            'expires_at' => now('UTC')->subDay(),
            'failed_at' => null,
            'failure_code' => null,
        ])->save();
        $before = $profilelessExport->fresh()->getRawOriginal();
        $this->assertSame(0, Artisan::call('flowerflow:exports-diagnose', ['--json' => true]));
        $this->assertStringContainsString('"evaluation_exports_table": true', Artisan::output());
        $this->assertSame($before, $profilelessExport->fresh()->getRawOriginal());

        $this->artisan('flowerflow:exports-purge')->assertSuccessful();
        $this->assertSame(EvaluationExportStatus::Expired, $profilelessExport->fresh()->status);
        $this->assertNull($profilelessExport->fresh()->path);
        Storage::disk('exports')->assertMissing($profilelessPath);
        $this->assertDatabaseHas('audit_logs', ['action' => 'evaluation_export.expired']);
    }

    private function assignmentFor(User $judge): JudgeAssignment
    {
        return JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
    }

    private function complete(User $judge, JudgeAssignment $assignment, string $score, string $criterionComment): Evaluation
    {
        $evaluation = app(OpenEvaluationDraft::class)->execute($assignment, $judge);
        $criteria = $assignment->rubricVersion->criteria()->orderBy('sort_order')->get();

        return app(SaveEvaluationDraft::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'general_comment' => '=Comentario general sintético '.str_repeat('x', 110),
            'criteria' => $criteria->map(fn ($criterion): array => [
                'code' => $criterion->code,
                'score' => $score,
                'comment' => $criterionComment,
            ])->all(),
        ]);
    }

    private function makeSnapshotExportable(int $versionId, string $publicId, string $folio, bool $withProfile = true): void
    {
        DB::table('submission_versions')->where('id', $versionId)->update([
            'snapshot' => json_encode([
                'schema_version' => 1,
                'submission' => [
                    'public_id' => $publicId,
                    'folio' => $folio,
                    'title' => '=Proyecto inmutable exportable',
                ],
                'category' => ['slug' => 'sintetica', 'name' => 'Categoría sintética'],
                'participant' => [
                    'email' => 'participante-export@example.test',
                    'profile' => $withProfile ? [
                        'first_names' => '=Persona',
                        'last_names' => 'Sintética',
                        'mobile_e164' => '+526620000000',
                        'neighborhood' => 'Domicilio que no se exporta',
                    ] : null,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    /** @return array<string, array<int, array<int, Cell>>> */
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
