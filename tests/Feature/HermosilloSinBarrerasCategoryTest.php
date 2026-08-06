<?php

namespace Tests\Feature;

use App\Mail\SubmissionReceived;
use App\Models\Competition;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HermosilloSinBarrerasCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'flowerflow.flags.panel' => true,
            'flowerflow.flags.submissions' => true,
        ]);
        Storage::fake('local');
        Mail::fake();
    }

    public function test_seed_and_data_migration_are_idempotent_and_preserve_public_id(): void
    {
        $this->assertSame(4, config('flowerflow.limits.submissions_per_user'));
        $this->assertDatabaseCount('categories', 0);
        $this->seedFlowerFlow();

        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $categories = $competition->categories()->where('active', true)->orderBy('sort_order')->get();

        $this->assertSame([
            'movilidad-con-flow',
            'hermosillo-florece',
            'mi-familia-mi-mascota',
            'hermosillo-sin-barreras',
        ], $categories->pluck('slug')->all());
        $this->assertSame([1, 2, 3, 4], $categories->pluck('sort_order')->all());
        $this->assertSame(4, $categories->count());

        $mobility = $categories->firstWhere('slug', 'movilidad-con-flow');
        $barriers = $categories->firstWhere('slug', 'hermosillo-sin-barreras');
        $publicId = $barriers->public_id;

        $this->assertSame(
            'Ideas para mejorar la movilidad, la vialidad y la seguridad de los desplazamientos en la ciudad.',
            $mobility->description
        );
        $this->assertSame('Hermosillo sin Barreras', $barriers->name);
        $this->assertSame(
            'Ideas para mejorar la accesibilidad y la inclusión para todas y todos.',
            $barriers->description
        );
        $this->assertTrue($barriers->active);

        $barriers->update([
            'name' => 'Nombre temporal',
            'description' => 'Descripción temporal',
            'sort_order' => 9,
            'active' => false,
        ]);
        $mobility->update(['description' => 'Descripción anterior']);

        $migration = require database_path('migrations/2026_08_06_064500_add_hermosillo_sin_barreras_category.php');
        $migration->up();
        $migration->up();

        $barriers->refresh();
        $mobility->refresh();
        $this->assertSame($publicId, $barriers->public_id);
        $this->assertSame('Hermosillo sin Barreras', $barriers->name);
        $this->assertSame(4, $barriers->sort_order);
        $this->assertTrue($barriers->active);
        $this->assertSame(
            'Ideas para mejorar la accesibilidad y la inclusión para todas y todos.',
            $barriers->description
        );
        $this->assertSame(
            'Ideas para mejorar la movilidad, la vialidad y la seguridad de los desplazamientos en la ciudad.',
            $mobility->description
        );
        $this->assertSame(1, $competition->categories()->where('slug', 'hermosillo-sin-barreras')->count());

        $this->seedFlowerFlow();
        $this->assertSame($publicId, $barriers->fresh()->public_id);

        $migration->down();
        $this->assertDatabaseHas('categories', [
            'competition_id' => $competition->id,
            'public_id' => $publicId,
            'slug' => 'hermosillo-sin-barreras',
        ]);
    }

    public function test_category_is_available_across_participant_submission_snapshot_and_mail(): void
    {
        $this->seedFlowerFlow();
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $category = $competition->categories()->where('slug', 'hermosillo-sin-barreras')->firstOrFail();
        $mobility = $competition->categories()->where('slug', 'movilidad-con-flow')->firstOrFail();
        $inactive = $competition->categories()->create([
            'slug' => 'categoria-no-seleccionable',
            'name' => 'Categoría no seleccionable',
            'description' => 'Inactiva.',
            'sort_order' => 5,
            'active' => false,
        ]);
        $participant = $this->participant(['email' => 'barreras-participante@example.test']);

        $this->actingAs($participant)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder($competition->categories()->where('active', true)->orderBy('sort_order')->pluck('name')->all())
            ->assertSee('Hermosillo sin Barreras')
            ->assertDontSee($inactive->name);

        $this->actingAs($participant)->get(route('submissions.create'))
            ->assertOk()
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('Ideas para mejorar la accesibilidad y la inclusión para todas y todos.')
            ->assertSee('ri-accessibility-line', false)
            ->assertDontSee($inactive->name);

        $this->actingAs($participant)->post(route('submissions.store'), [
            'wizard_step' => 1,
            'wizard_action' => 'continue',
            'category_public_id' => $category->public_id,
            'participation_type' => 'individual',
            'title' => 'Banquetas accesibles para todas las personas',
            'summary' => 'Propuesta sintética de accesibilidad urbana.',
        ])->assertRedirect();

        $submission = Submission::query()->where('user_id', $participant->id)->firstOrFail();
        $this->assertSame($category->id, $submission->category_id);

        $this->actingAs($participant)->put(route('submissions.update', $submission), [
            'wizard_step' => 1,
            'wizard_action' => 'save',
            'category_public_id' => $mobility->public_id,
            'participation_type' => 'individual',
            'title' => $submission->title,
            'summary' => $submission->summary,
        ])->assertRedirect();
        $this->assertSame($mobility->id, $submission->fresh()->category_id);

        $this->actingAs($participant)->put(route('submissions.update', $submission), [
            'wizard_step' => 1,
            'wizard_action' => 'save',
            'category_public_id' => $category->public_id,
            'participation_type' => 'individual',
            'title' => $submission->title,
            'summary' => $submission->summary,
        ])->assertRedirect();
        $submission->refresh();

        $this->actingAs($participant)->get(route('submissions.edit', ['submission' => $submission, 'step' => 1]))
            ->assertOk()
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('value="'.$category->public_id.'"', false)
            ->assertSee('ri-accessibility-line', false);
        $this->actingAs($participant)->get(route('submissions.index'))
            ->assertOk()
            ->assertSee($submission->title)
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('ri-accessibility-line', false);
        $this->actingAs($participant)->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('4. Revisión y envío')
            ->assertSee('Hermosillo sin Barreras');

        $submission->update([
            'description_delta' => ['ops' => [['insert' => 'Detalle accesible']]],
            'description_html' => '<p>Detalle accesible.</p>',
            'description_text' => 'Detalle accesible.',
        ]);
        $file = $submission->files()->create([
            'actor_user_id' => $participant->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/'.$submission->public_id.'/propuesta.pdf',
            'original_name' => 'propuesta.pdf',
            'stored_name' => 'propuesta.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 20,
            'sha256' => str_repeat('a', 64),
        ]);
        Storage::disk('local')->put($file->path, "%PDF-1.4\n%%EOF");

        $this->actingAs($participant)->post(route('submissions.submit', $submission), [
            'accept_call_rules' => '1',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ])->assertRedirect();

        $submission->refresh()->load('category');
        $this->assertSame('submitted', $submission->status);
        $this->assertSame(
            'hermosillo-sin-barreras',
            $submission->versions()->firstOrFail()->snapshot['category']['slug']
        );
        Mail::assertQueued(SubmissionReceived::class, function (SubmissionReceived $mail): bool {
            return str_contains($mail->render(), 'Hermosillo sin Barreras');
        });

        $this->actingAs($participant)->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('Hermosillo sin Barreras');
        $this->actingAs($participant)->get(route('submissions.edit', $submission))->assertForbidden();
    }

    public function test_admin_dashboard_filter_detail_and_download_support_the_category(): void
    {
        $this->seedFlowerFlow();
        $competition = Competition::query()->where('slug', 'hermosillo-florece-2026')->firstOrFail();
        $category = $competition->categories()->where('slug', 'hermosillo-sin-barreras')->firstOrFail();
        $inactive = $competition->categories()->where('slug', 'mi-familia-mi-mascota')->firstOrFail();
        $inactive->update(['active' => false]);
        $admin = $this->admin(['email' => 'barreras-admin@example.test']);

        $this->actingAs($admin)->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('<span>Hermosillo sin Barreras</span><strong>0</strong>', false)
            ->assertDontSee($inactive->name);

        $owner = $this->participant(['email' => 'barreras-owner@example.test']);
        $submission = Submission::query()->create([
            'competition_id' => $competition->id,
            'category_id' => $category->id,
            'user_id' => $owner->id,
            'participation_type' => 'individual',
            'title' => 'Acceso universal al espacio público',
            'summary' => 'Resumen sintético.',
            'description_html' => '<p>Detalle sintético.</p>',
            'description_text' => 'Detalle sintético.',
            'status' => 'submitted',
            'folio' => 'HMO26-900001',
            'submitted_at' => now('UTC'),
        ]);
        $otherOwner = $this->participant(['email' => 'movilidad-owner@example.test']);
        $mobility = $competition->categories()->where('slug', 'movilidad-con-flow')->firstOrFail();
        Submission::query()->create([
            'competition_id' => $competition->id,
            'category_id' => $mobility->id,
            'user_id' => $otherOwner->id,
            'participation_type' => 'individual',
            'title' => 'Propuesta de movilidad que no debe aparecer',
            'summary' => 'Resumen sintético.',
            'status' => 'submitted',
            'folio' => 'HMO26-900002',
            'submitted_at' => now('UTC'),
        ]);
        $historical = Submission::query()->create([
            'competition_id' => $competition->id,
            'category_id' => $inactive->id,
            'user_id' => $otherOwner->id,
            'participation_type' => 'individual',
            'title' => 'Propuesta histórica en categoría inactiva',
            'summary' => 'Resumen sintético.',
            'status' => 'submitted',
            'folio' => 'HMO26-900003',
            'submitted_at' => now('UTC'),
        ]);
        $file = $submission->files()->create([
            'actor_user_id' => $owner->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/'.$submission->public_id.'/accesibilidad.pdf',
            'original_name' => 'accesibilidad.pdf',
            'stored_name' => 'accesibilidad.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 20,
            'sha256' => str_repeat('b', 64),
        ]);
        Storage::disk('local')->put($file->path, "%PDF-1.4\n%%EOF");

        $this->actingAs($admin)->get(route('panel.dashboard'))
            ->assertOk()
            ->assertSee('<span>Hermosillo sin Barreras</span><strong>1</strong>', false);
        $this->actingAs($admin)->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee($historical->title)
            ->assertSee($inactive->name);
        $this->actingAs($admin)->get(route('panel.submissions.index', [
            'category' => 'hermosillo-sin-barreras',
        ]))
            ->assertOk()
            ->assertSee('value="hermosillo-sin-barreras" selected', false)
            ->assertSee($submission->title)
            ->assertDontSee('Propuesta de movilidad que no debe aparecer');
        $this->actingAs($admin)->get(route('panel.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Hermosillo sin Barreras')
            ->assertSee('accesibilidad.pdf');
        $this->actingAs($admin)->get(route('submissions.files.download', [$submission, $file]))
            ->assertOk()
            ->assertDownload('accesibilidad.pdf');
    }
}
