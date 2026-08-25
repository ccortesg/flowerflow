<?php

namespace Tests\Feature;

use App\Enums\EligibilityReviewStatus;
use App\Models\Category;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdmissibilityRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private const REVIEWER_PERMISSIONS = [
        'view admissibility reviews',
        'review admissibility',
        'request clarification',
        'decide admissibility',
        'view residency documents',
        'download residency documents',
    ];

    private const ADMIN_PERMISSIONS = [
        ...self::REVIEWER_PERMISSIONS,
        'manage admissibility reviews',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.admissibility_review' => true,
        ]);
        $this->seedFlowerFlow();
    }

    public function test_recovery_migration_provisions_missing_permissions_idempotently(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::ADMIN_PERMISSIONS)
            ->get()
            ->each->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame(0, Permission::query()->whereIn('name', self::ADMIN_PERMISSIONS)->count());

        $migration = require database_path('migrations/2026_08_25_120000_recover_admissibility_permissions.php');
        $migration->up();
        $migration->up();

        $this->assertSame(7, Permission::query()->whereIn('name', self::ADMIN_PERMISSIONS)->count());

        $reviewer = Role::findByName('reviewer');
        $admin = Role::findByName('admin');
        $participant = Role::findByName('participant');
        $judge = Role::findByName('judge');

        foreach (self::REVIEWER_PERMISSIONS as $permission) {
            $this->assertTrue($reviewer->hasPermissionTo($permission));
            $this->assertTrue($admin->hasPermissionTo($permission));
            $this->assertFalse($participant->hasPermissionTo($permission));
            $this->assertFalse($judge->hasPermissionTo($permission));
        }
        $this->assertFalse($reviewer->hasPermissionTo('manage admissibility reviews'));
        $this->assertTrue($admin->hasPermissionTo('manage admissibility reviews'));
    }

    public function test_proposal_action_opens_the_existing_review_without_mutating_it(): void
    {
        [, $submission, $review] = $this->submittedReview();
        $review->refresh();
        $eventCount = $review->events()->count();
        $original = $review->getAttributes();

        $this->actingAs($this->admin())->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee('Revisar admisibilidad')
            ->assertSee(route('panel.admissibility.show', $review), false);

        $this->actingAs($this->admin())->get(route('panel.admissibility.show', $review))->assertOk();

        $this->assertSame($eventCount, $review->events()->count());
        $this->assertSame($original, $review->fresh()->getAttributes());
        $this->assertSame('submitted', $submission->fresh()->status);
    }

    public function test_proposal_action_labels_follow_review_state_and_missing_reviews_are_visible(): void
    {
        [, , $review] = $this->submittedReview();
        $missing = $this->submittedReview()[1];
        $missing->eligibilityReview()->delete();

        foreach ([
            [EligibilityReviewStatus::Pending, 'Revisar admisibilidad'],
            [EligibilityReviewStatus::InReview, 'Continuar admisibilidad'],
            [EligibilityReviewStatus::ClarificationRequested, 'Continuar admisibilidad'],
            [EligibilityReviewStatus::Admitted, 'Ver admisión'],
            [EligibilityReviewStatus::NotAdmitted, 'Ver resolución'],
        ] as [$status, $label]) {
            $review->update(['status' => $status]);
            $this->actingAs($this->reviewer())->get(route('panel.submissions.index'))
                ->assertOk()
                ->assertSee($label)
                ->assertSee('Sin expediente');
        }
    }

    public function test_action_requires_flag_and_permission_and_is_never_available_for_drafts(): void
    {
        [, , $review] = $this->submittedReview();
        $draftOwner = $this->participant();
        $category = Category::query()->firstOrFail();
        $draft = Submission::query()->create([
            'competition_id' => $category->competition_id,
            'category_id' => $category->id,
            'user_id' => $draftOwner->id,
            'participation_type' => 'individual',
            'title' => 'Borrador sin acción de admisibilidad',
            'summary' => 'Resumen sintético.',
            'description_html' => '<p>Descripción sintética.</p>',
            'description_text' => 'Descripción sintética.',
            'status' => 'draft',
        ]);
        $reviewer = $this->reviewer();

        config(['flowerflow.flags.admissibility_review' => false]);
        $this->actingAs($reviewer)->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertDontSee(route('panel.admissibility.show', $review), false);

        config(['flowerflow.flags.admissibility_review' => true]);
        $this->actingAs($reviewer)->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee(route('panel.admissibility.show', $review), false)
            ->assertDontSee('Revisar admisibilidad de la propuesta '.$draft->title);

        Role::findByName('reviewer')->revokePermissionTo('view admissibility reviews');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($reviewer)->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertDontSee(route('panel.admissibility.show', $review), false);

        $this->actingAs($draftOwner)->get(route('panel.submissions.index'))->assertForbidden();
    }

    public function test_migration_down_removes_only_permissions_and_preserves_admissibility_evidence(): void
    {
        [, $submission, $review] = $this->submittedReview();
        $reviewId = $review->id;
        $eventCount = $review->events()->count();
        $migration = require database_path('migrations/2026_08_25_120000_recover_admissibility_permissions.php');

        $migration->down();

        $this->assertSame(0, Permission::query()->whereIn('name', self::ADMIN_PERMISSIONS)->count());
        $this->assertDatabaseHas('eligibility_reviews', [
            'id' => $reviewId,
            'submission_id' => $submission->id,
            'status' => EligibilityReviewStatus::Pending->value,
        ]);
        $this->assertSame($eventCount, $review->events()->count());
    }
}
