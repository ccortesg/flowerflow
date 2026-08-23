<?php

namespace Tests\Support;

use App\Actions\Assignments\ActivateSubmissionCoverage;
use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Actions\Rubrics\ActivateRubricVersion;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\BlindReviewPackage;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
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
        app(ActivateRubricVersion::class)->execute($rubric, $admin, 'Activación sintética de rúbrica para M6.');
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

        app(ActivateSubmissionCoverage::class)->execute(
            $submission,
            $admin,
            'Cobertura sintética completa para evaluación M6.',
        );
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
