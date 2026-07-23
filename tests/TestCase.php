<?php

namespace Tests;

use App\Actions\EnsureEligibilityReview;
use App\Models\Category;
use App\Models\EligibilityReview;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\FlowerFlowSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedFlowerFlow(): void
    {
        $this->seed(FlowerFlowSeeder::class);
    }

    protected function participant(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('participant');
        $user->profile()->create([
            'first_names' => 'Persona',
            'last_names' => 'Prueba',
            'mobile_e164' => '+526621234567',
            'whatsapp_opt_in' => false,
            'birth_date' => '1990-01-01',
            'neighborhood' => 'Centro',
            'adult_declared_at' => now('UTC'),
            'hermosillo_resident_declared_at' => now('UTC'),
        ]);

        return $user;
    }

    protected function reviewer(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('reviewer');

        return $user;
    }

    protected function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('admin');

        return $user;
    }

    /** @return array{User, Submission, EligibilityReview} */
    protected function submittedReview(bool $team = false): array
    {
        $participant = $this->participant();
        $category = Category::query()->firstOrFail();
        $teamModel = null;
        if ($team) {
            $teamModel = Team::query()->create([
                'owner_user_id' => $participant->id,
                'name' => 'Equipo sintético',
                'eligibility_declared_at' => now('UTC'),
            ]);
            $teamModel->members()->create([
                'full_name' => $participant->name,
                'email' => $participant->email,
                'is_representative' => true,
            ]);
            $teamModel->members()->create([
                'full_name' => 'Integrante Sintética',
                'email' => 'integrante@example.test',
                'is_representative' => false,
            ]);
        }

        $submission = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $participant->id,
            'team_id' => $teamModel?->id,
            'participation_type' => $team ? 'team' : 'individual',
            'title' => 'Propuesta sintética de admisibilidad',
            'summary' => 'Resumen exclusivamente sintético para pruebas.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética.',
            'status' => 'submitted',
            'folio' => 'HMO26-'.str_pad((string) fake()->unique()->numberBetween(1000, 999999), 6, '0', STR_PAD_LEFT),
            'submitted_at' => now('UTC'),
        ]);
        $version = $submission->versions()->create([
            'version' => 1,
            'snapshot' => [
                'schema_version' => 1,
                'submission' => [
                    'title' => $submission->title,
                    'summary' => $submission->summary,
                    'description_text' => $submission->description_text,
                    'participation_type' => $submission->participation_type,
                ],
            ],
            'created_at' => now('UTC'),
        ]);
        $review = app(EnsureEligibilityReview::class)->execute($submission, $version, $participant);

        return [$participant, $submission, $review];
    }
}
