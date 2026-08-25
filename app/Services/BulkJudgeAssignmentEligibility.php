<?php

namespace App\Services;

use App\Enums\BlindReviewPackageStatus;
use App\Enums\ClarificationStatus;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeProfileStatus;
use App\Enums\ResidencyVerificationStatus;
use App\Enums\RubricVersionStatus;
use App\Exceptions\BulkJudgeAssignmentRejected;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;

final class BulkJudgeAssignmentEligibility
{
    public function assertAdministrator(User $actor): void
    {
        abort_unless(config('flowerflow.flags.bulk_judge_assignment'), 404);
        if (! $actor->hasExactRoles(['admin'])
            || ! $actor->can('decide admissibility')
            || ! $actor->can('manage blind review packages')
            || ! $actor->can('manage evaluation assignments')) {
            abort(403);
        }
    }

    public function findEligibleJudge(string $publicId, bool $lock = false): JudgeProfile
    {
        $query = JudgeProfile::query()
            ->where('public_id', $publicId)
            ->with('user.roles');
        $profile = ($lock ? $query->lockForUpdate() : $query)->first();

        if (! $profile
            || $profile->status !== JudgeProfileStatus::Active
            || $profile->max_active_assignments !== null
            || $profile->password_initialized_at === null
            || ! $profile->user
            || ! $profile->user->hasExactRoles(['judge'])
            || ! $profile->user->hasVerifiedEmail()) {
            throw new BulkJudgeAssignmentRejected(
                'selected_judge_ineligible',
                'El juez seleccionado ya no está activo, verificado o con configuración completa.',
            );
        }

        return $profile;
    }

    /** @return array<string, int|string|null> */
    public function snapshot(Submission $submission, ?JudgeProfile $judge = null): array
    {
        $version = $submission->relationLoaded('versions')
            ? $submission->versions->sortByDesc(fn (SubmissionVersion $item): array => [$item->version, $item->id])->first()
            : $submission->versions()->orderByDesc('version')->orderByDesc('id')->first();
        $review = $submission->relationLoaded('eligibilityReview')
            ? $submission->eligibilityReview
            : $submission->eligibilityReview()->first();
        $package = $version?->relationLoaded('blindReviewPackage')
            ? $version->blindReviewPackage
            : $version?->blindReviewPackage()->first();
        $openClarifications = $review
            ? $review->clarifications()->where('status', ClarificationStatus::Open->value)->count()
            : 0;
        $blockingResidency = $review
            ? $review->residencyRequests()->whereNotIn('status', [
                ResidencyVerificationStatus::Verified->value,
                ResidencyVerificationStatus::Cancelled->value,
            ])->count()
            : 0;
        $assignmentId = $version && $judge
            ? JudgeAssignment::query()
                ->where('submission_version_id', $version->id)
                ->where('judge_profile_id', $judge->id)
                ->where('current_slot', 1)
                ->whereIn('status', [
                    JudgeAssignmentStatus::Active->value,
                    JudgeAssignmentStatus::ConflictDeclared->value,
                ])->value('id')
            : null;
        $rubrics = RubricVersion::query()
            ->where('competition_id', $submission->competition_id)
            ->where('status', RubricVersionStatus::Active)
            ->with(['criteria' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id')
            ->get()
            ->map(fn (RubricVersion $rubric): array => [
                'id' => $rubric->id,
                'version' => $rubric->version,
                'criteria' => $rubric->criteria->map(fn ($criterion): array => [
                    'id' => $criterion->id,
                    'code' => $criterion->code,
                    'weight' => (string) $criterion->weight,
                ])->all(),
            ])->all();

        return [
            'submission_id' => $submission->id,
            'submission_status' => $submission->status,
            'submission_version_id' => $version?->id,
            'eligibility_review_id' => $review?->id,
            'eligibility_status' => $review?->status?->value,
            'open_clarifications' => $openClarifications,
            'blocking_residency' => $blockingResidency,
            'package_id' => $package?->id,
            'package_status' => $package?->status?->value,
            'package_sha256' => $package?->payload_sha256,
            'current_assignment_id' => $assignmentId,
            'active_rubric_fingerprint' => hash('sha256', json_encode($rubrics, JSON_THROW_ON_ERROR)),
        ];
    }

    /** @param array<string, int|string|null> $snapshot */
    public function blocker(array $snapshot): ?array
    {
        if ($snapshot['submission_status'] !== 'submitted') {
            return ['code' => 'submission_not_submitted', 'message' => 'La propuesta no está enviada.'];
        }
        if (! $snapshot['submission_version_id']) {
            return ['code' => 'missing_submission_version', 'message' => 'No existe una versión inmutable vigente.'];
        }
        if (! $snapshot['eligibility_review_id']) {
            return ['code' => 'missing_eligibility_review', 'message' => 'No existe expediente; ejecuta primero el backfill de admisibilidad.'];
        }
        if ((int) $snapshot['open_clarifications'] > 0) {
            return ['code' => 'open_clarifications', 'message' => 'Tiene aclaraciones abiertas que deben resolverse.'];
        }
        if ((int) $snapshot['blocking_residency'] > 0) {
            return ['code' => 'residency_pending', 'message' => 'Tiene solicitudes de residencia pendientes de verificar o cancelar.'];
        }
        if ($snapshot['eligibility_status'] === EligibilityReviewStatus::NotAdmitted->value) {
            return ['code' => 'eligibility_not_admitted', 'message' => 'El expediente ya fue resuelto como no admitido.'];
        }
        if (! in_array($snapshot['eligibility_status'], [
            EligibilityReviewStatus::Pending->value,
            EligibilityReviewStatus::InReview->value,
            EligibilityReviewStatus::Admitted->value,
        ], true)) {
            return ['code' => 'eligibility_state_blocked', 'message' => 'El estado de admisibilidad no permite esta operación.'];
        }
        if ($snapshot['package_status'] === BlindReviewPackageStatus::Invalidated->value) {
            return ['code' => 'package_invalidated', 'message' => 'El paquete ciego está invalidado y es evidencia terminal.'];
        }

        return null;
    }
}
