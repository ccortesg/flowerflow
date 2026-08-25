<?php

namespace Tests\Support;

use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeProfileStatus;
use App\Models\BlindReviewPackage;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait CreatesEvaluationScenario
{
    /** @return array{User,Collection<int,User>,Collection<int,User>,Submission,SubmissionVersion,BlindReviewPackage} */
    protected function createEvaluationScenario(): array
    {
        $admin = $this->admin([
            'email' => 'admin-m6-'.fake()->unique()->numerify('######').'@example.test',
            'password' => Hash::make('AdminPass1!'),
        ]);
        $primaries = collect(range(1, 4))->map(
            fn (int $number): User => $this->createActiveEvaluationJudge($admin, JudgeAssignmentRole::Primary, $number)
        );
        $substitutes = collect(range(5, 6))->map(
            fn (int $number): User => $this->createActiveEvaluationJudge($admin, JudgeAssignmentRole::Substitute, $number)
        );

        $rubric = RubricVersion::query()->where('version', 1)->firstOrFail();
        DB::table('rubric_versions')->where('id', $rubric->id)->update([
            'status' => 'superseded',
            'activated_at' => now('UTC'),
            'activated_by_user_id' => null,
            'activation_source' => 'migration',
            'activation_reason' => 'Activación histórica sintética para compatibilidad M6.',
            'superseded_at' => now('UTC'),
            'superseded_by_user_id' => null,
            'superseded_source' => 'migration',
        ]);
        [, $submission, $review] = $this->submittedReview();
        $review->update([
            'status' => EligibilityReviewStatus::Admitted,
            'resolved_at' => now('UTC'),
            'participant_reason' => 'Admisibilidad sintética aprobada para M6.',
        ]);
        $version = $submission->versions()->firstOrFail();
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode([
            'schema_version' => 1,
            'category' => [
                'slug' => $submission->category->slug,
                'name' => $submission->category->name,
            ],
            'submission' => [
                'participation_type' => 'individual',
                'title' => 'Proyecto sintético para evaluación M6',
                'summary' => 'Resumen sintético para evaluación en borrador.',
                'description_html' => '<p>Descripción sintética para evaluación.</p>',
                'description_text' => 'Descripción sintética para evaluación.',
            ],
            'external_links' => [],
            'files' => [],
        ], JSON_THROW_ON_ERROR)]);
        $version = $version->fresh();

        foreach ($primaries as $judge) {
            $assignment = new JudgeAssignment;
            $assignment->forceFill([
                'competition_id' => $submission->competition_id,
                'submission_version_id' => $version->id,
                'judge_profile_id' => $judge->judgeProfile->id,
                'rubric_version_id' => $rubric->id,
                'type' => JudgeAssignmentType::Initial,
                'status' => JudgeAssignmentStatus::Active,
                'current_slot' => 1,
                'due_at' => CarbonImmutable::parse(config('flowerflow.evaluation_close_at'), config('flowerflow.timezone'))->utc(),
                'assigned_by_user_id' => $admin->id,
                'assignment_reason' => 'Asignación histórica sintética fijada a rúbrica v1.',
                'assigned_at' => now('UTC'),
            ])->save();
        }
        app(GenerateBlindReviewPackageDraft::class)->execute(
            $submission,
            $admin,
            'Generación administrativa sintética del paquete para M6.',
        );
        $package = app(ActivateBlindReviewPackage::class)->execute(
            $submission,
            $admin,
            'Activación administrativa sintética del paquete para M6.',
        );

        return [$admin, $primaries, $substitutes, $submission->fresh('category'), $version, $package];
    }

    protected function createActiveEvaluationJudge(User $creator, JudgeAssignmentRole $role, int $number): User
    {
        $user = User::factory()->create([
            'email' => "judge-m6-{$number}-".fake()->unique()->numerify('######').'@example.test',
        ]);
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => $role->value,
            'status' => JudgeProfileStatus::Active->value,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $user->setRelation('judgeProfile', $profile);
    }
}
