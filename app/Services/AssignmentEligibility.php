<?php

namespace App\Services;

use App\Enums\EligibilityReviewStatus;
use App\Exceptions\AssignmentOperationRejected;
use App\Models\EligibilityReview;
use App\Models\Submission;
use App\Models\SubmissionVersion;

final class AssignmentEligibility
{
    public function requireCurrentVersion(Submission $submission, bool $lock = false): SubmissionVersion
    {
        $submissionQuery = Submission::query()->whereKey($submission->getKey());
        $lockedSubmission = ($lock ? $submissionQuery->lockForUpdate() : $submissionQuery)->firstOrFail();

        if ($lockedSubmission->status !== 'submitted' || ! $lockedSubmission->submitted_at) {
            throw new AssignmentOperationRejected('submission_not_submitted', 'La propuesta no está enviada y no puede asignarse.');
        }

        $versionQuery = SubmissionVersion::query()
            ->where('submission_id', $lockedSubmission->id)
            ->orderByDesc('version')
            ->orderByDesc('id');
        $version = ($lock ? $versionQuery->lockForUpdate() : $versionQuery)->first();

        if (! $version) {
            throw new AssignmentOperationRejected('missing_submission_version', 'La propuesta no tiene una versión final disponible.');
        }

        $reviewQuery = EligibilityReview::query()->where('submission_id', $lockedSubmission->id);
        $review = ($lock ? $reviewQuery->lockForUpdate() : $reviewQuery)->first();

        if (! $review
            || $review->status !== EligibilityReviewStatus::Admitted
            || $review->submission_version_id !== $version->id) {
            throw new AssignmentOperationRejected('submission_not_admitted', 'La propuesta y su versión final vigente no están admitidas para evaluación.');
        }

        return $version;
    }
}
