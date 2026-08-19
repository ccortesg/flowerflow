<?php

namespace App\Actions\Rubrics;

use App\Enums\RubricVersionStatus;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationRubricContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateRubricDraft
{
    public function __construct(
        private EnsureRubricAdministrator $ensureActor,
        private EvaluationRubricContract $contract,
        private AuditLogger $audit,
    ) {}

    public function execute(
        RubricVersion $rubric,
        User $actor,
        string $title,
        array $versionAttributes,
        array $criteria,
    ): RubricVersion {
        $this->ensureActor->execute($actor, 'manage evaluation rubrics');
        $this->contract->assertPayload($versionAttributes, $criteria);

        return DB::transaction(function () use ($rubric, $actor, $title, $versionAttributes, $criteria): RubricVersion {
            $locked = RubricVersion::query()->whereKey($rubric->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== RubricVersionStatus::Draft) {
                throw ValidationException::withMessages(['rubric' => 'Sólo una versión en borrador puede editarse.']);
            }

            $locked->forceFill([
                'title' => trim($title),
                ...$versionAttributes,
                'last_edited_by_user_id' => $actor->id,
            ])->save();

            foreach ($criteria as $criterionAttributes) {
                $criterion = RubricCriterion::query()
                    ->where('rubric_version_id', $locked->id)
                    ->where('code', $criterionAttributes['code'])
                    ->first() ?? new RubricCriterion;
                $criterion->forceFill([
                    'rubric_version_id' => $locked->id,
                    'code' => $criterionAttributes['code'],
                    'label' => $criterionAttributes['label'],
                    'description' => null,
                    'weight' => $criterionAttributes['weight'],
                    'min_score' => $criterionAttributes['min_score'],
                    'max_score' => $criterionAttributes['max_score'],
                    'score_step' => $criterionAttributes['score_step'],
                    'sort_order' => $criterionAttributes['sort_order'],
                ])->save();
            }

            $this->contract->assertPersisted($locked->refresh()->load('criteria'));
            $this->audit->record('rubric.draft_edited', $locked, $actor, [
                'competition_id' => $locked->competition_id,
                'version' => $locked->version,
                'status' => RubricVersionStatus::Draft->value,
            ]);

            return $locked->refresh()->load('criteria');
        });
    }
}
