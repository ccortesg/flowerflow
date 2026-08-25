<?php

namespace App\Actions\Assignments;

use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Enums\AssignmentNotificationMode;
use App\Enums\BlindReviewPackageStatus;
use App\Enums\EligibilityReviewStatus;
use App\Exceptions\BulkJudgeAssignmentRejected;
use App\Models\AuditLog;
use App\Models\BlindReviewPackage;
use App\Models\BlindReviewPackageFile;
use App\Models\ClarificationRequest;
use App\Models\Competition;
use App\Models\EligibilityReview;
use App\Models\JudgeProfile;
use App\Models\ResidencyDocumentRequest;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BulkJudgeAssignmentEligibility;
use App\Services\BulkJudgeAssignmentIntent;
use App\Services\EligibilityReviewWorkflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ExecuteBulkJudgeAssignment
{
    public function __construct(
        private BulkJudgeAssignmentIntent $intents,
        private BulkJudgeAssignmentEligibility $eligibility,
        private EligibilityReviewWorkflow $admissibility,
        private GenerateBlindReviewPackageDraft $generatePackage,
        private ActivateBlindReviewPackage $activatePackage,
        private AssignJudgesToSubmission $assign,
        private SendBulkJudgeAssignmentNotification $sendNotification,
        private AuditLogger $audit,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $encryptedIntent, User $actor): array
    {
        $this->eligibility->assertAdministrator($actor);
        $payload = $this->intents->decode($encryptedIntent, $actor);
        $submissionPublicIds = array_values(array_map('strval', $payload['submissions']));
        $limit = (int) config('flowerflow.bulk_judge_assignment.limit');
        if ($submissionPublicIds === []
            || count($submissionPublicIds) > $limit
            || count($submissionPublicIds) !== count(array_unique($submissionPublicIds))) {
            throw new BulkJudgeAssignmentRejected('intent_selection_invalid', 'La selección cifrada ya no es válida.');
        }

        $lock = Cache::lock(
            'flowerflow:bulk-judge-assignment:'.$payload['operation_id'],
            (int) config('flowerflow.bulk_judge_assignment.lock_seconds'),
        );
        if (! $lock->get()) {
            throw new BulkJudgeAssignmentRejected('operation_in_progress', 'La operación ya está siendo procesada. Espera el resultado antes de intentarlo de nuevo.');
        }

        try {
            if ($this->terminalAuditExists($actor, $payload['operation_id'])) {
                throw new BulkJudgeAssignmentRejected('operation_already_completed', 'La operación ya fue procesada y no se ejecutó nuevamente.');
            }

            $judge = $this->eligibility->findEligibleJudge($payload['judge_profile']);
            $this->recordRequestedOnce($actor, $judge, $payload);
            $results = [];
            $createdAssignmentIds = [];

            foreach ($submissionPublicIds as $submissionPublicId) {
                try {
                    $item = DB::transaction(function () use ($submissionPublicId, $payload, $actor): array {
                        $submission = Submission::query()
                            ->where('public_id', $submissionPublicId)
                            ->lockForUpdate()
                            ->first();
                        if (! $submission) {
                            throw new BulkJudgeAssignmentRejected('submission_unknown', 'La propuesta seleccionada ya no existe.');
                        }

                        $version = SubmissionVersion::query()
                            ->where('submission_id', $submission->id)
                            ->orderByDesc('version')
                            ->orderByDesc('id')
                            ->lockForUpdate()
                            ->first();
                        $review = EligibilityReview::query()
                            ->where('submission_id', $submission->id)
                            ->lockForUpdate()
                            ->first();
                        if ($review) {
                            ClarificationRequest::query()
                                ->where('eligibility_review_id', $review->id)
                                ->orderBy('id')
                                ->lockForUpdate()
                                ->get();
                            ResidencyDocumentRequest::query()
                                ->where('eligibility_review_id', $review->id)
                                ->orderBy('id')
                                ->lockForUpdate()
                                ->get();
                        }
                        if ($version) {
                            $package = BlindReviewPackage::query()
                                ->where('submission_version_id', $version->id)
                                ->lockForUpdate()
                                ->first();
                            if ($package) {
                                BlindReviewPackageFile::query()
                                    ->where('blind_review_package_id', $package->id)
                                    ->orderBy('id')
                                    ->lockForUpdate()
                                    ->get();
                            }
                        }
                        Competition::query()->whereKey($submission->competition_id)->lockForUpdate()->firstOrFail();
                        $rubricIds = RubricVersion::query()
                            ->where('competition_id', $submission->competition_id)
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->pluck('id');
                        RubricCriterion::query()
                            ->whereIn('rubric_version_id', $rubricIds)
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get();

                        $judge = $this->eligibility->findEligibleJudge($payload['judge_profile'], true);
                        $submission->unsetRelation('versions')->unsetRelation('eligibilityReview');
                        $actualState = $this->eligibility->snapshot($submission, $judge);
                        $expectedState = $payload['states'][$submissionPublicId] ?? null;
                        if (! is_array($expectedState) || $actualState !== $expectedState) {
                            throw new BulkJudgeAssignmentRejected('selection_state_changed', 'La propuesta cambió después del preflight; vuelve a revisarla.');
                        }
                        if ($blocker = $this->eligibility->blocker($actualState)) {
                            throw new BulkJudgeAssignmentRejected($blocker['code'], $blocker['message']);
                        }
                        if (! $review || ! $version || $review->submission_version_id !== $version->id) {
                            throw new BulkJudgeAssignmentRejected('eligibility_version_diverged', 'El expediente no corresponde a la versión final vigente.');
                        }

                        $admissibilityState = 'already_admitted';
                        if ($review->status !== EligibilityReviewStatus::Admitted) {
                            $this->admissibility->decide(
                                $review,
                                $actor,
                                EligibilityReviewStatus::Admitted,
                                (string) $payload['participant_reason'],
                                filled($payload['internal_notes'] ?? null) ? (string) $payload['internal_notes'] : null,
                            );
                            $admissibilityState = 'admitted';
                        }

                        $packageStateBefore = $actualState['package_status'];
                        $package = $this->generatePackage->execute(
                            $submission,
                            $actor,
                            (string) $payload['package_reason'],
                        );
                        if ($package->status !== BlindReviewPackageStatus::Active) {
                            $package = $this->activatePackage->execute(
                                $submission,
                                $actor,
                                (string) $payload['package_reason'],
                            );
                        }
                        if ($package->status !== BlindReviewPackageStatus::Active) {
                            throw new BulkJudgeAssignmentRejected('package_not_active', 'El paquete ciego no quedó activo.');
                        }
                        $packageState = $packageStateBefore === BlindReviewPackageStatus::Active->value
                            ? 'reused'
                            : 'created_and_activated';

                        $assignmentResult = $this->assign->executeWithNotificationMode(
                            $submission,
                            $actor,
                            [$judge->public_id],
                            (string) $payload['assignment_reason'],
                            (bool) $payload['notify_judge']
                                ? AssignmentNotificationMode::Bulk
                                : AssignmentNotificationMode::None,
                        );
                        $assignment = $assignmentResult['created']->first();

                        return [
                            'submission_public_id' => $submission->public_id,
                            'admissibility' => $admissibilityState,
                            'package' => $packageState,
                            'assignment' => $assignment ? 'created' : 'already_exists',
                            'assignment_id' => $assignment?->id,
                            'reason_code' => null,
                            'message' => $assignment
                                ? 'La propuesta quedó preparada y asignada.'
                                : 'La propuesta ya tenía una asignación vigente para este juez.',
                        ];
                    }, 5);

                    if ($item['assignment_id']) {
                        $createdAssignmentIds[] = $item['assignment_id'];
                    }
                    unset($item['assignment_id']);
                    $results[] = $item;
                } catch (AuthorizationException $exception) {
                    throw $exception;
                } catch (BulkJudgeAssignmentRejected $exception) {
                    $results[] = $this->failedItem($submissionPublicId, $exception->reasonCode, $exception->getMessage());
                } catch (ValidationException $exception) {
                    $results[] = $this->failedItem(
                        $submissionPublicId,
                        'business_validation_rejected',
                        collect($exception->errors())->flatten()->first() ?: 'La propuesta no superó una validación de negocio.',
                    );
                } catch (Throwable) {
                    $results[] = $this->failedItem(
                        $submissionPublicId,
                        'unexpected_item_failure',
                        'La propuesta no pudo procesarse y sus cambios fueron revertidos.',
                    );
                }
            }

            $notification = $this->sendNotification->execute(
                $judge,
                $createdAssignmentIds,
                $actor,
                (string) $payload['operation_id'],
                (bool) $payload['notify_judge'],
            );
            $failedCount = collect($results)->where('assignment', 'failed')->count();
            $createdCount = collect($results)->where('assignment', 'created')->count();
            $existingCount = collect($results)->where('assignment', 'already_exists')->count();
            $action = $failedCount > 0
                ? 'assignment.bulk_partially_completed'
                : 'assignment.bulk_completed';
            $this->audit->record($action, $judge, $actor, [
                'operation_id' => $payload['operation_id'],
                'judge_profile_id' => $judge->id,
                'selected_count' => count($submissionPublicIds),
                'created_count' => $createdCount,
                'existing_count' => $existingCount,
                'failed_count' => $failedCount,
                'notification_requested' => (bool) $payload['notify_judge'],
                'notification_queued' => $notification,
                'reason_codes' => collect($results)->pluck('reason_code')->filter()->unique()->values()->all(),
            ]);

            return [
                'operation_id' => $payload['operation_id'],
                'judge_public_id' => $judge->public_id,
                'selected_count' => count($submissionPublicIds),
                'created_count' => $createdCount,
                'existing_count' => $existingCount,
                'failed_count' => $failedCount,
                'notification_requested' => (bool) $payload['notify_judge'],
                'notification_queued' => $notification,
                'items' => $results,
            ];
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, string|null> */
    private function failedItem(string $submissionPublicId, string $reasonCode, string $message): array
    {
        return [
            'submission_public_id' => $submissionPublicId,
            'admissibility' => 'failed',
            'package' => 'failed',
            'assignment' => 'failed',
            'reason_code' => $reasonCode,
            'message' => $message,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function recordRequestedOnce(User $actor, JudgeProfile $judge, array $payload): void
    {
        $exists = AuditLog::query()
            ->where('actor_user_id', $actor->id)
            ->where('action', 'assignment.bulk_requested')
            ->where('metadata->operation_id', $payload['operation_id'])
            ->exists();
        if (! $exists) {
            $this->audit->record('assignment.bulk_requested', $judge, $actor, [
                'operation_id' => $payload['operation_id'],
                'judge_profile_id' => $judge->id,
                'selected_count' => count($payload['submissions']),
                'notification_requested' => (bool) $payload['notify_judge'],
            ]);
        }
    }

    private function terminalAuditExists(User $actor, string $operationId): bool
    {
        return AuditLog::query()
            ->where('actor_user_id', $actor->id)
            ->whereIn('action', ['assignment.bulk_completed', 'assignment.bulk_partially_completed'])
            ->where('metadata->operation_id', $operationId)
            ->exists();
    }
}
