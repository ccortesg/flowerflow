<?php

namespace App\Actions\Rubrics;

use App\Enums\RubricVersionStatus;
use App\Models\Competition;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationRubricContract;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateRubricDraft
{
    public function __construct(
        private EnsureRubricAdministrator $ensureActor,
        private EvaluationRubricContract $contract,
        private AuditLogger $audit,
    ) {}

    public function execute(
        User $actor,
        Competition $competition,
        int $version,
        string $title,
        array $versionAttributes,
        array $criteria,
    ): RubricVersion {
        $this->ensureActor->execute($actor, 'manage evaluation rubrics');
        $this->contract->assertPayload($versionAttributes, $criteria);

        try {
            return DB::transaction(function () use ($actor, $competition, $version, $title, $versionAttributes, $criteria): RubricVersion {
                Competition::query()->whereKey($competition->getKey())->lockForUpdate()->firstOrFail();

                if (RubricVersion::query()->where('competition_id', $competition->id)->where('version', $version)->exists()) {
                    throw ValidationException::withMessages(['version' => 'Ya existe esta versión para la convocatoria.']);
                }

                $rubric = new RubricVersion;
                $rubric->forceFill([
                    'competition_id' => $competition->id,
                    'version' => $version,
                    'title' => trim($title),
                    ...$versionAttributes,
                    'status' => RubricVersionStatus::Draft,
                    'created_by_user_id' => $actor->id,
                ])->save();

                foreach ($criteria as $criterionAttributes) {
                    $criterion = new RubricCriterion;
                    $criterion->forceFill([
                        'rubric_version_id' => $rubric->id,
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

                $this->contract->assertPersisted($rubric->load('criteria'));
                $this->audit->record('rubric.draft_created', $rubric, $actor, [
                    'competition_id' => $competition->id,
                    'version' => $version,
                    'status' => RubricVersionStatus::Draft->value,
                ]);

                return $rubric->refresh()->load('criteria');
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'version' => 'No fue posible crear la versión porque ya existe o viola el contrato de integridad.',
            ]);
        }
    }
}
