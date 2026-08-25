<?php

namespace Tests\Feature;

use App\Actions\Assignments\CancelJudgeAssignment;
use App\Actions\Assignments\DeclareJudgeConflict;
use App\Actions\Assignments\ResolveJudgeConflict;
use App\Actions\Evaluations\OpenEvaluationDraft;
use App\Actions\Evaluations\ReopenEvaluation;
use App\Actions\Evaluations\SaveEvaluationDraft;
use App\Actions\Evaluations\SubmitEvaluation;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationType;
use App\Enums\JudgeConflictType;
use App\Events\EvaluationReopened;
use App\Events\EvaluationSubmitted;
use App\Events\JudgeConflictDeclared;
use App\Events\JudgeConflictResolved;
use App\Exceptions\EvaluationCloseDigestRejected;
use App\Jobs\DeliverCommunication;
use App\Models\AuditLog;
use App\Models\CommunicationDelivery;
use App\Models\Evaluation;
use App\Models\EvaluationReopening;
use App\Models\JudgeAssignment;
use App\Models\RubricCriterion;
use App\Models\User;
use App\Notifications\EvaluationCloseDigestNotification;
use App\Notifications\JudgeConflictDeclaredNotification;
use App\Notifications\JudgeConflictResolvedNotification;
use App\Services\CommunicationMessageRegistry;
use App\Services\EvaluationCloseDigest;
use App\Services\EvaluationCommunicationDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesEvaluationScenario;
use Tests\TestCase;

