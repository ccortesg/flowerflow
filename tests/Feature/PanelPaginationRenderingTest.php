<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelPaginationRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_lists_render_bootstrap_pagination_without_unbounded_tailwind_svgs(): void
    {
        $this->seedFlowerFlow();
        config(['flowerflow.flags.panel' => true]);
        $category = Category::query()->firstOrFail();

        foreach (range(1, 26) as $index) {
            $user = User::factory()->create();
            Submission::query()->create([
                'competition_id' => $category->competition_id,
                'category_id' => $category->id,
                'user_id' => $user->id,
                'participation_type' => 'individual',
                'title' => "Propuesta paginada {$index}",
                'summary' => 'Resumen sintético para validar la paginación.',
                'description_text' => 'Descripción sintética.',
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('panel.submissions.index'))
            ->assertOk()
            ->assertSee('class="pagination"', false)
            ->assertSee('class="page-link"', false)
            ->assertDontSee('class="w-5 h-5"', false);
    }
}
