<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Exceptions\EvaluationDraftRejected;
use App\Exceptions\StaleEvaluationDraft;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationDraftCalculator;
use App\Services\EvaluationRubricContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class SaveEvaluationDraft
{
    public function __construct(
        private EnsureEvaluationDraftContext $ensureContext,
        private EvaluationDraftCalculator $calculator,
        private EvaluationRubricContract $rubricContract,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  array{lock_version:int,general_comment:?string,criteria:list<array{code:string,score:string|int|null,comment:?string}>,intent?:string}  $payload
     */
    public function execute(JudgeAssignment $assignment, User $actor, array $payload): Evaluation
    {
        try {
            $payload = $this->normalizePayload($payload);

            return DB::transaction(function () use ($assignment, $actor, $payload): Evaluation {
                $isAdmin = $actor->hasExactRoles(['admin']);
                $context = $isAdmin
                    ? $this->ensureContext->executeForAdmin($assignment, $actor, 'manage reopened evaluations', true, true)
                    : $this->ensureContext->execute($assignment, $actor, true, true);
                $evaluation = Evaluation::query()
                    ->where('judge_assignment_id', $context['assignment']->id)
                    ->lockForUpdate()
                    ->first();
                if (! $evaluation) {
                    throw new EvaluationDraftRejected('evaluation_not_started', 'Primero inicia explícitamente la evaluación.');
                }

                $allowedStatuses = $isAdmin
                    ? [EvaluationStatus::Reopened]
                    : [EvaluationStatus::Draft, EvaluationStatus::Reopened];
                $aggregate = $this->ensureContext->assertAggregate(
                    $evaluation,
                    $context,
                    true,
                    $allowedStatuses,
                    [EvaluationRevisionStatus::Draft],
                );
                if ($evaluation->status === EvaluationStatus::Reopened
                    && config('flowerflow.flags.evaluation_finalization') !== true) {
                    throw new EvaluationDraftRejected('evaluation_finalization_disabled', 'La edición de revisiones reabiertas está deshabilitada.');
                }
                if ($evaluation->lock_version !== $payload['lock_version']) {
                    throw new StaleEvaluationDraft($evaluation->lock_version);
                }

                $incomingByCode = collect($payload['criteria'])->keyBy('code');
                if ($incomingByCode->keys()->diff($context['criteria']->pluck('code'))->isNotEmpty()) {
                    throw new EvaluationDraftRejected('criterion_code_unknown', 'La solicitud contiene un criterio desconocido o ajeno a la rúbrica fijada.');
                }
                $scoresByCriterion = $aggregate['scores']->keyBy('rubric_criterion_id');
                $components = [];
                $captured = 0;
                $now = now('UTC');

                foreach ($context['criteria'] as $criterion) {
                    /** @var EvaluationScore $scoreRow */
                    $scoreRow = $scoresByCriterion->get($criterion->id);
                    $incoming = $incomingByCode->get($criterion->code);
                    $score = $incoming !== null
                        ? $this->calculator->normalizeScore($incoming['score'])
                        : $scoreRow->score;
                    $comment = $incoming !== null ? $incoming['comment'] : $scoreRow->comment;
                    $component = $score === null ? null : $this->calculator->component($score, $criterion->weight);

                    DB::table('evaluation_scores')->where('id', $scoreRow->id)->update([
                        'score' => $score,
                        'calculated_component' => $component,
                        'comment' => $comment,
                        'updated_at' => $now,
                    ]);
                    $components[] = $component;
                    if ($score !== null) {
                        $captured++;
                    }
                }

                $total = $this->calculator->total($components, $context['criteria']->count());
                $previousLockVersion = $evaluation->lock_version;
                $newLockVersion = $previousLockVersion + 1;
                DB::table('evaluation_revisions')->where('id', $aggregate['revision']->id)->update([
                    'general_comment' => $payload['general_comment'],
                    'total_raw' => $total,
                    'last_saved_by_user_id' => $actor->id,
                    'updated_at' => $now,
                ]);
                $updated = DB::table('evaluations')
                    ->where('id', $evaluation->id)
                    ->where('lock_version', $previousLockVersion)
                    ->update([
                        'lock_version' => $newLockVersion,
                        'updated_at' => $now,
                    ]);
                if ($updated !== 1) {
                    throw new StaleEvaluationDraft($previousLockVersion);
                }

                $evaluation->refresh();
                $auditAction = $evaluation->status === EvaluationStatus::Reopened
                    ? ($isAdmin ? 'evaluation.reopened_draft_saved_administratively' : 'evaluation.reopened_draft_saved')
                    : 'evaluation.draft_saved';
                $this->audit->record($auditAction, $evaluation, $actor, [
                    'assignment_id' => $context['assignment']->id,
                    'rubric_version_id' => $context['rubric']->id,
                    'blind_review_package_id' => $context['package']->id,
                    'revision_number' => $aggregate['revision']->revision_number,
                    'lock_version_previous' => $previousLockVersion,
                    'lock_version_new' => $newLockVersion,
                    'captured_criteria_count' => $captured,
                    'is_complete' => $total !== null,
                ]);

                return $evaluation;
            }, 5);
        } catch (StaleEvaluationDraft $exception) {
            $evaluation = Evaluation::query()->where('judge_assignment_id', $assignment->id)->first();
            $subject = $evaluation ?? $assignment;
            $action = $evaluation?->status === EvaluationStatus::Reopened
                ? 'evaluation.reopened_draft_save_rejected_stale'
                : 'evaluation.draft_save_rejected_stale';
            $this->audit->record($action, $subject, $actor, [
                'assignment_id' => $assignment->id,
                ...($evaluation ? ['evaluation_id' => $evaluation->id] : []),
                'lock_version_previous' => $exception->persistedLockVersion,
                'reason_code' => 'stale_lock_version',
            ]);

            throw $exception;
        } catch (EvaluationDraftRejected $exception) {
            $evaluation = Evaluation::query()->where('judge_assignment_id', $assignment->id)->first();
            $action = $evaluation?->status === EvaluationStatus::Reopened
                ? 'evaluation.reopened_draft_save_rejected'
                : 'evaluation.draft_save_rejected';
            $this->audit->record($action, $evaluation ?? $assignment, $actor, [
                'assignment_id' => $assignment->id,
                ...($evaluation ? ['evaluation_id' => $evaluation->id] : []),
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['evaluation' => $exception->getMessage()]);
        }
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array{lock_version:int,general_comment:?string,criteria:list<array{code:string,score:string|int|null,comment:?string}>,intent:string}
     */
    private function normalizePayload(array $payload): array
    {
        if (array_diff(array_keys($payload), ['lock_version', 'general_comment', 'criteria', 'intent']) !== []
            || ! isset($payload['lock_version'])
            || filter_var($payload['lock_version'], FILTER_VALIDATE_INT) === false
            || (int) $payload['lock_version'] < 0
            || ! array_key_exists('general_comment', $payload)
            || ! array_key_exists('criteria', $payload)
            || ! is_array($payload['criteria'])
            || count($payload['criteria']) > $this->rubricContract->maximumCriterionCount()) {
            throw new EvaluationDraftRejected('evaluation_payload_invalid', 'El contenido del borrador no respeta la allowlist autorizada.');
        }

        $intent = $payload['intent'] ?? 'save';
        if (! is_string($intent) || ! in_array($intent, ['save', 'review', 'autosave'], true)) {
            throw new EvaluationDraftRejected('evaluation_intent_invalid', 'La intención del guardado no es válida.');
        }

        $generalComment = $payload['general_comment'];
        if ($generalComment !== null && (! is_string($generalComment) || mb_strlen($generalComment) > 2000)) {
            throw new EvaluationDraftRejected('general_comment_invalid', 'El comentario general admite hasta 2,000 caracteres.');
        }

        $seen = [];
        $criteria = [];
        foreach ($payload['criteria'] as $criterion) {
            if (! is_array($criterion)
                || array_diff(array_keys($criterion), ['code', 'score', 'comment']) !== []
                || ! isset($criterion['code'])
                || ! array_key_exists('score', $criterion)
                || ! array_key_exists('comment', $criterion)
                || ! is_string($criterion['code'])
                || isset($seen[$criterion['code']])) {
                throw new EvaluationDraftRejected('criteria_payload_invalid', 'Los criterios deben ser únicos y contener sólo código, puntaje y comentario.');
            }
            $seen[$criterion['code']] = true;
            $comment = $criterion['comment'];
            if ($comment !== null && (! is_string($comment) || mb_strlen($comment) > 1000)) {
                throw new EvaluationDraftRejected('criterion_comment_invalid', 'Cada comentario de criterio admite hasta 1,000 caracteres.');
            }
            try {
                $this->calculator->normalizeScore($criterion['score']);
            } catch (InvalidArgumentException $exception) {
                throw new EvaluationDraftRejected('criterion_score_invalid', $exception->getMessage());
            }
            $criteria[] = [
                'code' => $criterion['code'],
                'score' => $criterion['score'],
                'comment' => $comment,
            ];
        }

        return [
            'lock_version' => (int) $payload['lock_version'],
            'general_comment' => $generalComment,
            'criteria' => $criteria,
            'intent' => $intent,
        ];
    }
}