class EvaluationCommunicationTest extends TestCase
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
            'flowerflow.flags.communication_ledger' => true,
            'flowerflow.flags.evaluation_notifications' => true,
            'flowerflow.evaluation_notifications.close_digest_enabled' => true,
            'flowerflow.evaluation_notifications.close_digest_catchup_hours' => 24,
            'flowerflow.judge_notifications.assignment_enabled' => false,
            'flowerflow.timezone' => 'America/Hermosillo',
            'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
            'flowerflow.evaluation_reopen_close_at' => '2026-08-27 20:00:00',
        ]);
        $this->seedFlowerFlow();
    }

    public function test_events_are_id_only_after_commit_contracts_and_the_ledger_exposes_five_spanish_types(): void
    {
        foreach ([
            new JudgeConflictDeclared(1, 2, 3),
            new JudgeConflictResolved(1, 2, 3, 4),
            new EvaluationSubmitted(1, 2, 3),
            new EvaluationReopened(1, 2, 3, 4, 5),
        ] as $event) {
            $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
            $this->assertStringNotContainsString('@', serialize($event));
            $this->assertStringNotContainsString('comment', serialize($event));
            $this->assertStringNotContainsString('score', serialize($event));
        }

        $expected = [
            CommunicationType::JudgeConflictDeclared->value => 'Conflicto de evaluación declarado',
            CommunicationType::JudgeConflictResolved->value => 'Conflicto de evaluación resuelto',
            CommunicationType::EvaluationSubmitted->value => 'Evaluación enviada',
            CommunicationType::EvaluationReopened->value => 'Evaluación reabierta',
            CommunicationType::EvaluationCloseDigest->value => 'Resumen de cierre de evaluación',
        ];
        foreach ($expected as $type => $label) {
            $this->assertSame($label, CommunicationType::from($type)->label());
        }
    }

    public function test_domain_events_are_not_published_when_the_surrounding_transaction_rolls_back(): void
    {
        Event::fake([JudgeConflictDeclared::class]);

        DB::beginTransaction();
        JudgeConflictDeclared::dispatch(101, 202, 303);
        Event::assertNotDispatched(JudgeConflictDeclared::class);
        DB::rollBack();
        Event::assertNotDispatched(JudgeConflictDeclared::class);

        DB::transaction(fn () => JudgeConflictDeclared::dispatch(101, 202, 303));
        Event::assertDispatchedTimes(JudgeConflictDeclared::class, 1);
        $this->assertDatabaseCount('communication_deliveries', 0);
    }

    public function test_conflict_notifications_use_only_the_responsible_admin_and_outgoing_judge_without_duplicating_incoming_assignment_mail(): void
    {
        Queue::fake();
        Notification::fake();
        [$admin, $judges, $substitutes] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);

        $conflict = app(DeclareJudgeConflict::class)->execute(
            $assignment,
            $judge,
            JudgeConflictType::Other,
            'Explicación sintética reservada que jamás debe aparecer en el correo.',
        );
        app(EvaluationCommunicationDispatcher::class)->conflictDeclared(
            new JudgeConflictDeclared($conflict->id, $assignment->id, $judge->id),
        );

        $declared = CommunicationDelivery::query()->sole();
        $this->assertSame(CommunicationType::JudgeConflictDeclared, $declared->notification_type);
        $this->assertSame($admin->id, $declared->recipient_user_id);
        DB::table('categories')
            ->where('id', $assignment->submissionVersion->submission->category_id)
            ->update(['name' => '<script>alert("m8")</script>']);
        $prepared = app(CommunicationMessageRegistry::class)->prepare($declared);
        $mail = $prepared['message']->toMail($admin);
        $html = (string) $mail->render();
        $text = view($mail->view['text'], $mail->viewData)->render();
        $this->assertStringContainsString('Revisar conflicto', $html);
        $this->assertStringContainsString($assignment->public_id, $html);
        $this->assertStringContainsString('&lt;script&gt;alert', $html);
        $this->assertStringNotContainsString('<script>alert("m8")</script>', $html);
        $this->assertStringContainsString('FLOWER FLOW', $text);
        $this->assertStringContainsString('FLORECE HERMOSILLO', $text);
        $this->assertStringNotContainsString('Explicación sintética reservada', $html);
        $this->assertStringNotContainsString($judge->name, $html);

        $replacement = app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $substitutes->first()->judgeProfile->public_id,
            'Resolución administrativa sintética que tampoco debe exponerse.',
            false,
        );
        app(EvaluationCommunicationDispatcher::class)->conflictResolved(
            new JudgeConflictResolved($conflict->id, $assignment->id, $replacement->id, $admin->id),
        );

        $this->assertDatabaseCount('communication_deliveries', 2);
        $resolved = CommunicationDelivery::query()->where('notification_type', CommunicationType::JudgeConflictResolved)->sole();
        $this->assertSame($judge->id, $resolved->recipient_user_id);
        $this->assertFalse(CommunicationDelivery::query()
            ->where('recipient_user_id', $replacement->judgeProfile->user_id)
            ->where('notification_type', CommunicationType::JudgeAssignmentCreated)
            ->exists());

        app()->call([new DeliverCommunication($declared->id), 'handle']);
        app()->call([new DeliverCommunication($resolved->id), 'handle']);
        $this->assertSame(CommunicationDeliveryStatus::Cancelled, $declared->fresh()->status);
        $this->assertSame('judge_conflict_declared_invalid', $declared->fresh()->failure_code);
        $this->assertSame(CommunicationDeliveryStatus::Sent, $resolved->fresh()->status);
        Notification::assertNotSentTo($admin, JudgeConflictDeclaredNotification::class);
        Notification::assertSentTo($judge, JudgeConflictResolvedNotification::class);

        $this->actingAs($admin)
            ->get(route('panel.communication-deliveries.index', ['type' => CommunicationType::JudgeConflictResolved->value]))
            ->assertOk()
            ->assertSee('Conflicto de evaluación resuelto')
            ->assertDontSee($judge->email);
    }

    public function test_invalid_responsible_admin_and_disabled_flags_skip_without_legacy_fallback(): void
    {
        Queue::fake();
        Notification::fake();
        [$admin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $admin->forceFill(['email_verified_at' => null])->save();
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $assignment,
            $judge,
            JudgeConflictType::ParticipationInSubmission,
            null,
        );

        app(EvaluationCommunicationDispatcher::class)->conflictDeclared(
            new JudgeConflictDeclared($conflict->id, $assignment->id, $judge->id),
        );
        $this->assertDatabaseCount('communication_deliveries', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assignment.conflict_notification_skipped',
            'auditable_id' => $conflict->id,
        ]);

        $admin->forceFill(['email_verified_at' => now('UTC')])->save();
        config(['flowerflow.flags.communication_ledger' => false]);
        app(EvaluationCommunicationDispatcher::class)->conflictDeclared(
            new JudgeConflictDeclared($conflict->id, $assignment->id, $judge->id),
        );
        $this->assertDatabaseCount('communication_deliveries', 0);
        Queue::assertNothingPushed();
        Notification::assertNothingSent();
    }

    public function test_submission_and_reopening_resolve_two_exact_recipients_preserve_history_and_cancel_stale_reopening_mail(): void
    {
        Queue::fake();
        Notification::fake();
        [$assigningAdmin, $judges] = $this->createEvaluationScenario();
        $judge = $judges->first();
        $assignment = $this->assignmentFor($judge);
        $evaluation = $this->completeEvaluation($judge, $assignment);
        $revisionOne = $evaluation->currentRevision;

        app(EvaluationCommunicationDispatcher::class)->evaluationSubmitted(
            new EvaluationSubmitted($evaluation->id, $revisionOne->id, $judge->id),
        );
        $this->assertDatabaseCount('communication_deliveries', 2);
        $this->assertEqualsCanonicalizing(
            [$judge->id, $assigningAdmin->id],
            CommunicationDelivery::query()->where('notification_type', CommunicationType::EvaluationSubmitted)
                ->pluck('recipient_user_id')->all(),
        );
        foreach (CommunicationDelivery::query()->where('notification_type', CommunicationType::EvaluationSubmitted)->get() as $delivery) {
            $prepared = app(CommunicationMessageRegistry::class)->prepare($delivery);
            $html = (string) $prepared['message']->toMail($prepared['recipient'])->render();
            $this->assertStringContainsString($evaluation->public_id, $html);
            $this->assertStringNotContainsString(str_repeat('c', 120), $html);
            $this->assertStringNotContainsString('80.0000', $html);
            $this->assertStringNotContainsString($assigningAdmin->name, $html);
        }

        $reopeningAdmin = $this->admin();
        $evaluation = app(ReopenEvaluation::class)->execute(
            $evaluation,
            $reopeningAdmin,
            $evaluation->lock_version,
            'Motivo cifrado sintético de reapertura que no debe salir en comunicaciones.',
        );
        $reopening = EvaluationReopening::query()->sole();
        app(EvaluationCommunicationDispatcher::class)->evaluationReopened(new EvaluationReopened(
            $evaluation->id,
            $reopening->id,
            $reopening->source_revision_id,
            $reopening->target_revision_id,
            $reopeningAdmin->id,
        ));
        $reopeningDelivery = CommunicationDelivery::query()->where('notification_type', CommunicationType::EvaluationReopened)->sole();
        $this->assertSame($judge->id, $reopeningDelivery->recipient_user_id);

        $evaluation = app(SubmitEvaluation::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'confirm_submission' => '1',
        ]);
        $revisionTwo = $evaluation->currentRevision;
        app(EvaluationCommunicationDispatcher::class)->evaluationSubmitted(
            new EvaluationSubmitted($evaluation->id, $revisionTwo->id, $judge->id),
        );
        $adminDelivery = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationSubmitted)
            ->where('related_id', $revisionTwo->id)
            ->where('variant', 'responsible_admin')
            ->sole();
        $this->assertSame($reopeningAdmin->id, $adminDelivery->recipient_user_id);
        $this->assertNotSame($assigningAdmin->id, $adminDelivery->recipient_user_id);

        $this->actingAs($reopeningAdmin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('panel.communication-deliveries.store', $reopeningDelivery), [
                'lock_version' => $reopeningDelivery->lock_version,
                'reason' => 'Reintento sintético desde la bitácora para comprobar la revalidación.',
                'confirm_processing' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $reopeningDelivery->attempts()->count());
        app()->call([new DeliverCommunication($reopeningDelivery->id), 'handle']);
        $this->assertSame(CommunicationDeliveryStatus::Cancelled, $reopeningDelivery->fresh()->status);
        $this->assertSame('evaluation_reopening_no_longer_actionable', $reopeningDelivery->fresh()->failure_code);

        $historicalAck = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationSubmitted)
            ->where('related_id', $revisionOne->id)
            ->where('variant', 'subject_judge')
            ->sole();
        app()->call([new DeliverCommunication($historicalAck->id), 'handle']);
        $this->assertSame(CommunicationDeliveryStatus::Sent, $historicalAck->fresh()->status);
    }

    public function test_close_digest_is_dry_run_by_default_idempotent_bounded_and_fails_whole_batch_on_due_at_drift(): void
    {
        Queue::fake();
        Notification::fake();
        [$admin, $judges, $substitutes] = $this->createEvaluationScenario();
        $first = $this->assignmentFor($judges->get(0));
        $second = $this->assignmentFor($judges->get(1));
        $third = $this->assignmentFor($judges->get(2));
        $fourth = $this->assignmentFor($judges->get(3));
        $this->completeEvaluation($judges->get(0), $first);
        $conflict = app(DeclareJudgeConflict::class)->execute(
            $third,
            $judges->get(2),
            JudgeConflictType::ProfessionalOrEconomicRelationship,
            null,
        );
        $replacement = app(ResolveJudgeConflict::class)->execute(
            $conflict,
            $admin,
            $substitutes->first()->judgeProfile->public_id,
            'Resolución sintética para preparar el resumen de cierre.',
            false,
        );
        app(CancelJudgeAssignment::class)->execute(
            $fourth,
            $admin,
            'Cancelación sintética para preparar el resumen de cierre.',
        );

        $digests = app(EvaluationCloseDigest::class);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-27 23:59:59', 'America/Hermosillo'));
        $this->assertSame('evaluation_close_digest_window_not_open', $digests->queue(false)['reason_code']);
        $this->assertSame(0, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->count());

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 00:00:00', 'America/Hermosillo'));
        $preview = $digests->queue(false);
        $this->assertSame('dry_run', $preview['status']);
        $this->assertSame(5, $preview['judges_considered']);
        $this->assertSame(0, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->count());
        $this->artisan('flowerflow:evaluations-queue-close-digests')
            ->expectsOutputToContain('dry_run')
            ->assertSuccessful();

        $result = $digests->queue(true);
        $this->assertSame(5, $result['deliveries_requested']);
        $this->assertSame(5, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->count());
        $digests->queue(true);
        $this->assertSame(5, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->count());
        $this->assertSame(
            ['submitted' => 1, 'pending' => 0, 'conflicts_replacements' => 0, 'cancelled' => 0],
            $digests->counts($judges->get(0)->judgeProfile, $first->competition),
        );
        $this->assertSame(1, $digests->counts($judges->get(1)->judgeProfile, $second->competition)['pending']);
        $this->assertSame(1, $digests->counts($judges->get(2)->judgeProfile, $third->competition)['conflicts_replacements']);
        $this->assertSame(1, $digests->counts($judges->get(3)->judgeProfile, $fourth->competition)['cancelled']);
        $this->assertSame(1, $digests->counts($substitutes->first()->judgeProfile, $replacement->competition)['conflicts_replacements']);

        $sentDigest = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->where('related_id', $judges->get(0)->judgeProfile->id)
            ->sole();
        app()->call([new DeliverCommunication($sentDigest->id), 'handle']);
        $this->assertSame(CommunicationDeliveryStatus::Sent, $sentDigest->fresh()->status);
        Notification::assertSentTo($judges->get(0), EvaluationCloseDigestNotification::class);

        $changedDigest = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::EvaluationCloseDigest)
            ->where('related_id', $judges->get(1)->judgeProfile->id)
            ->sole();
        DB::table('judge_assignments')->where('id', $second->id)->update([
            'status' => 'cancelled',
            'current_slot' => null,
            'cancelled_by_user_id' => $admin->id,
            'cancelled_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        app()->call([new DeliverCommunication($changedDigest->id), 'handle']);
        $this->assertSame(CommunicationDeliveryStatus::Cancelled, $changedDigest->fresh()->status);
        $this->assertSame('evaluation_close_digest_counts_changed', $changedDigest->fresh()->failure_code);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 23:59:59', 'America/Hermosillo'));
        $this->assertSame('dry_run', $digests->queue(false)['status']);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-29 00:00:00', 'America/Hermosillo'));
        $this->expectException(EvaluationCloseDigestRejected::class);
        $digests->queue(false);
    }

    public function test_digest_drift_rejects_before_creating_any_delivery_and_audit_metadata_remains_redacted(): void
    {
        Queue::fake();
        [, $judges] = $this->createEvaluationScenario();
        $assignment = $this->assignmentFor($judges->first());
        DB::table('judge_assignments')->where('id', $assignment->id)->update([
            'due_at' => $assignment->due_at->addSecond(),
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-28 00:00:00', 'America/Hermosillo'));

        try {
            app(EvaluationCloseDigest::class)->queue(true);
            $this->fail('A divergent assignment deadline must reject the complete digest batch.');
        } catch (EvaluationCloseDigestRejected $exception) {
            $this->assertSame('assignment_due_at_diverged', $exception->reasonCode);
        }
        $this->assertDatabaseCount('communication_deliveries', 0);

        $serialized = AuditLog::query()
            ->where(function ($query): void {
                $query->where('action', 'like', 'assignment.%notification%')
                    ->orWhere('action', 'like', 'evaluation.%notification%')
                    ->orWhere('action', 'like', 'evaluation.close_digest%');
            })
            ->pluck('metadata')->toJson();
        foreach (['@example.test', 'score', 'total', 'comment', 'Motivo cifrado', 'Motivo'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }
    }

    private function assignmentFor(User $judge): JudgeAssignment
    {
        return JudgeAssignment::query()->where('judge_profile_id', $judge->judgeProfile->id)->firstOrFail();
    }

    private function completeEvaluation(User $judge, JudgeAssignment $assignment): Evaluation
    {
        $evaluation = app(OpenEvaluationDraft::class)->execute($assignment, $judge);
        $criteria = RubricCriterion::query()
            ->where('rubric_version_id', $assignment->rubric_version_id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (RubricCriterion $criterion): array => [
                'code' => $criterion->code,
                'score' => '8.0000',
                'comment' => 'Comentario sintético de criterio.',
            ])->all();
        $evaluation = app(SaveEvaluationDraft::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'general_comment' => str_repeat('c', 120),
            'criteria' => $criteria,
            'intent' => 'save',
        ]);

        return app(SubmitEvaluation::class)->execute($assignment, $judge, [
            'lock_version' => $evaluation->lock_version,
            'confirm_submission' => '1',
        ])->fresh('currentRevision');
    }
}
