<?php

namespace Tests\Feature;

use App\Enums\JudgeAssignmentStatus;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\EvaluationRevision;
use App\Models\JudgeAssignment;
use App\Services\CanonicalJson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;

class JudgeEvaluationWizardTest extends TestCase
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
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_wizard_is_read_only_until_explicit_start_and_separates_project_from_evaluation(): void
    {
        [, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge->judgeProfile->id);

        $before = $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Paso 1 de 4')
            ->assertSee('Iniciar evaluación')
            ->assertDontSee('Proyecto sintético para evaluación M6')
            ->getContent();
        $this->assertSame(2, substr_count($before, 'Declarar conflicto'));
        $this->assertDatabaseCount('evaluations', 0);
        $this->actingAs($judge)->get(route('judge.assignments.project.show', $assignment))->assertStatus(409);
        $this->actingAs($judge)->get(route('judge.assignments.evaluation.show', $assignment))
            ->assertRedirect(route('judge.assignments.show', $assignment));
        $this->assertDatabaseCount('evaluations', 0);

        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))
            ->assertRedirect(route('judge.assignments.project.show', $assignment));
        $this->assertDatabaseCount('evaluations', 1);

        $after = $this->actingAs($judge)->get(route('judge.assignments.show', $assignment))
            ->assertOk()->assertSee('Continuar evaluación')->getContent();
        $this->assertSame(1, substr_count($after, 'Declarar conflicto'));

        $this->actingAs($judge)->get(route('judge.assignments.project.show', $assignment))
            ->assertOk()
            ->assertSee('Paso 2 de 4')
            ->assertSee('Proyecto sintético para evaluación M6')
            ->assertSee('Exportar PDF')
            ->assertSee('Exportar Excel')
            ->assertDontSee('Puntaje');
        $this->actingAs($judge)->get(route('judge.assignments.evaluation.show', $assignment))
            ->assertOk()
            ->assertSee('Paso 3 de 4')
            ->assertSee('data-evaluation-autosave', false)
            ->assertSee('data-autosave-interval="30000"', false)
            ->assertSee('Guardar borrador')
            ->assertDontSee('Declarar conflicto');

        $otherJudge = $judges->get(1);
        $this->actingAs($otherJudge)->get(route('judge.assignments.project.show', $assignment))->assertForbidden();
        $this->actingAs($otherJudge)->get(route('judge.assignments.evaluation.show', $assignment))->assertForbidden();
    }

    public function test_autosave_returns_server_state_and_stale_tab_gets_json_409_without_overwrite(): void
    {
        [, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge->judgeProfile->id);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();

        $payload = [
            'lock_version' => 0,
            'general_comment' => 'Avance guardado automáticamente.',
            'criteria' => [[
                'code' => 'pertinence',
                'score' => '5.0000',
                'comment' => 'Comentario parcial sintético.',
            ]],
            'intent' => 'autosave',
        ];
        $this->actingAs($judge)->patchJson(route('judge.assignments.evaluation.update', $assignment), $payload)
            ->assertOk()
            ->assertJsonPath('lock_version', 1)
            ->assertJsonPath('captured_criteria', 1)
            ->assertJsonPath('total_criteria', 5)
            ->assertJsonPath('is_complete', false)
            ->assertJsonPath('total_display', null)
            ->assertJsonStructure(['saved_at']);

        $this->actingAs($judge)->patchJson(route('judge.assignments.evaluation.update', $assignment), [
            ...$payload,
            'general_comment' => 'Esta pestaña obsoleta no debe sobrescribir.',
        ])->assertStatus(409)
            ->assertJsonPath('code', 'stale_lock_version')
            ->assertJsonStructure(['message', 'reload_url']);

        $this->assertSame(1, Evaluation::query()->sole()->lock_version);
        $this->assertSame('Avance guardado automáticamente.', EvaluationRevision::query()->sole()->general_comment);
        $this->assertDatabaseHas('evaluation_scores', [
            'score' => '5.0000',
            'comment' => 'Comentario parcial sintético.',
        ]);

        $completeCriteria = collect(['pertinence', 'clarity', 'feasibility', 'impact', 'coherence'])
            ->map(fn (string $code): array => ['code' => $code, 'score' => '10', 'comment' => null])
            ->all();
        $this->actingAs($judge)->patchJson(route('judge.assignments.evaluation.update', $assignment), [
            'lock_version' => 1,
            'general_comment' => str_repeat('Comentario completo. ', 7),
            'criteria' => $completeCriteria,
            'intent' => 'autosave',
        ])->assertOk()
            ->assertJsonPath('lock_version', 2)
            ->assertJsonPath('captured_criteria', 5)
            ->assertJsonPath('is_complete', true)
            ->assertJsonPath('total_display', '100.00');
    }

    public function test_pdf_and_xlsx_exports_are_private_branded_redacted_and_text_only(): void
    {
        [, $judges, , , , $package] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge->judgeProfile->id);
        $payload = $package->payload;
        $payload['submission']['title'] = '=HYPERLINK("https://evil.example.test")';
        $payload['submission']['summary'] = '+SUM(1,1)';
        $payload['submission']['description_text'] = '-1+2';
        $payload['submission']['description_html'] = '<p>Descripción segura y sintética.</p>';
        $payload['external_links'] = [[
            'kind' => 'public_folder',
            'url' => 'https://files.example.test/project',
            'normalized_host' => 'files.example.test',
        ]];
        DB::table('blind_review_packages')->where('id', $package->id)->update([
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'payload_sha256' => app(CanonicalJson::class)->hash($payload),
        ]);
        $this->actingAs($judge)->post(route('judge.assignments.evaluation.store', $assignment))->assertRedirect();

        $pdfResponse = $this->actingAs($judge)->get(route('judge.assignments.project.exports.pdf', $assignment))
            ->assertOk()
            ->assertDownload('flower-flow-proyecto-'.$assignment->public_id.'.pdf')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $pdfPath = $pdfResponse->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('%PDF-', (string) File::get($pdfPath));

        $xlsxResponse = $this->actingAs($judge)->get(route('judge.assignments.project.exports.xlsx', $assignment))
            ->assertOk()
            ->assertDownload('flower-flow-proyecto-'.$assignment->public_id.'.xlsx')
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $xlsxPath = $xlsxResponse->baseResponse->getFile()->getPathname();
        $book = IOFactory::load($xlsxPath);
        $this->assertSame(['Proyecto', 'Enlaces', 'Anexos'], $book->getSheetNames());
        $this->assertSame('A5:B5', $book->getSheetByName('Proyecto')->getAutoFilter()->getRange());
        $this->assertSame('A5:C5', $book->getSheetByName('Enlaces')->getAutoFilter()->getRange());
        $this->assertSame('A5:E5', $book->getSheetByName('Anexos')->getAutoFilter()->getRange());
        foreach ($book->getAllSheets() as $sheet) {
            $this->assertCount(2, $sheet->getDrawingCollection());
            foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                $cell = $sheet->getCell($coordinate);
                $this->assertNotSame(DataType::TYPE_FORMULA, $cell->getDataType());
                $this->assertStringNotContainsString('@example.test', (string) $cell->getValue());
            }
        }
        $this->assertSame('=HYPERLINK("https://evil.example.test")', $book->getSheetByName('Proyecto')->getCell('B9')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $book->getSheetByName('Proyecto')->getCell('B9')->getDataType());
        $this->assertSame('https://files.example.test/project', $book->getSheetByName('Enlaces')->getCell('C6')->getValue());
        $book->disconnectWorksheets();

        File::delete([$pdfPath, $xlsxPath]);
        $this->assertSame(2, AuditLog::query()->where('action', 'blind_review_package.project_exported')->count());
        $serializedAudit = AuditLog::query()->where('action', 'blind_review_package.project_exported')->get()->toJson();
        foreach (['HYPERLINK', 'SUM(1,1)', 'files.example.test', '@example.test'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serializedAudit);
        }

        DB::table('blind_review_packages')->where('id', $package->id)->update(['payload_sha256' => str_repeat('0', 64)]);
        $this->actingAs($judge)->get(route('judge.assignments.project.exports.pdf', $assignment))->assertStatus(409);
        $this->assertDatabaseHas('audit_logs', ['action' => 'blind_review_package.project_export_rejected']);
        $rejected = AuditLog::query()->where('action', 'blind_review_package.project_export_rejected')->latest('id')->firstOrFail();
        $this->assertSame('package_integrity_diverged', data_get($rejected->metadata, 'reason_code'));
    }

    private function assignmentFor(int $judgeProfileId): JudgeAssignment
    {
        return JudgeAssignment::query()
            ->where('judge_profile_id', $judgeProfileId)
            ->where('status', JudgeAssignmentStatus::Active)
            ->firstOrFail();
    }
}
