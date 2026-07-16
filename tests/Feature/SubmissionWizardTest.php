<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Competition;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['flowerflow.flags.submissions' => true]);
        Storage::fake('local');
        $this->seedFlowerFlow();
    }

    public function test_wizard_renders_real_steps_controls_and_safe_query_fallback(): void
    {
        $user = $this->participant();

        $this->actingAs($user)->get(route('submissions.create'))
            ->assertOk()
            ->assertSee('Paso 1 de 4')
            ->assertSee('name="participation_type"', false)
            ->assertSee('name="category_public_id"', false)
            ->assertSee('aria-current="step"', false)
            ->assertSee('data-character-counter', false)
            ->assertSee('Guardar borrador')
            ->assertDontSee('Guardado automáticamente');

        $submission = $this->createDraft($user);

        $this->actingAs($user)->get(route('submissions.edit', ['submission' => $submission, 'step' => 99]))
            ->assertOk()
            ->assertSee('1. Modalidad y categoría')
            ->assertDontSee('2. Descripción del proyecto');

        $this->actingAs($user)->get(route('submissions.edit', ['submission' => $submission, 'step' => 2]))
            ->assertOk()
            ->assertSee('Paso 2 de 4')
            ->assertSee('data-flowerflow-editor', false)
            ->assertSee('Puedes guardar una versión incompleta');

        $this->actingAs($user)->get(route('submissions.edit', ['submission' => $submission, 'step' => 3]))
            ->assertOk()
            ->assertSee('Paso 3 de 4')
            ->assertSee('Imágenes de apoyo')
            ->assertDontSee('Imágenes para la descripción')
            ->assertSee('Total acumulado del proyecto')
            ->assertSee('data-youtube-preview', false);
    }

    public function test_step_one_enforces_profile_team_limit_eligibility_and_competition_category(): void
    {
        $incomplete = User::factory()->create();
        $incomplete->assignRole('participant');

        $this->actingAs($incomplete)->from(route('submissions.index'))->get(route('submissions.create'))
            ->assertRedirect(route('submissions.index'))
            ->assertSessionHasErrors('profile');

        $user = $this->participant();
        $category = Category::query()->firstOrFail();
        $members = collect(range(1, 5))->map(fn (int $number) => [
            'full_name' => "Integrante $number",
            'email' => "integrante$number@example.test",
        ])->all();

        $this->actingAs($user)->post(route('submissions.store'), [
            'wizard_step' => 1,
            'wizard_action' => 'save',
            'participation_type' => 'team',
        ])->assertSessionHasErrors([
            'category_public_id', 'team_name', 'team_eligibility', 'title', 'summary',
        ]);

        $this->actingAs($user)->post(route('submissions.store'), [
            ...$this->stepOnePayload($category),
            'participation_type' => 'team',
            'team_name' => 'Equipo ciudadano',
            'team_members' => $members,
        ])->assertSessionHasErrors(['team_members', 'team_eligibility']);

        $otherCompetition = Competition::query()->create([
            'slug' => 'otra-convocatoria',
            'name' => 'Otra convocatoria',
            'closes_at' => now('UTC')->addMonth(),
            'source_timezone' => 'America/Hermosillo',
            'active' => true,
        ]);
        $foreignCategory = $otherCompetition->categories()->create([
            'slug' => 'categoria-ajena',
            'name' => 'Categoría ajena',
            'description' => 'No pertenece a la convocatoria activa.',
            'active' => true,
        ]);

        $this->actingAs($user)->post(route('submissions.store'), $this->stepOnePayload($foreignCategory))
            ->assertSessionHasErrors('category_public_id');

        $validMembers = array_slice($members, 0, 4);
        $this->actingAs($user)->post(route('submissions.store'), [
            ...$this->stepOnePayload($category),
            'participation_type' => 'team',
            'team_name' => 'Equipo ciudadano',
            'team_members' => $validMembers,
            'team_eligibility' => '1',
        ])->assertRedirect();

        $submission = Submission::query()->firstOrFail();
        $this->assertSame(5, $submission->team->members()->count());
        $this->assertNotNull($submission->team->eligibility_declared_at);

        $this->actingAs($user)->post(route('submissions.store'), $this->stepOnePayload($category))
            ->assertSessionHasErrors('category_public_id');

        $secondCategory = Category::query()
            ->where('competition_id', $category->competition_id)
            ->whereKeyNot($category->id)
            ->orderBy('sort_order')
            ->firstOrFail();
        $response = $this->actingAs($user)->post(route('submissions.store'), [
            ...$this->stepOnePayload($secondCategory),
            'wizard_action' => 'save',
            'title' => 'Segundo borrador',
        ]);
        $secondSubmission = Submission::query()->where('category_id', $secondCategory->id)->firstOrFail();
        $response->assertRedirect(route('submissions.edit', ['submission' => $secondSubmission, 'step' => 1]));
    }

    public function test_each_step_updates_only_its_section_and_description_is_sanitized(): void
    {
        $user = $this->participant();
        $submission = $this->createDraft($user, ['title' => 'Título original']);

        $stepTwoUrl = route('submissions.edit', ['submission' => $submission, 'step' => 2]);
        $this->actingAs($user)->from($stepTwoUrl)->put(route('submissions.update', $submission), [
            'wizard_step' => 2,
            'wizard_action' => 'save',
            'title' => 'No debe cambiar',
            'description_delta' => '',
            'description_html' => '',
            'description_text' => '',
        ])->assertRedirect(route('submissions.edit', ['submission' => $submission, 'step' => 2]));

        $submission->refresh();
        $this->assertSame('Título original', $submission->title);
        $this->assertNull($submission->description_delta);

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 2,
            'wizard_action' => 'continue',
            'description_delta' => json_encode(['ops' => [['insert' => 'Contenido seguro']]]),
            'description_html' => '<p>Contenido seguro</p><script>alert(1)</script><a href="javascript:alert(2)">enlace</a>',
            'description_text' => 'Contenido seguro',
        ])->assertRedirect(route('submissions.edit', ['submission' => $submission, 'step' => 3]));

        $submission->refresh();
        $this->assertSame('Título original', $submission->title);
        $this->assertStringNotContainsString('<script', $submission->description_html);
        $this->assertStringNotContainsString('javascript:', $submission->description_html);
    }

    public function test_step_two_requires_content_only_when_continuing(): void
    {
        $user = $this->participant();
        $submission = $this->createDraft($user);
        $stepTwoUrl = route('submissions.edit', ['submission' => $submission, 'step' => 2]);

        $this->actingAs($user)->from($stepTwoUrl)->put(route('submissions.update', $submission), [
            'wizard_step' => 2,
            'wizard_action' => 'continue',
            'description_delta' => '',
            'description_html' => '',
            'description_text' => '',
        ])->assertRedirect($stepTwoUrl)
            ->assertSessionHasErrors(['description_delta', 'description_html', 'description_text'])
            ->assertSessionHasInput('wizard_step', 2);

        $this->assertNull($submission->fresh()->description_text);
    }

    public function test_step_three_validates_hosts_credentials_and_shared_existing_quota(): void
    {
        $user = $this->participant();
        $submission = $this->createDraft($user);

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'save',
            'documents' => [
                UploadedFile::fake()->createWithContent('programa.exe', 'MZ'),
                UploadedFile::fake()->createWithContent('falso.pdf', 'no es un pdf'),
            ],
            'editor_images' => [UploadedFile::fake()->createWithContent('falsa.jpg', 'no es una imagen')],
        ])->assertSessionHasErrors(['documents.0', 'documents.1', 'editor_images.0']);

        $submission->files()->create([
            'actor_user_id' => $user->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/existing.pdf',
            'original_name' => 'existente.pdf',
            'stored_name' => 'existing.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 5 * 1024 * 1024,
            'sha256' => str_repeat('a', 64),
        ]);

        $largePdf = UploadedFile::fake()->createWithContent(
            'adicional.pdf',
            str_pad('%PDF-', 6 * 1024 * 1024, 'a')
        );

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'save',
            'youtube_url' => 'https://usuario@youtube.com/watch?v=dQw4w9WgXcQ',
            'public_folder_url' => 'https://example.com/carpeta',
            'documents' => [$largePdf],
        ])->assertSessionHasErrors(['youtube_url', 'public_folder_url', 'documents']);

        $this->assertDatabaseCount('submission_external_links', 0);
        $this->assertDatabaseCount('submission_files', 1);
    }

    public function test_step_three_is_optional_for_draft_but_review_and_finalization_require_a_document(): void
    {
        $user = $this->participant();
        $submission = $this->createDraft($user);
        $this->completeDescription($user, $submission);

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'continue',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'public_folder_url' => 'https://drive.google.com/drive/folders/example',
        ])->assertRedirect(route('submissions.show', $submission));

        $this->actingAs($user)->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('Paso 4 de 4')
            ->assertSee('Falta adjuntar al menos un documento antes de enviar.');

        $this->actingAs($user)->post(route('submissions.submit', $submission), $this->acceptances())
            ->assertSessionHasErrors('files');

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'continue',
            'documents' => [UploadedFile::fake()->createWithContent('propuesta.pdf', "%PDF-1.4\n%%EOF")],
            'editor_images' => [UploadedFile::fake()->image('apoyo.jpg', 240, 160)],
        ])->assertRedirect(route('submissions.show', $submission));

        $this->actingAs($user)->post(route('submissions.submit', $submission), $this->acceptances())
            ->assertRedirect(route('submissions.show', $submission));
        $this->assertSame('submitted', $submission->fresh()->status);
        $this->assertDatabaseHas('submission_files', ['submission_id' => $submission->id, 'kind' => 'document']);
        $this->assertDatabaseHas('submission_files', ['submission_id' => $submission->id, 'kind' => 'editor_image']);
    }

    public function test_finalization_requires_description_and_legal_acceptances_and_shows_folio(): void
    {
        $user = $this->participant();
        $submission = $this->createDraft($user);

        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'save',
            'documents' => [UploadedFile::fake()->createWithContent('propuesta.pdf', "%PDF-1.4\n%%EOF")],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('submissions.submit', $submission), $this->acceptances())
            ->assertSessionHasErrors('description');

        $this->completeDescription($user, $submission);
        $this->actingAs($user)->post(route('submissions.submit', $submission), [])
            ->assertSessionHasErrors(['accept_call_rules', 'accept_terms', 'accept_privacy']);

        $this->actingAs($user)->post(route('submissions.submit', $submission), $this->acceptances())
            ->assertRedirect(route('submissions.show', $submission));

        $folio = $submission->fresh()->folio;
        $this->assertNotNull($folio);
        $this->actingAs($user)->get(route('submissions.show', $submission))->assertOk()->assertSee($folio);
    }

    public function test_only_owner_can_edit_or_delete_and_submitted_version_is_immutable(): void
    {
        $owner = $this->participant();
        $other = $this->participant();
        $submission = $this->createDraft($owner);
        $file = $submission->files()->create([
            'actor_user_id' => $owner->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/private.pdf',
            'original_name' => 'privado.pdf',
            'stored_name' => 'private.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('b', 64),
        ]);

        $this->actingAs($other)->get(route('submissions.edit', $submission))->assertForbidden();
        $this->actingAs($other)->delete(route('submissions.files.destroy', [$submission, $file]))->assertForbidden();

        $otherSubmission = $this->createDraft($other);
        $otherFile = $otherSubmission->files()->create([
            'actor_user_id' => $other->id,
            'kind' => 'document',
            'format_category' => 'pdf',
            'disk' => 'local',
            'path' => 'submissions/other.pdf',
            'original_name' => 'ajeno.pdf',
            'stored_name' => 'other.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 10,
            'sha256' => str_repeat('c', 64),
        ]);
        $this->actingAs($owner)->delete(route('submissions.files.destroy', [$submission, $otherFile]))->assertNotFound();

        $submission->update(['status' => 'submitted']);
        $this->actingAs($owner)->put(route('submissions.update', $submission), [
            'wizard_step' => 3,
            'wizard_action' => 'save',
        ])->assertForbidden();
        $this->actingAs($owner)->delete(route('submissions.files.destroy', [$submission, $file]))->assertForbidden();

        $this->assertDatabaseHas('submission_files', ['id' => $file->id]);
    }

    private function createDraft(User $user, array $overrides = []): Submission
    {
        $category = Category::query()->orderBy('sort_order')->firstOrFail();
        $this->actingAs($user)->post(route('submissions.store'), [
            ...$this->stepOnePayload($category),
            ...$overrides,
        ])->assertRedirect();

        return Submission::query()->latest('id')->firstOrFail();
    }

    private function stepOnePayload(Category $category): array
    {
        return [
            'wizard_step' => 1,
            'wizard_action' => 'continue',
            'category_public_id' => $category->public_id,
            'participation_type' => 'individual',
            'title' => 'Proyecto ciudadano',
            'summary' => 'Resumen claro de una propuesta para Hermosillo.',
        ];
    }

    private function completeDescription(User $user, Submission $submission): void
    {
        $this->actingAs($user)->put(route('submissions.update', $submission), [
            'wizard_step' => 2,
            'wizard_action' => 'continue',
            'description_delta' => json_encode(['ops' => [['insert' => 'Descripción completa']]]),
            'description_html' => '<p>Descripción completa</p>',
            'description_text' => 'Descripción completa',
        ])->assertRedirect();
    }

    private function acceptances(): array
    {
        return [
            'accept_call_rules' => '1',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ];
    }
}
