<?php

namespace Tests\Feature;

use App\Actions\Assignments\ActivateSubmissionCoverage;
use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Actions\Rubrics\ActivateRubricVersion;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeConflictType;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\BlindReviewPackageRejected;
use App\Models\AuditLog;
use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Services\BlindReviewPackageBuilder;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BlindReviewPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
        ]);
        Storage::fake('local');
        $this->seedFlowerFlow();
    }

    public function test_m5_provisioning_is_additive_idempotent_and_admin_only(): void
    {
        $participant = $this->participant(['email' => 'preserved-m5@example.test']);
        $this->seed(FlowerFlowSeeder::class);
        $this->seed(FlowerFlowSeeder::class);

        $this->assertDatabaseCount('blind_review_packages', 0);
        $this->assertDatabaseCount('blind_review_package_files', 0);
        $this->assertTrue($participant->fresh()->hasExactRoles(['participant']));
        foreach (['view blind review packages', 'manage blind review packages'] as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
            foreach (['participant', 'reviewer', 'judge'] as $role) {
                $this->assertFalse(Role::findByName($role)->hasPermissionTo($permission));
            }
        }
    }

    public function test_builder_and_activation_are_deterministic_allowlisted_and_immutable(): void
    {
        [$admin, $primaries, , $submission, $version] = $this->coveredSubmission();
        $first = app(BlindReviewPackageBuilder::class)->build($version);
        $second = app(BlindReviewPackageBuilder::class)->build($version);
        $this->assertSame($first, $second);
        $this->assertSame(['category', 'submission', 'external_links'], array_keys($first['payload']));
        $this->assertSame(['slug', 'name'], array_keys($first['payload']['category']));
        $this->assertSame(['participation_type', 'title', 'summary', 'description_html', 'description_text'], array_keys($first['payload']['submission']));
        $this->assertStringNotContainsString('<script', $first['payload']['submission']['description_html']);
        $this->assertStringNotContainsString('onerror', $first['payload']['submission']['description_html']);

        $this->actingAs($admin)->post(route('panel.blind-review-packages.generate', $submission), [
            'reason' => 'Generación administrativa del paquete sintético de evaluación.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $package = BlindReviewPackage::query()->with('files')->sole();
        $this->assertSame(BlindReviewPackageStatus::Draft, $package->status);
        $this->assertSame($first['payload_sha256'], $package->payload_sha256);
        $this->assertCount(2, $package->files);
        $this->assertSame(['Documento 01.pdf', 'Imagen de apoyo 01.png'], $package->files->pluck('neutral_label')->all());
        $serialized = $package->toJson();
        foreach ($this->hiddenCanaries() as $canary) {
            $this->assertStringNotContainsString($canary, $serialized);
        }

        $this->actingAs($admin)->post(route('panel.blind-review-packages.activate', $submission), [
            'reason' => 'Activación administrativa del paquete ciego ya validado.',
            'current_password' => 'incorrecta',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame(BlindReviewPackageStatus::Draft, $package->fresh()->status);

        $this->actingAs($admin)->post(route('panel.blind-review-packages.activate', $submission), [
            'reason' => 'Activación administrativa del paquete ciego ya validado.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('panel.blind-review-packages.activate', $submission), [
            'reason' => 'Activación administrativa del paquete ciego ya validado.',
            'current_password' => 'AdminPass1!',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('blind_review_packages', 1);
        $this->assertDatabaseCount('blind_review_package_files', 2);
        $this->assertSame(BlindReviewPackageStatus::Active, $package->fresh()->status);

        try {
            $package->fresh()->forceFill(['payload_sha256' => str_repeat('a', 64)])->save();
            $this->fail('An active package must be immutable.');
        } catch (LogicException) {
            $this->assertSame($first['payload_sha256'], $package->fresh()->payload_sha256);
        }
        try {
            BlindReviewPackage::query()->create(['status' => BlindReviewPackageStatus::Draft]);
            $this->fail('Mass assignment must remain blocked.');
        } catch (MassAssignmentException) {
            $this->assertDatabaseCount('blind_review_packages', 1);
        }

        $this->assertDatabaseHas('audit_logs', ['action' => 'blind_review_package.activated', 'actor_user_id' => $admin->id]);
        $this->assertSame(1, AuditLog::query()->where('action', 'blind_review_package.activated')->count());
        $this->assertTrue($primaries->every(fn (User $judge): bool => $judge->hasExactRoles(['judge'])));
    }

    public function test_structural_blindness_access_matrix_and_neutral_download_are_enforced(): void
    {
        [$admin, $primaries, , $submission] = $this->coveredSubmission();
        app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación sintética para comprobar acceso estructural ciego.');
        $package = app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación sintética para comprobar acceso estructural ciego.');
        $assignment = JudgeAssignment::query()->where('judge_profile_id', $primaries->first()->judgeProfile->id)->firstOrFail();
        $packageFile = $package->files()->firstOrFail();

        $html = $this->actingAs($primaries->first())->get(route('judge.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('Anonimización estructural')
            ->assertSee('IDENTIDAD-AUTOEXPUESTA-ACEPTADA')
            ->assertSee('Documento 01.pdf')
            ->assertDontSee('Pertinencia')
            ->assertDontSee('puntaje')
            ->getContent();
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        foreach ($this->hiddenCanaries() as $canary) {
            $this->assertStringNotContainsString($canary, $html);
        }

        $download = $this->actingAs($primaries->first())->get(route('judge.assignments.packages.files.download', [$assignment, $packageFile]));
        $download->assertOk()->assertDownload('Documento 01.pdf');
        $this->assertSame('nosniff', $download->headers->get('X-Content-Type-Options'));
        $this->assertStringNotContainsString('identidad-original', (string) $download->headers->get('Content-Disposition'));

        $otherJudge = $primaries->get(1);
        $this->actingAs($otherJudge)->get(route('judge.assignments.show', $assignment))->assertForbidden();
        $this->actingAs($otherJudge)->get(route('judge.assignments.packages.files.download', [$assignment, $packageFile]))->assertForbidden();
        $this->app['auth']->logout();
        $this->get(route('judge.assignments.show', $assignment))->assertRedirect();
        foreach ([$this->participant(), $this->reviewer(), $admin, User::factory()->create()] as $forbidden) {
            $this->actingAs($forbidden)->get(route('judge.assignments.show', $assignment))->assertForbidden();
        }

        $multiRole = User::factory()->create();
        $multiRole->assignRole(['judge', 'participant']);
        $this->actingAs($multiRole)->get(route('judge.assignments.show', $assignment))->assertForbidden();
        $this->actingAs($primaries->first())->get('/juez/asignaciones/'.$assignment->public_id.'/anexos/01J00000000000000000000000')->assertNotFound();
        $this->actingAs($primaries->first())->get(route('judge.assignments.packages.files.download', [$assignment, $package->files()->latest('id')->firstOrFail()]))->assertOk();

        $primaries->first()->judgeProfile->forceFill(['status' => JudgeProfileStatus::Suspended->value])->save();
        $this->actingAs($primaries->first())->get(route('judge.assignments.show', $assignment))->assertRedirect(route('judge.status'));
    }

    public function test_conflict_removes_access_and_manual_replacement_uses_the_same_package(): void
    {
        [$admin, $primaries, $substitutes, $submission] = $this->coveredSubmission();
        $package = app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación sintética para probar reasignación del mismo paquete.');
        $package = app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación sintética para probar reasignación del mismo paquete.');
        $original = JudgeAssignment::query()->where('judge_profile_id', $primaries->first()->judgeProfile->id)->firstOrFail();
        $file = $package->files()->firstOrFail();

        $conflict = app(DeclareJudgeConflict::class)->execute(
            $original,
            $primaries->first(),
            JudgeConflictType::ParticipationInSubmission,
            null,
        );
        $this->actingAs($primaries->first())->get(route('judge.assignments.show', $original))
            ->assertOk()->assertDontSee('IDENTIDAD-AUTOEXPUESTA-ACEPTADA');
        $this->actingAs($primaries->first())->get(route('judge.assignments.packages.files.download', [$original, $file]))->assertForbidden();

        $replacement = app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $substitutes->last()->judgeProfile->public_id,
            'Reasignación manual sintética hacia el segundo sustituto.',
        );
        $this->assertSame($original->submission_version_id, $replacement->submission_version_id);
        $this->assertSame(BlindReviewPackageStatus::Active, $package->fresh()->status);
        $this->assertDatabaseCount('blind_review_packages', 1);
        $this->actingAs($substitutes->last())->get(route('judge.assignments.show', $replacement))
            ->assertOk()->assertSee('IDENTIDAD-AUTOEXPUESTA-ACEPTADA');
        $this->actingAs($substitutes->last())->get(route('judge.assignments.packages.files.download', [$replacement, $file]))
            ->assertOk()->assertDownload('Documento 01.pdf');
        $this->actingAs($primaries->first())->get(route('judge.assignments.show', $original))->assertOk()->assertDontSee('Documento 01.pdf');
    }

    public function test_unknown_schema_inventory_and_binary_drift_fail_closed_without_partial_rows(): void
    {
        [$admin, , , $submission, $version] = $this->coveredSubmission();
        $snapshot = $version->snapshot;
        $snapshot['schema_version'] = 99;
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode($snapshot)]);
        $this->expectException(BlindReviewPackageRejected::class);
        app(BlindReviewPackageBuilder::class)->build($version->fresh());
    }

    public function test_missing_duplicate_and_crossed_snapshot_files_fail_closed(): void
    {
        [, , , , $version] = $this->coveredSubmission();
        $baseline = $version->snapshot;

        $missing = $baseline;
        unset($missing['files'][0]['sha256']);
        $this->assertBuilderRejected($version, $missing, 'invalid_snapshot_field');

        $duplicate = $baseline;
        $duplicate['files'][1] = $duplicate['files'][0];
        $this->assertBuilderRejected($version, $duplicate, 'duplicate_snapshot_file');

        $crossed = $baseline;
        $crossed['files'][0]['public_id'] = '01J00000000000000000000000';
        $this->assertBuilderRejected($version, $crossed, 'crossed_snapshot_file');

        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode($baseline)]);
        $this->assertSame(2, count(app(BlindReviewPackageBuilder::class)->build($version->fresh())['files']));
        $this->assertDatabaseCount('blind_review_packages', 0);
    }

    public function test_extra_file_and_download_drift_fail_closed_with_redacted_audit(): void
    {
        [$admin, $primaries, , $submission] = $this->coveredSubmission();
        $version = $submission->versions()->firstOrFail();
        $extraPath = 'submissions/'.$submission->public_id.'/extra.pdf';
        Storage::disk('local')->put($extraPath, $this->pdfBytes('extra'));
        SubmissionFile::query()->create([
            'submission_id' => $submission->id,
            'actor_user_id' => $submission->user_id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => $extraPath,
            'original_name' => 'EXTRA-HIDDEN.pdf',
            'stored_name' => 'extra.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => strlen($this->pdfBytes('extra')),
            'sha256' => hash('sha256', $this->pdfBytes('extra')),
        ]);
        $this->actingAs($admin)->post(route('panel.blind-review-packages.generate', $submission), [
            'reason' => 'El inventario extra debe provocar un rechazo cerrado.',
            'current_password' => 'AdminPass1!',
        ])->assertSessionHasErrors('package');
        $this->assertDatabaseCount('blind_review_packages', 0);
        SubmissionFile::query()->latest('id')->firstOrFail()->delete();
        Storage::disk('local')->delete($extraPath);

        $package = app(GenerateBlindReviewPackageDraft::class)->execute($submission, $admin, 'Generación válida posterior al retiro del archivo extra.');
        $package = app(ActivateBlindReviewPackage::class)->execute($submission, $admin, 'Activación válida posterior al retiro del archivo extra.');
        $file = $package->files()->firstOrFail();
        Storage::disk('local')->put($file->submissionFile->path, $this->pdfBytes('drift'));
        $assignment = JudgeAssignment::query()->where('judge_profile_id', $primaries->first()->judgeProfile->id)->firstOrFail();
        $this->actingAs($primaries->first())->get(route('judge.assignments.packages.files.download', [$assignment, $file]))
            ->assertStatus(409)->assertDontSee($file->submissionFile->path);

        $auditJson = AuditLog::query()->where('action', 'like', 'blind_review_package.%')->get()->toJson();
        foreach (['IDENTIDAD-ESTRUCTURADA-OCULTA', 'identidad-original', $file->submissionFile->path, 'AdminPass1'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $auditJson);
        }
        $this->assertSame($version->id, $package->submission_version_id);
    }

    /** @return array{User, Collection<int, User>, Collection<int, User>, Submission, SubmissionVersion} */
    private function coveredSubmission(): array
    {
        $admin = $this->admin([
            'email' => 'admin-m5-'.fake()->unique()->numerify('######').'@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
        $primaries = collect(range(1, 4))->map(fn (int $number): User => $this->activeJudge($admin, JudgeAssignmentRole::Primary, $number));
        $substitutes = collect(range(5, 6))->map(fn (int $number): User => $this->activeJudge($admin, JudgeAssignmentRole::Substitute, $number));
        $rubric = RubricVersion::query()->where('version', 1)->firstOrFail();
        app(ActivateRubricVersion::class)->execute($rubric, $admin, 'Activación sintética de rúbrica para M5.');

        [, $submission, $review] = $this->submittedReview(true);
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'IDENTIDAD-ADMISIBILIDAD-OCULTA',
            'internal_notes' => 'IDENTIDAD-NOTA-INTERNA-OCULTA',
        ]);
        $submission->user->profile->update([
            'first_names' => 'IDENTIDAD-ESTRUCTURADA-OCULTA',
            'last_names' => 'APELLIDO-OCULTO',
            'mobile_e164' => '+526620000000',
            'neighborhood' => 'COLONIA-OCULTA',
        ]);

        $pdf = $this->pdfBytes('principal');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        $files = collect([
            $this->storeFile($submission, 'document', 'pdf', 'application/pdf', 'identidad-original-participante.pdf', $pdf),
            $this->storeFile($submission, 'editor_image', 'png', 'image/png', 'rostro-identificable-original.png', $png),
        ]);
        $version = $submission->versions()->firstOrFail();
        $snapshot = [
            'schema_version' => 1,
            'captured_at_utc' => 'IDENTIDAD-FECHA-CAPTURA-OCULTA',
            'submission' => [
                'public_id' => 'IDENTIDAD-SUBMISSION-ID-OCULTA',
                'folio' => 'IDENTIDAD-FOLIO-OCULTA',
                'participation_type' => 'team',
                'title' => 'IDENTIDAD-AUTOEXPUESTA-ACEPTADA',
                'summary' => 'Resumen sustantivo con autor autoidentificado y riesgo aceptado.',
                'description_delta' => ['ops' => [['insert' => 'IDENTIDAD-AUTOEXPUESTA-ACEPTADA']]],
                'description_html' => '<p>Descripción <strong>IDENTIDAD-AUTOEXPUESTA-ACEPTADA</strong><script>alert(1)</script><img src="https://participant.invalid/private" onerror="alert(2)"></p>',
                'description_text' => 'Descripción IDENTIDAD-AUTOEXPUESTA-ACEPTADA',
                'submitted_at' => 'IDENTIDAD-FECHA-ENVIO-OCULTA',
            ],
            'competition' => ['public_id' => 'IDENTIDAD-COMPETENCIA-OCULTA', 'slug' => 'oculta'],
            'category' => [
                'public_id' => 'IDENTIDAD-CATEGORIA-ID-OCULTA',
                'slug' => $submission->category->slug,
                'name' => $submission->category->name,
            ],
            'participant' => [
                'public_id' => 'IDENTIDAD-PARTICIPANTE-ID-OCULTA',
                'email' => 'identidad-estructurada-oculta@example.test',
                'profile' => ['first_names' => 'IDENTIDAD-ESTRUCTURADA-OCULTA'],
            ],
            'team' => ['name' => 'IDENTIDAD-EQUIPO-OCULTA', 'members' => [['email' => 'integrante-oculto@example.test']]],
            'files' => $files->map(fn (SubmissionFile $file): array => [
                'public_id' => $file->public_id,
                'kind' => $file->kind,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'extension' => $file->extension,
                'size_bytes' => (int) $file->size_bytes,
                'sha256' => $file->sha256,
            ])->all(),
            'external_links' => [[
                'kind' => 'youtube',
                'url' => 'https://www.youtube.com/watch?v=IDENTIDAD-AUTOEXPUESTA-ACEPTADA',
                'normalized_host' => 'www.youtube.com',
            ]],
        ];
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode($snapshot)]);
        $version = $version->fresh();
        app(ActivateSubmissionCoverage::class)->execute($submission, $admin, 'Cobertura sintética completa para construir el paquete M5.');

        return [$admin, $primaries, $substitutes, $submission->fresh('category'), $version];
    }

    private function activeJudge(User $creator, JudgeAssignmentRole $role, int $number): User
    {
        $user = User::factory()->create(['email' => "judge-m5-{$number}-".fake()->unique()->numerify('######').'@example.test']);
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => $role->value,
            'status' => JudgeProfileStatus::Active->value,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $user->setRelation('judgeProfile', $profile);
    }

    private function storeFile(Submission $submission, string $kind, string $extension, string $mime, string $originalName, string $contents): SubmissionFile
    {
        $storedName = strtolower($kind).'-'.fake()->unique()->numerify('######').'.'.$extension;
        $path = 'submissions/'.$submission->public_id.'/'.$storedName;
        Storage::disk('local')->put($path, $contents);

        return SubmissionFile::query()->create([
            'submission_id' => $submission->id,
            'actor_user_id' => $submission->user_id,
            'kind' => $kind,
            'format_category' => $kind === 'editor_image' ? 'image' : 'pdf',
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ]);
    }

    private function pdfBytes(string $marker): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%{$marker}\n%%EOF";
    }

    private function assertBuilderRejected(SubmissionVersion $version, array $snapshot, string $reasonCode): void
    {
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode($snapshot)]);

        try {
            app(BlindReviewPackageBuilder::class)->build($version->fresh());
            $this->fail("Builder should reject {$reasonCode}.");
        } catch (BlindReviewPackageRejected $exception) {
            $this->assertSame($reasonCode, $exception->reasonCode);
        }
    }

    /** @return list<string> */
    private function hiddenCanaries(): array
    {
        return [
            'IDENTIDAD-ESTRUCTURADA-OCULTA',
            'IDENTIDAD-PARTICIPANTE-ID-OCULTA',
            'IDENTIDAD-SUBMISSION-ID-OCULTA',
            'IDENTIDAD-FOLIO-OCULTA',
            'IDENTIDAD-FECHA-CAPTURA-OCULTA',
            'IDENTIDAD-FECHA-ENVIO-OCULTA',
            'IDENTIDAD-EQUIPO-OCULTA',
            'IDENTIDAD-NOTA-INTERNA-OCULTA',
            'identidad-original-participante.pdf',
            'rostro-identificable-original.png',
        ];
    }
}
