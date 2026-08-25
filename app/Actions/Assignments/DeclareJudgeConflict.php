<?php

namespace App\Actions\Assignments;

use App\Enums\EvaluationRevisionStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeConflictStatus;
use App\Enums\JudgeConflictType;
use App\Events\JudgeConflictDeclared;
use App\Models\EvaluationRevision;
use App\Models\JudgeAssignment;
use App\Models\JudgeConflict;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeclareJudgeConflict
{
    public function __construct(
        private EnsureActiveJudgeAssignmentActor $ensureActor,
        private AuditLogger $audit,
    ) {}

    public function execute(
        JudgeAssignment $assignment,
        User $actor,
        JudgeConflictType $type,
        ?string $explanation,
    ): JudgeConflict {
        $profile = $this->ensureActor->execute($actor);
        $normalizedExplanation = $type === JudgeConflictType::Other ? trim((string) $explanation) : null;

        if ($type === JudgeConflictType::Other && mb_strlen($normalizedExplanation) < 20) {
            throw ValidationException::withMessages(['explanation' => 'Explica el otro conflicto con al menos 20 caracteres.']);
        }

        return DB::transaction(function () use ($assignment, $profile, $actor, $type, $normalizedExplanation): JudgeConflict {
            $locked = JudgeAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            $existing = JudgeConflict::query()->where('judge_assignment_id', $locked->id)->lockForUpdate()->first();

            if ($locked->judge_profile_id !== $profile->id) {
                abort(404);
            }

            if ($existing) {
                if ($locked->status === JudgeAssignmentStatus::ConflictDeclared
                    && $existing->status === JudgeConflictStatus::Declared
                    && $existing->type === $type
                    && $existing->explanation === $normalizedExplanation) {
                    return $existing;
                }

                throw ValidationException::withMessages(['conflict' => 'La asignación ya tiene una declaración de conflicto.']);
            }

            if ($locked->status !== JudgeAssignmentStatus::Active) {
                throw ValidationException::withMessages(['conflict' => 'Sólo una asignación activa puede declarar conflicto.']);
            }

            if (EvaluationRevision::query()
                ->whereHas('evaluation', fn ($query) => $query->where('judge_assignment_id', $locked->id))
                ->where('status', EvaluationRevisionStatus::Submitted->value)
                ->exists()) {
                throw ValidationException::withMessages(['conflict' => 'No se puede declarar conflicto después de enviar una evaluación.']);
            }

            if (now('UTC')->greaterThan($locked->due_at)) {
                throw ValidationException::withMessages(['conflict' => 'El plazo de la asignación ya terminó.']);
            }

            DB::table('judge_assignments')->where('id', $locked->id)->update([
                'status' => JudgeAssignmentStatus::ConflictDeclared->value,
                'updated_at' => now('UTC'),
            ]);

            $conflict = new JudgeConflict;
            $conflict->forceFill([
                'judge_assignment_id' => $locked->id,
                'declared_by_judge_profile_id' => $profile->id,
                'type' => $type->value,
                'explanation' => $normalizedExplanation,
                'status' => JudgeConflictStatus::Declared->value,
                'declared_at' => now('UTC'),
            ])->save();

            $this->audit->record('assignment.conflict_declared', $conflict, $actor, [
                'assignment_id' => $locked->id,
                'judge_profile_id' => $profile->id,
                'conflict_type' => $type->value,
                'assignment_status' => JudgeAssignmentStatus::ConflictDeclared->value,
            ]);
            JudgeConflictDeclared::dispatch($conflict->id, $locked->id, $actor->id);

            return $conflict;
        }, 5);
    }
}
