<?php

namespace App\Actions\Evaluations;

use App\Enums\BlindReviewPackageStatus;
use App\Enums\EvaluationRevisionStatus;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeProfileStatus;
use App\Enums\RubricVersionStatus;
use App\Exceptions\EvaluationDraftRejected;
use App\Models\BlindReviewPackage;
use App\Models\Evaluation;
use App\Models\EvaluationReopening;
use App\Models\EvaluationRevision;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\JudgeProfile;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Services\EvaluationRubricContract;
use App\Services\EvaluationWindow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use LogicException;

final class EnsureEvaluationDraftContext
{
    public function __construct(
        private EvaluationRubricContract $rubricContract,
        private EvaluationWindow $window,
    ) {}

    /**
     * @return array{assignment:JudgeAssignment,profile:JudgeProfile,rubric:RubricVersion,criteria:Collection<int,RubricCriterion>,package:BlindReviewPackage}
     */
    public function execute(
        JudgeAssignment $assignment,
        User $actor,
        bool $forMutation,
        bool $lock,
        string $permission = 'manage own evaluation drafts',
    ): array {
        if (config('flowerflow.flags.evaluation') !== true
            || ! $actor->hasExactRoles(['judge'])
            || ! $actor->can($permission)
            || ! $actor->hasVerifiedEmail()) {
            throw new EvaluationDraftRejected('actor_not_authorized', 'La cuenta no está autorizada para operar este borrador.');
        }

        $lockedAssignment = $this->query(JudgeAssignment::query()->whereKey($assignment->id), $lock)->first();
        if (! $lockedAssignment) {
            throw new EvaluationDraftRejected('assignment_missing', 'La asignación ya no está disponible.');
        }

        $profile = $this->query(JudgeProfile::query()->where('user_id', $actor->id), $lock)->first();
        if (! $profile
            || $profile->status !== JudgeProfileStatus::Active
            || $profile->password_initialized_at === null
            || $lockedAssignment->judge_profile_id !== $profile->id) {
            throw new EvaluationDraftRejected('assignment_owner_not_operational', 'La asignación no pertenece a una cuenta juez operativa.');
        }

        return $this->resolve($lockedAssignment, $profile, $forMutation, $lock);
    }

    /**
     * @return array{assignment:JudgeAssignment,profile:JudgeProfile,rubric:RubricVersion,criteria:Collection<int,RubricCriterion>,package:BlindReviewPackage}
     */
    public function executeForAdmin(
        JudgeAssignment $assignment,
        User $actor,
        string $permission,
        bool $forMutation,
        bool $lock,
    ): array {
        if (config('flowerflow.flags.evaluation') !== true
            || ! $actor->hasExactRoles(['admin'])
            || ! $actor->can($permission)
            || ! $actor->hasVerifiedEmail()) {
            throw new EvaluationDraftRejected('actor_not_authorized', 'La cuenta administrativa no está autorizada para operar esta evaluación.');
        }

        $lockedAssignment = $this->query(JudgeAssignment::query()->whereKey($assignment->id), $lock)->first();
        if (! $lockedAssignment) {
            throw new EvaluationDraftRejected('assignment_missing', 'La asignación ya no está disponible.');
        }
        $profile = $this->query(JudgeProfile::query()->whereKey($lockedAssignment->judge_profile_id), $lock)->first();
        $judge = $profile?->user;
        if (! $profile || $profile->status !== JudgeProfileStatus::Active
            || $profile->password_initialized_at === null
            || ! $judge?->hasExactRoles(['judge'])
            || ! $judge->hasVerifiedEmail()) {
            throw new EvaluationDraftRejected('subject_judge_not_operational', 'El juez sujeto ya no es una cuenta operativa.');
        }

        return $this->resolve($lockedAssignment, $profile, $forMutation, $lock);
    }

