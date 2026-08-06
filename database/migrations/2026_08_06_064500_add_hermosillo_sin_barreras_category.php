<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const COMPETITION_SLUG = 'hermosillo-florece-2026';

    private const CATEGORY_SLUG = 'hermosillo-sin-barreras';

    public function up(): void
    {
        DB::transaction(function (): void {
            $competitionId = DB::table('competitions')
                ->where('slug', self::COMPETITION_SLUG)
                ->value('id');

            if (! $competitionId) {
                return;
            }

            $timestamp = now();

            DB::table('categories')
                ->where('competition_id', $competitionId)
                ->where('slug', 'movilidad-con-flow')
                ->update([
                    'description' => 'Ideas para mejorar la movilidad, la vialidad y la seguridad de los desplazamientos en la ciudad.',
                    'updated_at' => $timestamp,
                ]);

            $category = DB::table('categories')
                ->where('competition_id', $competitionId)
                ->where('slug', self::CATEGORY_SLUG)
                ->first();

            $attributes = [
                'name' => 'Hermosillo sin Barreras',
                'description' => 'Ideas para mejorar la accesibilidad y la inclusión para todas y todos.',
                'sort_order' => 4,
                'active' => true,
                'updated_at' => $timestamp,
            ];

            if ($category) {
                DB::table('categories')->where('id', $category->id)->update($attributes);

                return;
            }

            DB::table('categories')->insert([
                'competition_id' => $competitionId,
                'public_id' => (string) Str::ulid(),
                'slug' => self::CATEGORY_SLUG,
                'created_at' => $timestamp,
                ...$attributes,
            ]);
        });
    }

    public function down(): void
    {
        // Intencionalmente no destructiva: la categoría puede tener propuestas relacionadas.
    }
};
