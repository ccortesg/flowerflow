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

/**
 * Produces the canonical v1/v2 catalog for fresh local/testing installations.
 * The historical class name is retained because seeders and M3 evidence refer to it.
 */
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
            $versions = RubricVersion::query()
                ->where('competition_id', $competition->id)
                ->with('criteria')
                ->orderBy('version')
                ->lockForUpdate()
                ->get();
            if ($versions->contains(fn (RubricVersion $rubric): bool => ! in_array($rubric->version, $this->contract->supportedVersions(), true))) {
                throw new RuntimeException('The canonical competition contains a rubric outside the immutable v1/v2 catalog.');
            }

            $historical = $versions->firstWhere('version', EvaluationRubricContract::INITIAL_VERSION)
                ?? $this->createDraft($competition, EvaluationRubricContract::INITIAL_VERSION);
            $this->contract->assertPersisted($historical->load('criteria'));

            $legal = $versions->firstWhere('version', EvaluationRubricContract::LEGAL_VERSION);
            if (! $legal) {
                $legal = $this->createDraft($competition, EvaluationRubricContract::LEGAL_VERSION);
            }
            $this->contract->assertPersisted($legal->load('criteria'));

            $active = RubricVersion::query()
                ->where('competition_id', $competition->id)
                ->where('status', RubricVersionStatus::Active)
                ->lockForUpdate()
                ->get();
            if ($active->count() > 1 || ($active->isNotEmpty() && $active->sole()->id !== $historical->id && $active->sole()->id !== $legal->id)) {
                throw new RuntimeException('The canonical active rubric is not deterministic.');
            }

            $now = now('UTC');
            if ($historical->status === RubricVersionStatus::Active) {
                DB::table('rubric_versions')->where('id', $historical->id)->update([
                    'status' => RubricVersionStatus::Superseded->value,
                    'active_slot' => null,
                    'superseded_at' => $now,
                    'superseded_by_user_id' => null,
                    'superseded_source' => 'migration',
                    'updated_at' => $now,
                ]);
            }

            if ($legal->status === RubricVersionStatus::Draft) {
                DB::table('rubric_versions')->where('id', $legal->id)->update([
                    'status' => RubricVersionStatus::Active->value,
                    'active_slot' => 1,
                    'activated_at' => $now,
                    'activated_by_user_id' => null,
                    'activation_source' => 'migration',
                    'activation_reason' => 'Activación técnica de la rúbrica legal v2 aprobada para M6A.',
                    'updated_at' => $now,
                ]);
            } elseif ($legal->status !== RubricVersionStatus::Active) {
                throw new RuntimeException('Legal rubric v2 is not activatable in the canonical seed state.');
            }

            $legal->refresh()->load('criteria');
            $this->audit->record('rubric.canonical_catalog_provisioned', $legal, metadata: [
                'competition_id' => $competition->id,
                'version' => EvaluationRubricContract::LEGAL_VERSION,
                'status' => RubricVersionStatus::Active->value,
                'activation_source' => 'migration',
            ]);

            return $legal;
        }, 3);
    }

    private function createDraft(Competition $competition, int $version): RubricVersion
    {
        $rubric = new RubricVersion;
        $rubric->forceFill([
            'competition_id' => $competition->id,
            'version' => $version,
            'title' => $this->contract->title($version),
            ...$this->contract->versionAttributes($version),
            'status' => RubricVersionStatus::Draft,
            'created_by_user_id' => null,
        ])->save();

        foreach ($this->contract->criteria($version) as $criterionAttributes) {
            $criterion = new RubricCriterion;
            $criterion->forceFill([
                'rubric_version_id' => $rubric->id,
                ...$criterionAttributes,
            ])->save();
        }

        return $rubric->refresh()->load('criteria');
    }
}