    /**
     * @param  array{assignment:JudgeAssignment,profile:JudgeProfile,rubric:RubricVersion,criteria:Collection<int,RubricCriterion>,package:BlindReviewPackage}  $context
     * @return array{revision:EvaluationRevision,scores:Collection<int,EvaluationScore>}
     */
    public function assertAggregate(
        Evaluation $evaluation,
        array $context,
        bool $lock,
        array $allowedEvaluationStatuses = [EvaluationStatus::Draft],
        array $allowedRevisionStatuses = [EvaluationRevisionStatus::Draft],
    ): array {
        if ($evaluation->judge_assignment_id !== $context['assignment']->id
            || $evaluation->rubric_version_id !== $context['rubric']->id
            || $evaluation->blind_review_package_id !== $context['package']->id
            || ! in_array($evaluation->status, $allowedEvaluationStatuses, true)
            || $evaluation->current_revision_id === null) {
            throw new EvaluationDraftRejected('evaluation_aggregate_diverged', 'El borrador ya no coincide con la asignación, rúbrica o paquete fijados.');
        }

        $revision = $this->query(
            EvaluationRevision::query()->whereKey($evaluation->current_revision_id),
            $lock,
        )->first();
        if (! $revision
            || $revision->evaluation_id !== $evaluation->id
            || ! in_array($revision->status, $allowedRevisionStatuses, true)
            || $revision->subject_judge_profile_id !== $context['assignment']->judge_profile_id
            || ($revision->revision_number === 1 && $revision->source_revision_id !== null)
            || ($revision->revision_number > 1 && $revision->source_revision_id === null)) {
            throw new EvaluationDraftRejected('evaluation_revision_diverged', 'La revisión vigente no coincide con el contrato de la evaluación.');
        }
        if (($evaluation->status === EvaluationStatus::Draft
                && ($revision->revision_number !== 1 || $revision->status !== EvaluationRevisionStatus::Draft))
            || ($evaluation->status === EvaluationStatus::Reopened
                && ($revision->revision_number <= 1 || $revision->status !== EvaluationRevisionStatus::Draft))
            || ($evaluation->status === EvaluationStatus::Submitted
                && $revision->status !== EvaluationRevisionStatus::Submitted)) {
            throw new EvaluationDraftRejected('evaluation_status_revision_diverged', 'El estado agregado no coincide con su revisión vigente.');
        }
        if ($revision->revision_number > 1) {
            $source = $this->query(EvaluationRevision::query()->whereKey($revision->source_revision_id), $lock)->first();
            $reopening = $this->query(EvaluationReopening::query()
                ->where('evaluation_id', $evaluation->id)
                ->where('source_revision_id', $revision->source_revision_id)
                ->where('target_revision_id', $revision->id), $lock)->first();
            if (! $source || $source->evaluation_id !== $evaluation->id
                || $source->revision_number !== $revision->revision_number - 1
                || $source->status !== EvaluationRevisionStatus::Submitted
                || ! $reopening
                || $reopening->subject_judge_profile_id !== $context['assignment']->judge_profile_id) {
                throw new EvaluationDraftRejected('evaluation_revision_chain_diverged', 'La cadena append-only de revisiones es inconsistente.');
            }
        }

        $scores = $this->query(
            EvaluationScore::query()->where('evaluation_revision_id', $revision->id)->orderBy('rubric_criterion_id'),
            $lock,
        )->get();
        $expectedCriterionIds = $context['criteria']->pluck('id')->sort()->values()->all();
        $expectedCount = $context['criteria']->count();
        if ($scores->count() !== $expectedCount
            || $scores->pluck('rubric_criterion_id')->sort()->values()->all() !== $expectedCriterionIds) {
            throw new EvaluationDraftRejected('evaluation_scores_diverged', 'El borrador no conserva exactamente los criterios de la rúbrica fijada.');
        }

        return ['revision' => $revision, 'scores' => $scores];
    }

    /**
     * @return array{assignment:JudgeAssignment,profile:JudgeProfile,rubric:RubricVersion,criteria:Collection<int,RubricCriterion>,package:BlindReviewPackage}
     */
    private function resolve(JudgeAssignment $lockedAssignment, JudgeProfile $profile, bool $forMutation, bool $lock): array
    {
        if ($lockedAssignment->status !== JudgeAssignmentStatus::Active
            || $lockedAssignment->current_slot !== 1
            || JudgeConflict::query()->where('judge_assignment_id', $lockedAssignment->id)->exists()) {
            throw new EvaluationDraftRejected('assignment_not_active', 'La asignación ya no está activa para evaluación.');
        }

        $this->window->assertAssignment($lockedAssignment, $forMutation);

        $version = $this->query(SubmissionVersion::query()->whereKey($lockedAssignment->submission_version_id), $lock)->first();
        $submission = $version
            ? $this->query(Submission::query()->whereKey($version->submission_id), $lock)->first()
            : null;
        if (! $version || ! $submission || $submission->competition_id !== $lockedAssignment->competition_id) {
            throw new EvaluationDraftRejected('assignment_submission_diverged', 'La versión de propuesta fijada por la asignación es inconsistente.');
        }

        $rubric = $this->query(RubricVersion::query()->whereKey($lockedAssignment->rubric_version_id), $lock)->first();
        if (! $rubric
            || $rubric->competition_id !== $lockedAssignment->competition_id
            || ! in_array($rubric->status, [RubricVersionStatus::Active, RubricVersionStatus::Superseded], true)) {
            throw new EvaluationDraftRejected('assignment_rubric_diverged', 'La rúbrica fijada por la asignación ya no es válida.');
        }
        $criteria = $this->query(
            RubricCriterion::query()->where('rubric_version_id', $rubric->id)->orderBy('sort_order'),
            $lock,
        )->get();
        $rubric->setRelation('criteria', $criteria);
        try {
            $this->rubricContract->assertPersisted($rubric);
        } catch (LogicException) {
            throw new EvaluationDraftRejected('assignment_rubric_contract_diverged', 'La rúbrica fijada diverge del contrato aprobado.');
        }

        $package = $this->query(
            BlindReviewPackage::query()->where('submission_version_id', $lockedAssignment->submission_version_id),
            $lock,
        )->first();
        if (! $package || $package->status !== BlindReviewPackageStatus::Active) {
            throw new EvaluationDraftRejected('assignment_package_not_active', 'El paquete ciego fijado ya no está activo.');
        }
        if ($package->files()->where('status', '<>', BlindReviewPackageStatus::Active->value)->exists()) {
            throw new EvaluationDraftRejected('assignment_package_files_diverged', 'El inventario del paquete ciego es inconsistente.');
        }

        return [
            'assignment' => $lockedAssignment,
            'profile' => $profile,
            'rubric' => $rubric,
            'criteria' => $criteria,
            'package' => $package,
        ];
    }

    /** @template TModel of \Illuminate\Database\Eloquent\Model */
    private function query(Builder $query, bool $lock): Builder
    {
        return $lock ? $query->lockForUpdate() : $query;
    }
}
