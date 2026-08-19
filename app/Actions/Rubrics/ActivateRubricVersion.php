<?php

namespace App\Actions\Rubrics;

use App\Enums\RubricVersionStatus;
use App\Models\Competition;
use App\Models\RubricVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\EvaluationRubricContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class ActivateRubricVersion
{
    public function __construct(
        private EnsureRubricAdministrator $ensureActor,
        private EvaluationRubricContract $contract,
        private AuditLogger $audit,
    ) {}

    public function execute(RubricVersion $rubric, User $actor, string $reason): RubricVersion
    {
        $this->ensureActor->execute($actor, 'manage evaluation rubrics');

        return DB::transaction(function () use ($rubric, $actor, $reason): RubricVersion {
            Competition::query()->whereKey($rubric->competition_id)->lockForUpdate()->firstOrFail();
            $versions = RubricVersion::query()
                ->where('competition_id', $rubric->competition_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $target = $versions->firstWhere('id', $rubric->id);

            if (! $target || $target->status !== RubricVersionStatus::Draft) {
                throw ValidationException::withMessages(['rubric' => 'Sólo una versión en borrador puede activarse.']);
            }

            try {
                $this->contract->assertPersisted($target->load('criteria'));
            } catch (LogicException) {
                throw ValidationException::withMessages(['rubric' => 'La versión no coincide con el contrato aprobado y no puede activarse.']);
            }

            $now = now('UTC');
            $previous = $versions->first(fn (RubricVersion $version) => $version->status === RubricVersionStatus::Active);

            if ($previous) {
                RubricVersion::query()->whereKey($previous->id)->update([
                    'status' => RubricVersionStatus::Superseded->value,
                    'active_slot' => null,
                    'superseded_at' => $now,
                    'superseded_by_user_id' => $actor->id,
                    'updated_at' => $now,
                ]);
                $previous->refresh();
                $this->audit->record('rubric.superseded', $previous, $actor, [
                    'competition_id' => $previous->competition_id,
                    'version' => $previous->version,
                    'replacement_version' => $target->version,
                    'status' => RubricVersionStatus::Superseded->value,
                ]);
            }

            RubricVersion::query()->whereKey($target->id)->update([
                'status' => RubricVersionStatus::Active->value,
                'active_slot' => 1,
                'activated_at' => $now,
                'activated_by_user_id' => $actor->id,
                'activation_reason' => trim($reason),
                'updated_at' => $now,
            ]);
            $target->refresh();
            $this->audit->record('rubric.activated', $target, $actor, [
                'competition_id' => $target->competition_id,
                'version' => $target->version,
                'previous_version' => $previous?->version,
                'status' => RubricVersionStatus::Active->value,
                'reason' => trim($reason),
            ]);

            return $target->load('criteria');
        });
    }
}
