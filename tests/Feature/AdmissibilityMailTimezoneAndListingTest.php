<?php

namespace Tests\Feature;

use App\Enums\EligibilityReviewStatus;
use App\Mail\AdmissibilityUpdate;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AdmissibilityMailTimezoneAndListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.admissibility_review' => true, 'flowerflow.flags.panel' => true]);
        Storage::fake('residency');
        Storage::fake('clarifications');
        $this->seedFlowerFlow();
    }

    public function test_all_transactional_mail_variants_are_spanish_branded_and_hide_sensitive_data(): void
    {
        [, , $review] = $this->submittedReview();
        $review->update(['internal_notes' => 'SECRETO_INTERNO_SINTETICO']);

        foreach (['clarification_requested', 'residency_requested', 'response_received', 'admitted', 'not_admitted'] as $kind) {
            $mail = new AdmissibilityUpdate($review, $kind);
            $html = $mail->render();
            $this->assertStringContainsString('Flower Flow', $html);
            $this->assertStringContainsString('Florece Hermosillo', $html);
            $this->assertStringContainsString($review->submission->folio, $html);
            $this->assertStringNotContainsString('SECRETO_INTERNO_SINTETICO', $html);
            $this->assertStringContainsString('no incluye documentos', $html);
            $this->assertSame('database', $mail->connection);
            $this->assertSame('default', $mail->queue);
            $this->assertSame(4, $mail->tries);
            $this->assertInstanceOf(ShouldQueueAfterCommit::class, $mail);
            $this->assertInstanceOf(ShouldBeEncrypted::class, $mail);
        }
    }

    public function test_smtp_dispatch_failure_does_not_rollback_decision_or_return_500(): void
    {
        [, , $review] = $this->submittedReview();
        $reviewer = $this->reviewer();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Falla SMTP sintética'));

        $this->actingAs($reviewer)->post(route('panel.admissibility.decide', $review), [
            'decision' => 'not_admitted',
            'participant_reason' => 'El archivo principal no fue susceptible de abrirse.',
            'confirm_resolution' => '1',
        ])->assertRedirect()->assertSessionHas('warning');

        $this->assertSame(EligibilityReviewStatus::NotAdmitted, $review->fresh()->status);
        $this->assertDatabaseHas('eligibility_review_events', ['event' => 'review_not_admitted']);
    }

    public function test_listing_filters_paginate_and_do_not_lazy_load_relations(): void
    {
        $reviewer = $this->reviewer(['name' => 'Revisora Filtro']);
        for ($index = 0; $index < 27; $index++) {
            [, , $review] = $this->submittedReview();
            $review->update([
                'reviewer_user_id' => $index % 2 === 0 ? $reviewer->id : null,
                'status' => $index === 0 ? EligibilityReviewStatus::InReview : EligibilityReviewStatus::Pending,
            ]);
        }

        Model::preventLazyLoading();
        try {
            $this->actingAs($reviewer)->get(route('panel.admissibility.index', [
                'status' => 'in_review',
                'reviewer' => $reviewer->id,
            ]))->assertOk()->assertSee('Revisora Filtro');
            $page = $this->actingAs($reviewer)->get(route('panel.admissibility.index'))->assertOk();
            $this->assertSame(25, substr_count($page->getContent(), 'Propuesta sintética de admisibilidad'));
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    public function test_review_timestamps_persist_utc_and_render_in_hermosillo(): void
    {
        [$participant, , $review] = $this->submittedReview();
        $review->update(['resolved_at' => '2026-08-16 06:59:59', 'status' => EligibilityReviewStatus::Admitted, 'participant_reason' => 'Resolución sintética.']);

        $this->assertSame('2026-08-16 06:59:59', $review->fresh()->resolved_at->utc()->format('Y-m-d H:i:s'));
        $this->actingAs($participant)->get(route('submissions.show', $review->submission))
            ->assertOk()
            ->assertSee('15/08/2026 23:59')
            ->assertSee('(Hermosillo)');
    }
}
