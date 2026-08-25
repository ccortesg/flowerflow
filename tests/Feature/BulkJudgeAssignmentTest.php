<?php

namespace Tests\Feature;

use App\Actions\Assignments\CancelJudgeAssignment;
use App\Actions\BlindReview\GenerateBlindReviewPackageDraft;
use App\Enums\CommunicationType;
use App\Enums\EligibilityReviewStatus;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Models\AuditLog;
use App\Models\BlindReviewPackage;
use App\Models\CommunicationDelivery;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use App\Services\BulkJudgeAssignmentIntent;
use App\Services\CommunicationMessageRegistry;
use App\Services\EligibilityReviewWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkJudgeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.admissibility_review' => true,
            'flowerflow.flags.evaluation' => true,
            'flowerflow.flags.bulk_judge_assignment' => true,
            'flowerflow.flags.communication_ledger' => true,
            'flowerflow.judge_notifications.assignment_enabled' => true,
            'flowerflow.bulk_judge_assignment.limit' => 20,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_exact_admin_can_open_from_both_modules_and_get_is_read_only(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $submission = $this->bulkSubmission();
        $session = ['auth.password_confirmed_at' => now()->timestamp];

        $this->actingAs($admin)->withSession($session)
            ->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee('Asignar varias propuestas');
        $this->actingAs($admin)->withSession($session)
            ->get(route('panel.assignments.index'))
            ->assertOk()
            ->assertSee('Asignar varias propuestas');
        $before = $this->businessCounts();
        $this->actingAs($admin)->withSession($session)
            ->get(route('panel.assignments.bulk.create', ['judge_profile' => $judge->public_id]))
            ->assertOk()
            ->assertSee($submission->public_id)
            ->assertSee('Revisar operación');
        $this->assertSame($before, $this->businessCounts());

        foreach ([$this->participant(), $this->reviewer()] as $forbidden) {
            $this->actingAs($forbidden)->withSession($session)
                ->get(route('panel.assignments.bulk.create'))
                ->assertForbidden();
        }
        $multiRole = $this->adminWithPassword();
        $multiRole->assignRole('reviewer');
        $this->actingAs($multiRole)->withSession($session)
            ->get(route('panel.assignments.bulk.create'))
            ->assertForbidden();
        config(['flowerflow.flags.bulk_judge_assignment' => false]);
        $this->actingAs($admin)->withSession($session)
            ->get(route('panel.assignments.bulk.create'))
            ->assertNotFound();
        config(['flowerflow.flags.bulk_judge_assignment' => true]);
        $incompleteAdmin = $this->adminWithPassword();
        Role::findByName('admin')->revokePermissionTo('manage blind review packages');
        $this->actingAs($incompleteAdmin)->withSession($session)
            ->get(route('panel.assignments.bulk.create'))
            ->assertForbidden();
    }

    public function test_preflight_is_read_only_and_execution_admits_packages_assigns_and_queues_one_consolidated_email(): void
    {
        Queue::fake();
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $first = $this->bulkSubmission('Primera propuesta canario secreto');
        $second = $this->bulkSubmission('Segunda propuesta canario secreto');
        $payload = $this->reviewPayload($judge, [$first, $second], true);
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $before = $this->businessCounts();

        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $payload)
            ->assertOk()
            ->assertViewIs('panel.assignments.bulk.review');
        $this->assertSame($before, $this->businessCounts());
        $intent = $review->viewData('intent');

        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertRedirect(route('panel.assignments.bulk.result'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, DB::table('eligibility_reviews')->where('status', 'admitted')->count());
        $this->assertSame(2, DB::table('eligibility_reviews')->where('participant_reason', $payload['participant_reason'])->count());
        $this->assertDatabaseCount('blind_review_packages', 2);
        $this->assertSame(2, BlindReviewPackage::query()->where('status', 'active')->count());
        $this->assertDatabaseCount('judge_assignments', 2);
        $this->assertTrue(JudgeAssignment::query()->get()->every(fn (JudgeAssignment $assignment): bool => $assignment->judge_profile_id === $judge->id
            && $assignment->assigned_by_user_id === $admin->id
            && $assignment->assignment_reason === $payload['assignment_reason']));

        $bulkDelivery = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::JudgeAssignmentBulkCreated->value)
            ->sole();
        $this->assertSame($judge->id, $bulkDelivery->related_id);
        $this->assertSame(1, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::JudgeAssignmentBulkCreated->value)->count());
        $prepared = app(CommunicationMessageRegistry::class)->prepare($bulkDelivery);
        $html = (string) $prepared['message']->toMail($judge->user)->render();
        $this->assertStringContainsString('2', $html);
        $this->assertStringContainsString('Ver mis asignaciones', $html);
        $this->assertStringNotContainsString($first->title, $html);
        $this->assertStringNotContainsString($second->title, $html);
        $this->assertStringNotContainsString($first->user->email, $html);
        $this->assertStringNotContainsString($judge->user->name, $html);
        $this->assertStringNotContainsString($payload['participant_reason'], $html);
        $this->assertSame(1, AuditLog::query()->where('action', 'assignment.bulk_completed')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'assignment.bulk_notification_requested')->count());
    }

    public function test_item_failure_rolls_back_only_that_proposal_and_preserves_other_successes(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $first = $this->bulkSubmission();
        $second = $this->bulkSubmission();
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$first, $second], false))
            ->assertOk();
        $intent = $review->viewData('intent');

        $second->eligibilityReview()->update([
            'status' => EligibilityReviewStatus::NotAdmitted,
            'participant_reason' => 'Resolución sintética posterior al preflight.',
            'resolved_by_user_id' => $admin->id,
            'resolved_at' => now('UTC'),
        ]);
        $response = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $result = $response->getSession()->get('bulk_assignment_result');

        $this->assertSame(1, $result['created_count']);
        $this->assertSame(1, $result['failed_count']);
        $this->assertSame('selection_state_changed', collect($result['items'])->firstWhere('submission_public_id', $second->public_id)['reason_code']);
        $this->assertSame(EligibilityReviewStatus::Admitted, $first->eligibilityReview->fresh()->status);
        $this->assertSame(EligibilityReviewStatus::NotAdmitted, $second->eligibilityReview->fresh()->status);
        $this->assertDatabaseHas('judge_assignments', ['submission_version_id' => $first->versions()->sole()->id]);
        $this->assertDatabaseMissing('judge_assignments', ['submission_version_id' => $second->versions()->sole()->id]);
        $this->assertDatabaseHas('blind_review_packages', ['submission_version_id' => $first->versions()->sole()->id, 'status' => 'active']);
        $this->assertDatabaseMissing('blind_review_packages', ['submission_version_id' => $second->versions()->sole()->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'assignment.bulk_partially_completed']);
    }

    public function test_failure_after_admission_rolls_back_admission_package_assignment_and_their_evidence(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $submission = $this->bulkSubmission();
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$submission], false))
            ->assertOk();
        $intent = $review->viewData('intent');
        DB::table('submission_versions')->where('submission_id', $submission->id)->update([
            'snapshot' => json_encode(['schema_version' => 999], JSON_THROW_ON_ERROR),
        ]);
        $response = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $result = $response->getSession()->get('bulk_assignment_result');

        $this->assertSame(1, $result['failed_count']);
        $this->assertSame('business_validation_rejected', $result['items'][0]['reason_code']);
        $this->assertSame(EligibilityReviewStatus::Pending, $submission->eligibilityReview->fresh()->status);
        $this->assertDatabaseMissing('blind_review_packages', ['submission_version_id' => $submission->versions()->sole()->id]);
        $this->assertDatabaseMissing('judge_assignments', ['submission_version_id' => $submission->versions()->sole()->id]);
        $this->assertSame(0, AuditLog::query()->where('action', 'admissibility.admitted')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'blind_review_package.draft_generated')->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'assignment.created')->count());
    }

    public function test_duplicate_execution_and_existing_assignment_are_idempotent(): void
    {
        Queue::fake();
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $submission = $this->bulkSubmission();
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$submission], true))
            ->assertOk();
        $intent = $review->viewData('intent');

        $operationId = app(BulkJudgeAssignmentIntent::class)->decode($intent, $admin)['operation_id'];
        $concurrentLock = Cache::lock('flowerflow:bulk-judge-assignment:'.$operationId, 60);
        $this->assertTrue($concurrentLock->get());
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertSessionHasErrors('intent');
        $this->assertDatabaseCount('judge_assignments', 0);
        $concurrentLock->release();

        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertSessionHasErrors('intent');

        $this->assertDatabaseCount('judge_assignments', 1);
        $this->assertDatabaseCount('blind_review_packages', 1);
        $this->assertSame(1, AuditLog::query()->where('action', 'admissibility.admitted')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'assignment.bulk_requested')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'assignment.bulk_completed')->count());
        $this->assertSame(1, CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::JudgeAssignmentBulkCreated->value)->count());

        $next = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$submission], false))
            ->assertOk();
        $nextIntent = $next->viewData('intent');
        $response = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($nextIntent))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $result = $response->getSession()->get('bulk_assignment_result');
        $this->assertSame(0, $result['created_count']);
        $this->assertSame(1, $result['existing_count']);
        $this->assertDatabaseCount('judge_assignments', 1);
    }

    public function test_limits_strict_fields_tampering_and_expiration_fail_closed(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $submissions = collect(range(1, 21))->map(fn (): Submission => $this->bulkSubmission());
        $session = ['auth.password_confirmed_at' => now()->timestamp];

        $validTwenty = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, $submissions->take(20)->all(), false))
            ->assertOk();
        $this->assertNotEmpty($validTwenty->viewData('intent'));
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, $submissions->all(), false))
            ->assertSessionHasErrors('submissions');
        $empty = $this->reviewPayload($judge, [], false);
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $empty)
            ->assertSessionHasErrors('submissions');
        $duplicate = $this->reviewPayload($judge, [$submissions->first(), $submissions->first()], false);
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $duplicate)
            ->assertSessionHasErrors('submissions.1');
        $hostile = $this->reviewPayload($judge, [$submissions->first()], false);
        $hostile['rubric_version_id'] = 1;
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $hostile)
            ->assertSessionHasErrors('request');

        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$submissions->first()], false))
            ->assertOk();
        $intent = $review->viewData('intent');
        $otherAdmin = $this->adminWithPassword();
        $this->actingAs($otherAdmin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertSessionHasErrors('intent');
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent.'alterado'))
            ->assertSessionHasErrors('intent');
        $this->travel(16)->minutes();
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($intent))
            ->assertSessionHasErrors('intent');
        $this->assertDatabaseCount('judge_assignments', 0);
        $this->assertDatabaseCount('blind_review_packages', 0);
    }

    public function test_twenty_proposals_execute_in_one_operation_without_silent_truncation(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $submissions = collect(range(1, 20))->map(fn (): Submission => $this->bulkSubmission());
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, $submissions->all(), false))
            ->assertOk();

        $response = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($review->viewData('intent')))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $result = $response->getSession()->get('bulk_assignment_result');

        $this->assertSame(20, $result['selected_count']);
        $this->assertSame(20, $result['created_count']);
        $this->assertSame(0, $result['failed_count']);
        $this->assertDatabaseCount('blind_review_packages', 20);
        $this->assertDatabaseCount('judge_assignments', 20);
    }

    public function test_maximum_size_file_workload_for_twenty_proposals_is_measurable_on_demand(): void
    {
        if (! filter_var(env('FLOWERFLOW_RUN_BULK_PERFORMANCE_TEST', false), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Set FLOWERFLOW_RUN_BULK_PERFORMANCE_TEST=true to execute the 200 MiB local benchmark.');
        }

        Storage::fake('local');
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $prefix = "%PDF-1.4\n";
        $suffix = "\n%%EOF";
        $contents = $prefix.str_repeat('A', (10 * 1024 * 1024) - strlen($prefix) - strlen($suffix)).$suffix;
        $submissions = collect(range(1, 20))->map(function () use ($contents): Submission {
            $submission = $this->bulkSubmission();
            $file = $this->attachMaximumSizeFile($submission, $contents);
            $version = $submission->versions()->sole();
            $snapshot = $version->snapshot;
            $snapshot['files'] = [[
                'public_id' => $file->public_id,
                'kind' => $file->kind,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'extension' => $file->extension,
                'size_bytes' => (int) $file->size_bytes,
                'sha256' => $file->sha256,
            ]];
            DB::table('submission_versions')->where('id', $version->id)->update([
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            ]);

            return $submission->fresh(['user', 'category', 'versions', 'eligibilityReview']);
        });
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, $submissions->all(), false))
            ->assertOk();

        $startedAt = hrtime(true);
        $response = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($review->viewData('intent')))
            ->assertRedirect(route('panel.assignments.bulk.result'));
        $durationSeconds = (hrtime(true) - $startedAt) / 1_000_000_000;
        $result = $response->getSession()->get('bulk_assignment_result');

        fwrite(STDERR, sprintf("\nBulk maximum-file benchmark: %.3f seconds for 20 proposals / 200 MiB.\n", $durationSeconds));
        $this->assertSame(20, $result['created_count']);
        $this->assertSame(0, $result['failed_count']);
    }

    public function test_bulk_delivery_revalidates_each_assignment_and_cancels_when_none_remain(): void
    {
        Queue::fake();
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $first = $this->bulkSubmission();
        $second = $this->bulkSubmission();
        $session = ['auth.password_confirmed_at' => now()->timestamp];
        $review = $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, [$first, $second], true))
            ->assertOk();
        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.store'), $this->confirmationPayload($review->viewData('intent')))
            ->assertRedirect(route('panel.assignments.bulk.result'));

        $delivery = CommunicationDelivery::query()
            ->where('notification_type', CommunicationType::JudgeAssignmentBulkCreated->value)
            ->sole();
        $assignments = JudgeAssignment::query()->orderBy('id')->get();
        app(CancelJudgeAssignment::class)->execute(
            $assignments->first(),
            $admin,
            'Cancelación sintética para revalidar un elemento consolidado.',
        );
        $prepared = app(CommunicationMessageRegistry::class)->prepare($delivery);
        $this->assertCount(1, $prepared['message']->assignmentIds);

        app(CancelJudgeAssignment::class)->execute(
            $assignments->last(),
            $admin,
            'Segunda cancelación sintética para invalidar todo el consolidado.',
        );
        $this->expectException(CommunicationCancelledException::class);
        app(CommunicationMessageRegistry::class)->prepare($delivery);
    }

    public function test_missing_or_blocked_prerequisites_are_visible_and_rejected_before_intent(): void
    {
        $admin = $this->adminWithPassword();
        $judge = $this->activeJudge($admin);
        $missingReview = $this->bulkSubmission();
        $missingReview->eligibilityReview()->delete();
        $missingVersion = $this->bulkSubmission();
        $missingVersion->eligibilityReview()->delete();
        DB::table('submission_versions')->where('submission_id', $missingVersion->id)->delete();
        $notAdmitted = $this->bulkSubmission();
        $notAdmitted->eligibilityReview()->update(['status' => EligibilityReviewStatus::NotAdmitted]);
        $clarification = $this->bulkSubmission();
        app(EligibilityReviewWorkflow::class)->requestClarification(
            $clarification->eligibilityReview,
            $admin,
            'Aclaración sintética abierta que debe bloquear el lote.',
            null,
        );
        $residency = $this->bulkSubmission();
        app(EligibilityReviewWorkflow::class)->requestResidency(
            $residency->eligibilityReview,
            $admin,
            'representative',
            null,
            null,
            null,
        );
        $invalidatedPackage = $this->bulkSubmission();
        $invalidatedPackage->eligibilityReview()->update([
            'status' => EligibilityReviewStatus::Admitted,
            'participant_reason' => 'Admisión sintética para crear paquete invalidado.',
            'resolved_at' => now('UTC'),
        ]);
        $package = app(GenerateBlindReviewPackageDraft::class)->execute(
            $invalidatedPackage,
            $admin,
            'Generación sintética de un paquete que será invalidado.',
        );
        DB::table('blind_review_packages')->where('id', $package->id)->update([
            'status' => 'invalidated',
            'invalidated_by_user_id' => $admin->id,
            'invalidation_reason' => 'Invalidación sintética para comprobar el bloqueo del lote.',
            'invalidated_at' => now('UTC'),
        ]);
        $submissions = [$missingReview, $missingVersion, $notAdmitted, $clarification, $residency, $invalidatedPackage];
        $session = ['auth.password_confirmed_at' => now()->timestamp];

        $page = $this->actingAs($admin)->withSession($session)
            ->get(route('panel.assignments.bulk.create', ['judge_profile' => $judge->public_id]))
            ->assertOk();
        foreach ([
            'No existe expediente',
            'No existe una versión inmutable',
            'no admitido',
            'aclaraciones abiertas',
            'residencia pendientes',
            'paquete ciego está invalidado',
        ] as $message) {
            $page->assertSee($message);
        }

        $this->actingAs($admin)->withSession($session)
            ->post(route('panel.assignments.bulk.review'), $this->reviewPayload($judge, $submissions, false))
            ->assertSessionHasErrors('submissions');
        $this->assertDatabaseCount('judge_assignments', 0);
    }

    private function bulkSubmission(string $title = 'Propuesta sintética para asignación simultánea'): Submission
    {
        [, $submission] = $this->submittedReview();
        $submission->update(['title' => $title]);
        $version = $submission->versions()->sole();
        DB::table('submission_versions')->where('id', $version->id)->update(['snapshot' => json_encode([
            'schema_version' => 1,
            'category' => ['slug' => $submission->category->slug, 'name' => $submission->category->name],
            'submission' => [
                'participation_type' => $submission->participation_type,
                'title' => $title,
                'summary' => $submission->summary,
                'description_html' => $submission->description_html,
                'description_text' => $submission->description_text,
            ],
            'external_links' => [],
            'files' => [],
        ], JSON_THROW_ON_ERROR)]);

        return $submission->fresh(['user', 'category', 'versions', 'eligibilityReview']);
    }

    private function activeJudge(User $creator): JudgeProfile
    {
        $user = User::factory()->create([
            'name' => 'Juez de asignación simultánea',
            'email' => fake()->unique()->numerify('bulk-judge-######@example.test'),
            'email_verified_at' => now('UTC'),
        ]);
        $user->assignRole('judge');
        $profile = new JudgeProfile;
        $profile->forceFill([
            'user_id' => $user->id,
            'assignment_role' => JudgeAssignmentRole::Primary,
            'status' => JudgeProfileStatus::Active,
            'max_active_assignments' => null,
            'created_by_user_id' => $creator->id,
            'password_initialized_at' => now('UTC'),
            'activated_at' => now('UTC'),
        ])->save();

        return $profile->setRelation('user', $user);
    }

    private function attachMaximumSizeFile(Submission $submission, string $contents): SubmissionFile
    {
        $storedName = 'maximum-'.$submission->public_id.'.pdf';
        $path = 'bulk-performance/'.$submission->public_id.'/'.$storedName;
        Storage::disk('local')->put($path, $contents);

        return SubmissionFile::query()->create([
            'submission_id' => $submission->id,
            'actor_user_id' => $submission->user_id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'anexo-maximo.pdf',
            'stored_name' => $storedName,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
        ]);
    }

    private function adminWithPassword(): User
    {
        return $this->admin([
            'email' => fake()->unique()->numerify('bulk-admin-######@example.test'),
            'password' => Hash::make('AdminPass1!'),
        ]);
    }

    /** @param list<Submission> $submissions */
    private function reviewPayload(JudgeProfile $judge, array $submissions, bool $notify): array
    {
        return [
            'judge_profile' => $judge->public_id,
            'submissions' => collect($submissions)->pluck('public_id')->all(),
            'participant_reason' => 'La propuesta cumple los requisitos de admisibilidad revisados por la administración.',
            'internal_notes' => 'Nota interna sintética compartida para comprobar el lote.',
            'package_reason' => 'Generación y activación sintética del paquete ciego para el lote.',
            'assignment_reason' => 'Asignación administrativa sintética mediante el asistente de lote.',
            'notify_judge' => $notify ? 1 : 0,
            'current_password' => 'AdminPass1!',
        ];
    }

    private function confirmationPayload(string $intent): array
    {
        return [
            'intent' => $intent,
            'confirm_admission' => 1,
            'confirm_packages' => 1,
            'confirm_assignment' => 1,
        ];
    }

    private function businessCounts(): array
    {
        return [
            DB::table('eligibility_review_events')->count(),
            DB::table('blind_review_packages')->count(),
            DB::table('judge_assignments')->count(),
            DB::table('audit_logs')->count(),
            DB::table('communication_deliveries')->count(),
        ];
    }
}
