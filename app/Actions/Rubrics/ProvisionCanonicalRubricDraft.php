<?php

namespace App\Actions\Rubrics;

use App\Enums\RubricVersionStatus;
use App\Models\Competition;
use App\Models\RubricCriterion;
use App\Models\RubricVersion;
use App\Services\AuditLogger;
use App\Services\EvaluationRubricContract;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ProvisionCanonicalRubricDraft
{
    public function __construct(
        private EvaluationRubricContract $contract,
        private AuditLogger $audit,
    ) {}

    public function execute(Competition $competition): RubricVersion
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Canonical rubric provisioning is restricted to local and testing environments.');
        }

        return DB::transaction(function () use ($competition): RubricVersion {
            Competition::query()->whereKey($competition->getKey())->lockForUpdate()->firstOrFail();
            $existing = RubricVersion::query()
                ->where('competition_id', $competition->id)
                ->where('version', EvaluationRubricContract::INITIAL_VERSION)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $this->assertInitialVersion($existing);

                return $existing->load('criteria');
            }

            $rubric = new RubricVersion;
            $rubric->forceFill([
                'competition_id' => $competition->id,
                'version' => EvaluationRubricContract::INITIAL_VERSION,
                'title' => EvaluationRubricContract::INITIAL_TITLE,
                ...$this->contract->versionAttributes(),
                'status' => RubricVersionStatus::Draft,
                'created_by_user_id' => null,
            ])->save();

            foreach ($this->contract->criteria() as $criterionAttributes) {
                $criterion = new RubricCriterion;
                $criterion->forceFill([
                    'rubric_version_id' => $rubric->id,
                    ...$criterionAttributes,
                ])->save();
            }

            $this->assertInitialVersion($rubric);
            $this->audit->record('rubric.draft_provisioned', $rubric, metadata: [
                'competition_id' => $competition->id,
                'version' => EvaluationRubricContract::INITIAL_VERSION,
                'status' => RubricVersionStatus::Draft->value,
            ]);

            return $rubric->refresh()->load('criteria');
        });
    }

    private function assertInitialVersion(RubricVersion $rubric): void
    {
        if ($rubric->title !== EvaluationRubricContract::INITIAL_TITLE) {
            throw new RuntimeException('Rubric version 1 diverges from its immutable internal title.');
        }

        $this->contract->assertPersisted($rubric->load('criteria'));
    }
}
