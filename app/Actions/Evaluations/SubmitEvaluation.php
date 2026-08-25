<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\EvaluationSubmissionMode;
use App\Events\EvaluationSubmitted;
use App\Exceptions\EvaluationDraftRejected;
use App\Exceptions\StaleEvaluationDraft;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class SubmitEvaluation
{
    public function __construct(
        private EnsureEvaluationDraftContext $ensureContext,
        private EvaluationDraftCalculator $calculator,
        private AuditLogger $audit,
    ) {}

    /** @param array<string,mixed> $payload */
    public function execute(JudgeAssignment $assignment, User $actor, array $payload): Evaluation
    {
        try {
            $lockVersion = $this->normalizePayload($payload, $actor->hasExactRoles(['admin']));

            return DB::transaction(function () use ($assignment, $actor, $lockVersion): Evaluation {
                $isAdmin = $actor->hasExactRoles(['admin']);
                $context = $isAdmin
                    ? $this->ensureContext->executeForAdmin($assignment, $actor, 'manage reopened evaluations', true, true)
                    : $this->ensureContext->execute($assignment, $actor, true, true, 'submit own evaluations');
                $evaluation = Evaluation::query()
                    ->where('judge_assignment_id', $context['assignment']->id)
                    ->lockForUpdate()
                    ->first();
                if (! $evaluation) {
                    throw new EvaluationDraftRejected('evaluation_not_started', 'La evaluación todavía no existe.');
                }
                if ($evaluation->lock_version !== $lockVersion) {
                    throw new StaleEvaluationDraft($evaluation->lock_version);
                }
                if ($isAdmin && $evaluation->status !== EvaluationStatus::Reopened) {
                    throw new EvaluationDraftRejected('admin_initial_submission_forbidden', 'La administración sólo puede enviar una revisión reabierta.');
                }

                $aggregate = $this->ensureContext->assertAggregate(
                    $evaluation,
                    $context,
                    true,
                    $isAdmin ? [EvaluationStatus::Reopened] : [EvaluationStatus::Draft, EvaluationStatus::Reopened],
                    [EvaluationRevisionStatus::Draft],
                );
                $comment = $this->unicodeTrim((string) $aggregate['revision']->general_comment);
                $commentLength = mb_strlen($comment);
                if ($commentLength < 100 || $commentLength > 2000) {
                    throw new EvaluationDraftRejected('general_comment_submission_length_invalid', 'El comentario general debe contener entre 100 y 2,000 caracteres para enviar.');
                }

                $scores = $aggregate['scores']->keyBy('rubric_criterion_id');
                $components = [];
                foreach ($context['criteria'] as $criterion) {
                    /** @var EvaluationScore|null $scoreRow */
                    $scoreRow = $scores->get($criterion->id);
                    if (! $scoreRow || $scoreRow->score === null || $scoreRow->calculated_component === null) {
                        throw new EvaluationDraftRejected('evaluation_incomplete', 'Captura todos los criterios antes de enviar.');
                    }
                    try {
                        $score = $this->calculator->normalizeScore($scoreRow->score);
                    } catch (InvalidArgumentException) {
                        throw new EvaluationDraftRejected('persisted_score_invalid', 'Un puntaje persistido dejó de cumplir el contrato decimal.');
                    }
                    $component = $this->calculator->component($score, $criterion->weight);
                    if (bccomp($component, $scoreRow->calculated_component, 4) !== 0) {
                        throw new EvaluationDraftRejected('persisted_component_drift', 'Un componente persistido no coincide con el cálculo autoritativo.');
                    }
                    $components[] = $component;
                }
                $total = $this->calculator->total($components, $context['criteria']->count());
                if ($total === null || $aggregate['revision']->total_raw === null
                    || bccomp($total, $aggregate['revision']->total_raw, 4) !== 0) {
                    throw new EvaluationDraftRejected('persisted_total_drift', 'El total persistido no coincide con el cálculo autoritativo.');
                }

                $now = now('UTC');
                $mode = $isAdmin ? EvaluationSubmissionMode::Administrative : EvaluationSubmissionMode::Judge;
                DB::table('evaluation_revisions')->where('id', $aggregate['revision']->id)->update([
                    'status' => EvaluationRevisionStatus::Submitted->value,
                    'general_comment' => $comment,
                    'submitted_by_user_id' => $actor->id,
                    'submitted_at' => $now,
                    'submission_mode' => $mode->value,
                    'last_saved_by_user_id' => $actor->id,
                    'updated_at' => $now,
                ]);
                $newLock = $evaluation->lock_version + 1;
                $updated = DB::table('evaluations')->where('id', $evaluation->id)
                    ->where('lock_version', $evaluation->lock_version)
                    ->update([
                        'status' => EvaluationStatus::Submitted->value,
                        'lock_version' => $newLock,
                        'updated_at' => $now,
                    ]);
                if ($updated !== 1) {
                    throw new StaleEvaluationDraft($evaluation->lock_version);
                }

                $this->audit->record('evaluation.submitted', $evaluation, $actor, [
                    'evaluation_id' => $evaluation->id,
                    'assignment_id' => $context['assignment']->id,
                    'revision_id' => $aggregate['revision']->id,
                    'subject_judge_profile_id' => $context['profile']->id,
                    'revision_number' => $aggregate['revision']->revision_number,
                    'submission_mode' => $mode->value,
                    'status_previous' => $evaluation->status->value,
                    'status_new' => EvaluationStatus::Submitted->value,
                    'lock_version_previous' => $evaluation->lock_version,
                    'lock_version_new' => $newLock,
                    'captured_criteria_count' => $context['criteria']->count(),
                    'is_complete' => true,
                ]);
                EvaluationSubmitted::dispatch($evaluation->id, $aggregate['revision']->id, $actor->id, $mode->value);

                return $evaluation->fresh();
            }, 5);
        } catch (StaleEvaluationDraft $exception) {
            $evaluation = Evaluation::query()->where('judge_assignment_id', $assignment->id)->first();
            $this->audit->record('evaluation.submission_rejected_stale', $evaluation ?? $assignment, $actor, [
                'assignment_id' => $assignment->id,
                ...($evaluation ? ['evaluation_id' => $evaluation->id] : []),
                'lock_version_previous' => $exception->persistedLockVersion,
                'reason_code' => 'stale_lock_version',
            ]);
            throw $exception;
        } catch (EvaluationDraftRejected $exception) {
            $evaluation = Evaluation::query()->where('judge_assignment_id', $assignment->id)->first();
            $this->audit->record('evaluation.submission_rejected', $evaluation ?? $assignment, $actor, [
                'assignment_id' => $assignment->id,
                ...($evaluation ? ['evaluation_id' => $evaluation->id] : []),
                'reason_code' => $exception->reasonCode,
            ]);
            throw ValidationException::withMessages(['evaluation' => $exception->getMessage()]);
        }
    }

    /** @param array<string,mixed> $payload */
    private function normalizePayload(array $payload, bool $isAdmin): int
    {
        $allowed = $isAdmin
            ? ['lock_version', 'current_password', 'confirm_submission', 'confirm_acting_on_behalf']
            : ['lock_version', 'confirm_submission'];
        if (array_diff(array_keys($payload), $allowed) !== []
            || filter_var($payload['lock_version'] ?? null, FILTER_VALIDATE_INT) === false
            || (int) $payload['lock_version'] < 0
            || ! in_array($payload['confirm_submission'] ?? null, [true, 1, '1', 'yes', 'on'], true)
            || ($isAdmin && ! in_array($payload['confirm_acting_on_behalf'] ?? null, [true, 1, '1', 'yes', 'on'], true))) {
            throw new EvaluationDraftRejected('submission_payload_invalid', 'La confirmación de envío no respeta el contrato autorizado.');
        }

        return (int) $payload['lock_version'];
    }

    private function unicodeTrim(string $value): string
    {
        return preg_replace('/^\s+|\s+$/u', '', $value) ?? trim($value);
    }
}
