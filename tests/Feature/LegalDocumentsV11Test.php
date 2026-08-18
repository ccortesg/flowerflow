<?php

namespace Tests\Feature;

use App\Models\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalDocumentsV11Test extends TestCase
{
    use RefreshDatabase;

    public function test_v1_1_files_hashes_and_active_catalog_are_exact(): void
    {
        $this->seedFlowerFlow();

        foreach (config('flowerflow.legal_documents') as $code => $definition) {
            $path = public_path($definition['path']);

            $this->assertFileExists($path);
            $this->assertSame($definition['sha256'], hash_file('sha256', $path));
            $this->assertDatabaseHas('legal_documents', [
                'code' => $code,
                'version' => '1.1',
                'title' => $definition['title'],
                'public_path' => '/'.$definition['path'],
                'sha256' => $definition['sha256'],
                'active' => true,
                'acceptance_required' => true,
            ]);
            $this->assertSame(1, LegalDocument::query()->where('code', $code)->where('active', true)->count());
            $this->assertDatabaseHas('legal_documents', [
                'code' => $code,
                'version' => '1.0',
                'active' => false,
            ]);
        }
    }

    public function test_migration_switches_active_version_without_deleting_history_or_acceptances(): void
    {
        $this->seedFlowerFlow();
        $participant = $this->participant(['email' => 'historial-legal@example.test']);
        $historical = LegalDocument::query()->where('code', 'terms')->where('version', '1.0')->firstOrFail();
        $current = LegalDocument::query()->where('code', 'terms')->where('version', '1.1')->firstOrFail();
        $acceptance = $participant->legalAcceptances()->create([
            'legal_document_id' => $historical->id,
            'purpose' => 'historical_test',
            'document_version' => '1.0',
            'accepted' => true,
            'accepted_at' => now('UTC'),
            'context' => ['source' => 'synthetic_test'],
        ]);

        $migration = require database_path('migrations/2026_08_17_220000_publish_legal_documents_v1_1.php');
        $migration->down();

        $this->assertTrue($historical->fresh()->active);
        $this->assertFalse($current->fresh()->active);
        $this->assertDatabaseHas('legal_acceptances', [
            'id' => $acceptance->id,
            'legal_document_id' => $historical->id,
            'document_version' => '1.0',
        ]);

        $migration->up();
        $migration->up();
        $this->seedFlowerFlow();

        $this->assertFalse($historical->fresh()->active);
        $this->assertTrue($current->fresh()->active);
        $this->assertDatabaseHas('legal_acceptances', [
            'id' => $acceptance->id,
            'legal_document_id' => $historical->id,
            'document_version' => '1.0',
        ]);
    }

    public function test_public_participant_and_panel_surfaces_link_v1_1_and_show_the_legal_responsible(): void
    {
        $this->seedFlowerFlow();

        $this->get(route('documents'))->assertOk()
            ->assertSee('FUNXT, A.C.')
            ->assertSee('FUN110208BT0')
            ->assertSee('ESPOLI 6 TOSCANA')
            ->assertSee('versión 1.1')
            ->assertSee(config('flowerflow.legal_documents.mechanics.path'), false)
            ->assertSee(config('flowerflow.legal_documents.terms.path'), false)
            ->assertSee(config('flowerflow.legal_documents.privacy.path'), false);

        $participant = $this->participant(['email' => 'v11-participant@example.test']);
        $this->actingAs($participant)->get(route('profile.edit'))->assertOk()
            ->assertSee(config('flowerflow.legal_documents.privacy.path'), false)
            ->assertSee('versión 1.1');

        $admin = $this->admin(['email' => 'v11-admin@example.test']);
        $this->actingAs($admin)->get(route('panel.dashboard'))->assertOk()
            ->assertSee('Documentos jurídicos vigentes')
            ->assertSee('FUNXT, A.C.')
            ->assertSee(config('flowerflow.legal_documents.mechanics.path'), false);
    }
}
