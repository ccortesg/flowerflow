<?php

namespace Tests\Feature;

use App\Mail\SubmissionReceived;
use App\Models\Category;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.submissions' => true]);
        Storage::fake('local');
        Mail::fake();
        $this->seedFlowerFlow();
    }

    public function test_draft_is_sanitized_private_and_finalization_is_idempotent(): void
    {
        $user = $this->participant();
        $category = Category::first();

        $response = $this->actingAs($user)->post('/propuestas', [
            'category_public_id' => $category->public_id,
            'participation_type' => 'individual',
            'title' => 'Cruces seguros',
            'summary' => 'Una propuesta para mejorar cruces peatonales.',
            'description_delta' => json_encode(['ops' => [['insert' => 'Detalle']]]),
            'description_html' => '<p>Detalle seguro</p><script>alert(1)</script><a href="javascript:alert(2)">malo</a>',
            'description_text' => 'Detalle seguro',
            'documents' => [UploadedFile::fake()->createWithContent('propuesta.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF")],
        ]);

        $submission = Submission::firstOrFail();
        $response->assertRedirect(route('submissions.edit', $submission));
        $this->assertStringNotContainsString('<script', $submission->description_html);
        $this->assertStringNotContainsString('javascript:', $submission->description_html);
        $this->assertStringStartsWith('submissions/'.$submission->public_id.'/', $submission->files->first()->path);
        Storage::disk('local')->assertExists($submission->files->first()->path);

        $acceptances = [
            'accept_call_rules' => '1', 'accept_terms' => '1', 'accept_privacy' => '1', 'optional_updates' => '0',
        ];
        $headers = ['Idempotency-Key' => 'test-idempotency-001'];
        $this->actingAs($user)->withHeaders($headers)->post(route('submissions.submit', $submission), $acceptances)->assertRedirect();
        $this->actingAs($user)->withHeaders($headers)->post(route('submissions.submit', $submission), $acceptances)->assertRedirect();

        $submission->refresh();
        $this->assertSame('submitted', $submission->status);
        $this->assertSame('HMO26-'.str_pad((string) $submission->id, 6, '0', STR_PAD_LEFT), $submission->folio);
        $this->assertDatabaseCount('submission_versions', 1);
        $this->assertDatabaseCount('submission_events', 2);
        $this->assertDatabaseCount('legal_acceptances', 4);
        Mail::assertQueued(SubmissionReceived::class, 1);
    }

    public function test_external_link_allowlist_and_total_quota_are_enforced(): void
    {
        $user = $this->participant();
        $category = Category::first();
        $base = [
            'category_public_id' => $category->public_id, 'participation_type' => 'individual',
            'title' => 'Propuesta', 'summary' => 'Resumen válido',
            'description_delta' => '{}', 'description_html' => '<p>Detalle</p>', 'description_text' => 'Detalle',
        ];

        $this->actingAs($user)->post('/propuestas', [...$base,
            'youtube_url' => 'https://example.com/video',
            'public_folder_url' => 'https://127.0.0.1/private',
            'documents' => [UploadedFile::fake()->create('grande.pdf', 10241, 'application/pdf')],
        ])->assertSessionHasErrors(['youtube_url', 'public_folder_url', 'documents']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_one_submission_per_category_and_three_total(): void
    {
        $user = $this->participant();
        $categories = Category::all();
        foreach ($categories as $category) {
            Submission::create([
                'competition_id' => $category->competition_id, 'category_id' => $category->id, 'user_id' => $user->id,
                'participation_type' => 'individual', 'title' => 'P '.$category->id, 'summary' => 'Resumen',
                'description_html' => '<p>Detalle</p>', 'description_text' => 'Detalle',
            ]);
        }

        $this->actingAs($user)->post('/propuestas', [
            'category_public_id' => $categories->first()->public_id,
            'participation_type' => 'individual',
            'title' => 'Una cuarta',
            'summary' => 'No debe guardarse',
            'description_delta' => '{}',
            'description_html' => '<p>Detalle</p>',
            'description_text' => 'Detalle',
        ])->assertSessionHasErrors(['category_public_id']);
        $this->assertSame(3, $user->submissions()->count());
    }
}
