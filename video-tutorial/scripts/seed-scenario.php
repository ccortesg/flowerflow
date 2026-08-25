<?php

declare(strict_types=1);

use App\Actions\BlindReview\ActivateBlindReviewPackage;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeProfileStatus;
use App\Enums\RubricVersionStatus;
use App\Models\Category;
use App\Models\EligibilityReview;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\RubricVersion;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing')
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'flowerflow_testing'
    || config('database.connections.mysql.username') !== 'flowerflow_testing_user'
    || DB::selectOne('SELECT DATABASE() AS database_name')->database_name !== 'flowerflow_testing') {
    fwrite(STDERR, "Guard de base de datos rechazado.\n");
    exit(2);
}

config([
    'flowerflow.flags.evaluation' => true,
    'flowerflow.flags.evaluation_finalization' => true,
    'flowerflow.flags.communication_ledger' => false,
    'flowerflow.flags.evaluation_notifications' => false,
    'flowerflow.timezone' => 'America/Hermosillo',
    'flowerflow.evaluation_close_at' => '2026-08-27 23:59:59',
]);
CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-25 10:00:00', 'America/Hermosillo'));

$runtimeDirectory = dirname(__DIR__).'/.runtime';
if (! is_dir($runtimeDirectory) && ! mkdir($runtimeDirectory, 0700, true) && ! is_dir($runtimeDirectory)) {
    throw new RuntimeException('No se pudo crear el runtime privado.');
}

$scenarioPath = $runtimeDirectory.'/scenario.json';
$password = Str::password(24, true, true, true, false);

[$judge, $assignment] = DB::transaction(function () use ($password): array {
    $admin = User::factory()->create([
        'name' => 'Administración Tutorial',
        'email' => 'admin.tutorial@example.test',
        'email_verified_at' => now('UTC'),
        'password' => Hash::make(Str::random(48)),
    ]);
    $admin->assignRole('admin');

    $judge = User::factory()->create([
        'name' => 'Carlos Cortés',
        'email' => 'carlos.cortes.tutorial@example.test',
        'email_verified_at' => now('UTC'),
        'password' => Hash::make($password),
    ]);
    $judge->assignRole('judge');

    $judgeProfile = new JudgeProfile;
    $judgeProfile->forceFill([
        'user_id' => $judge->id,
        'assignment_role' => JudgeAssignmentRole::Primary->value,
        'status' => JudgeProfileStatus::Active->value,
        'max_active_assignments' => null,
        'created_by_user_id' => $admin->id,
        'password_initialized_at' => now('UTC'),
        'activated_at' => now('UTC'),
    ])->save();

    $participant = User::factory()->create([
        'name' => 'Participante Sintético',
        'email' => 'participante.tutorial@example.test',
        'email_verified_at' => now('UTC'),
        'password' => Hash::make(Str::random(48)),
    ]);
    $participant->assignRole('participant');

    $category = Category::query()->where('slug', 'hermosillo-florece')->firstOrFail();
    $submission = Submission::query()->create([
        'competition_id' => $category->competition_id,
        'category_id' => $category->id,
        'user_id' => $participant->id,
        'team_id' => null,
        'participation_type' => 'individual',
        'title' => 'Corredores de sombra para espacios públicos',
        'summary' => 'Red de sombra, vegetación nativa y puntos de descanso para recorridos peatonales de barrio.',
        'description_html' => '<p>La propuesta plantea corredores peatonales con árboles nativos, estructuras de sombra y estaciones de descanso.</p><p>La implementación se organiza por etapas y utiliza indicadores públicos de cobertura, uso y supervivencia del arbolado.</p>',
        'description_text' => 'La propuesta plantea corredores peatonales con árboles nativos, estructuras de sombra y estaciones de descanso. La implementación se organiza por etapas y utiliza indicadores públicos de cobertura, uso y supervivencia del arbolado.',
        'status' => 'submitted',
        'folio' => 'HMO26-TUTORIAL',
        'submitted_at' => now('UTC'),
    ]);

    $snapshot = [
        'schema_version' => 1,
        'category' => ['slug' => $category->slug, 'name' => $category->name],
        'submission' => [
            'participation_type' => 'individual',
            'title' => $submission->title,
            'summary' => $submission->summary,
            'description_html' => $submission->description_html,
            'description_text' => $submission->description_text,
        ],
        'external_links' => [
            ['kind' => 'youtube', 'url' => 'https://www.youtube.com/watch?v=DEMO-FLOWER-FLOW', 'normalized_host' => 'www.youtube.com'],
            ['kind' => 'public_folder', 'url' => 'https://drive.google.com/drive/folders/DEMO-SINTETICO', 'normalized_host' => 'drive.google.com'],
        ],
        'files' => [],
    ];

    $version = new SubmissionVersion;
    $version->forceFill([
        'submission_id' => $submission->id,
        'version' => 1,
        'snapshot' => $snapshot,
        'created_at' => now('UTC'),
    ])->save();

    $review = new EligibilityReview;
    $review->forceFill([
        'submission_id' => $submission->id,
        'submission_version_id' => $version->id,
        'reviewer_user_id' => $admin->id,
        'resolved_by_user_id' => $admin->id,
        'status' => EligibilityReviewStatus::Admitted->value,
        'participant_reason' => 'La propuesta sintética cumple los criterios de admisibilidad del escenario tutorial.',
        'internal_notes' => null,
        'started_at' => now('UTC'),
        'resolved_at' => now('UTC'),
    ])->save();

    app(GenerateBlindReviewPackageDraft::class)->execute(
        $submission,
        $admin,
        'Generación sintética y aislada del paquete ciego para el video tutorial.'
    );
    app(ActivateBlindReviewPackage::class)->execute(
        $submission,
        $admin,
        'Activación sintética y aislada del paquete ciego para el video tutorial.'
    );

    $rubric = RubricVersion::query()
        ->where('competition_id', $category->competition_id)
        ->where('status', RubricVersionStatus::Active->value)
        ->where('active_slot', 1)
        ->with('criteria')
        ->sole();
    if ($rubric->version !== 2 || $rubric->criteria->count() !== 4) {
        throw new RuntimeException('La rúbrica v2 activa no coincide con el contrato del tutorial.');
    }

    $assignment = new JudgeAssignment;
    $assignment->forceFill([
        'competition_id' => $category->competition_id,
        'submission_version_id' => $version->id,
        'judge_profile_id' => $judgeProfile->id,
        'rubric_version_id' => $rubric->id,
        'type' => JudgeAssignmentType::Initial->value,
        'status' => JudgeAssignmentStatus::Active->value,
        'current_slot' => 1,
        'due_at' => CarbonImmutable::parse('2026-08-27 23:59:59', 'America/Hermosillo')->utc(),
        'assigned_by_user_id' => $admin->id,
        'assignment_reason' => 'Asignación manual sintética para demostrar el flujo completo del tutorial.',
        'assigned_at' => now('UTC'),
    ])->save();

    return [$judge, $assignment];
}, 5);

$scenario = [
    'email' => $judge->email,
    'password' => $password,
    'judge_name' => $judge->name,
    'assignment_public_id' => $assignment->public_id,
    'expected_total' => '82.50',
];
file_put_contents($scenarioPath, json_encode($scenario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
chmod($scenarioPath, 0600);

fwrite(STDOUT, "Escenario sintético listo: 1 juez, 1 asignación, 0 evaluaciones.\n");
