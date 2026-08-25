<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const COMPETITION_SLUG = 'hermosillo-florece-2026';

    private const V2_TITLE = 'Rúbrica de evaluación Hermosillo Florece 2026 — Mecánica v1.1';

    private const MIGRATION_ACTIVATION_REASON = 'Activación técnica de la rúbrica legal v2 aprobada para M6A.';

    /** @var list<array{code:string,label:string,weight:string,sort_order:int}> */
    private const V2_CRITERIA = [
        ['code' => 'relevance_diagnosis', 'label' => 'Relevancia del problema para Hermosillo y claridad del diagnóstico', 'weight' => '25.0000', 'sort_order' => 1],
        ['code' => 'quality_originality', 'label' => 'Calidad, claridad y originalidad de la solución', 'weight' => '25.0000', 'sort_order' => 2],
        ['code' => 'participation_coordination', 'label' => 'Participación ciudadana y coordinación municipal propuesta', 'weight' => '25.0000', 'sort_order' => 3],
        ['code' => 'impact_sustainability', 'label' => 'Impacto, sostenibilidad y posibilidad de medición', 'weight' => '25.0000', 'sort_order' => 4],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('rubric_versions') || ! Schema::hasTable('judge_profiles')) {
            throw new RuntimeException('M6A requires the M2 and M3 schema before reconciliation.');
        }

        // MySQL DDL is not transactional. Reject divergent business data before
        // altering constraints so a closed failure cannot leave a half-applied schema.
        $this->assertExistingInstallationCanBeReconciled();

        Schema::table('rubric_versions', function (Blueprint $table): void {
            $table->string('activation_source', 16)->nullable()->after('activated_by_user_id');
            $table->string('superseded_source', 16)->nullable()->after('superseded_by_user_id');
        });

        DB::statement('ALTER TABLE rubric_versions DROP CHECK rubric_versions_lifecycle_check');
        DB::table('rubric_versions')->whereNotNull('activated_by_user_id')->update(['activation_source' => 'admin']);
        DB::table('rubric_versions')->whereNotNull('superseded_by_user_id')->update(['superseded_source' => 'admin']);
        DB::statement("ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_lifecycle_check CHECK ((status = 'draft' AND active_slot IS NULL AND activated_at IS NULL AND activated_by_user_id IS NULL AND activation_source IS NULL AND activation_reason IS NULL AND superseded_at IS NULL AND superseded_by_user_id IS NULL AND superseded_source IS NULL) OR (status = 'active' AND active_slot = 1 AND activated_at IS NOT NULL AND activation_source IN ('admin', 'migration') AND ((activation_source = 'admin' AND activated_by_user_id IS NOT NULL) OR (activation_source = 'migration' AND activated_by_user_id IS NULL)) AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NULL AND superseded_by_user_id IS NULL AND superseded_source IS NULL) OR (status = 'superseded' AND active_slot IS NULL AND activated_at IS NOT NULL AND activation_source IN ('admin', 'migration') AND ((activation_source = 'admin' AND activated_by_user_id IS NOT NULL) OR (activation_source = 'migration' AND activated_by_user_id IS NULL)) AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NOT NULL AND superseded_source IN ('admin', 'migration') AND ((superseded_source = 'admin' AND superseded_by_user_id IS NOT NULL) OR (superseded_source = 'migration' AND superseded_by_user_id IS NULL))))");

        DB::statement('ALTER TABLE rubric_criteria DROP CHECK rubric_criteria_exact_contract_check');
        DB::statement("ALTER TABLE rubric_criteria ADD CONSTRAINT rubric_criteria_exact_contract_check CHECK ((code = 'pertinence' AND label = 'Pertinencia' AND weight = 20.0000 AND sort_order = 1) OR (code = 'clarity' AND label = 'Claridad' AND weight = 20.0000 AND sort_order = 2) OR (code = 'feasibility' AND label = 'Viabilidad' AND weight = 25.0000 AND sort_order = 3) OR (code = 'impact' AND label = 'Impacto' AND weight = 25.0000 AND sort_order = 4) OR (code = 'coherence' AND label = 'Coherencia' AND weight = 10.0000 AND sort_order = 5) OR (code = 'relevance_diagnosis' AND label = 'Relevancia del problema para Hermosillo y claridad del diagnóstico' AND weight = 25.0000 AND sort_order = 1) OR (code = 'quality_originality' AND label = 'Calidad, claridad y originalidad de la solución' AND weight = 25.0000 AND sort_order = 2) OR (code = 'participation_coordination' AND label = 'Participación ciudadana y coordinación municipal propuesta' AND weight = 25.0000 AND sort_order = 3) OR (code = 'impact_sustainability' AND label = 'Impacto, sostenibilidad y posibilidad de medición' AND weight = 25.0000 AND sort_order = 4))");

        Schema::create('judge_setup_links', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('judge_profile_id')->constrained()->restrictOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->char('email_fingerprint', 64);
            $table->unsignedTinyInteger('active_slot')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->foreignId('issued_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['judge_profile_id', 'active_slot'], 'judge_setup_links_one_active_unique');
            $table->index(['judge_profile_id', 'expires_at'], 'judge_setup_links_profile_expiry_index');
        });
        DB::statement('ALTER TABLE judge_setup_links ADD CONSTRAINT judge_setup_links_lifecycle_check CHECK ((active_slot = 1 AND consumed_at IS NULL AND invalidated_at IS NULL AND expires_at > issued_at) OR (active_slot IS NULL AND consumed_at IS NOT NULL AND invalidated_at IS NULL) OR (active_slot IS NULL AND consumed_at IS NULL AND invalidated_at IS NOT NULL))');

        $this->activateLegalRubricForExistingInstallation();
    }

    public function down(): void
    {
        if (Schema::hasTable('judge_setup_links') && DB::table('judge_setup_links')->exists()) {
            throw new RuntimeException('Cannot remove M6A while judge setup-link evidence exists. Disable the notification flag instead.');
        }
        if (Schema::hasTable('audit_logs') && DB::table('audit_logs')->where('action', 'like', 'judge.setup_link.%')->exists()) {
            throw new RuntimeException('Cannot remove M6A while judge setup-link audit evidence exists.');
        }

        $v2Ids = DB::table('rubric_versions')->where('version', 2)->pluck('id');
        if ($v2Ids->isNotEmpty()) {
            $hasEvidence = (Schema::hasTable('judge_assignments') && DB::table('judge_assignments')->whereIn('rubric_version_id', $v2Ids)->exists())
                || (Schema::hasTable('evaluations') && DB::table('evaluations')->whereIn('rubric_version_id', $v2Ids)->exists())
                || (Schema::hasTable('evaluation_scores') && DB::table('evaluation_scores')
                    ->join('rubric_criteria', 'rubric_criteria.id', '=', 'evaluation_scores.rubric_criterion_id')
                    ->whereIn('rubric_criteria.rubric_version_id', $v2Ids)->exists());
            if ($hasEvidence) {
                throw new RuntimeException('Cannot remove legal rubric v2 while assignment or evaluation evidence exists. Disable FLOWERFLOW_EVALUATION_ENABLED instead.');
            }

            DB::table('rubric_criteria')->whereIn('rubric_version_id', $v2Ids)->delete();
            DB::table('rubric_versions')->whereIn('id', $v2Ids)->delete();
        }

        DB::table('rubric_versions')
            ->where('version', 1)
            ->where('status', 'superseded')
            ->where('superseded_source', 'migration')
            ->whereNotNull('superseded_at')
            ->update([
                'status' => 'active',
                'active_slot' => 1,
                'superseded_at' => null,
                'superseded_by_user_id' => null,
                'superseded_source' => null,
                'updated_at' => now('UTC'),
            ]);

        Schema::dropIfExists('judge_setup_links');
        DB::statement('ALTER TABLE rubric_criteria DROP CHECK rubric_criteria_exact_contract_check');
        DB::statement("ALTER TABLE rubric_criteria ADD CONSTRAINT rubric_criteria_exact_contract_check CHECK ((code = 'pertinence' AND label = 'Pertinencia' AND weight = 20.0000 AND sort_order = 1) OR (code = 'clarity' AND label = 'Claridad' AND weight = 20.0000 AND sort_order = 2) OR (code = 'feasibility' AND label = 'Viabilidad' AND weight = 25.0000 AND sort_order = 3) OR (code = 'impact' AND label = 'Impacto' AND weight = 25.0000 AND sort_order = 4) OR (code = 'coherence' AND label = 'Coherencia' AND weight = 10.0000 AND sort_order = 5))");
        DB::statement('ALTER TABLE rubric_versions DROP CHECK rubric_versions_lifecycle_check');
        DB::statement("ALTER TABLE rubric_versions ADD CONSTRAINT rubric_versions_lifecycle_check CHECK ((status = 'draft' AND active_slot IS NULL AND activated_at IS NULL AND activated_by_user_id IS NULL AND activation_reason IS NULL AND superseded_at IS NULL AND superseded_by_user_id IS NULL) OR (status = 'active' AND active_slot = 1 AND activated_at IS NOT NULL AND activated_by_user_id IS NOT NULL AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NULL AND superseded_by_user_id IS NULL) OR (status = 'superseded' AND active_slot IS NULL AND activated_at IS NOT NULL AND activated_by_user_id IS NOT NULL AND CHAR_LENGTH(activation_reason) BETWEEN 20 AND 1000 AND superseded_at IS NOT NULL AND superseded_by_user_id IS NOT NULL))");
        Schema::table('rubric_versions', function (Blueprint $table): void {
            $table->dropColumn(['activation_source', 'superseded_source']);
        });
    }

    private function activateLegalRubricForExistingInstallation(): void
    {
        $otherCompetitionHasRubric = DB::table('rubric_versions')
            ->join('competitions', 'competitions.id', '=', 'rubric_versions.competition_id')
            ->where('competitions.slug', '<>', self::COMPETITION_SLUG)
            ->exists();
        if ($otherCompetitionHasRubric) {
            throw new RuntimeException('M6A cannot infer a legal rubric for a different competition.');
        }

        $competition = DB::table('competitions')->where('slug', self::COMPETITION_SLUG)->first();
        if (! $competition) {
            return;
        }

        $versions = DB::table('rubric_versions')->where('competition_id', $competition->id)->lockForUpdate()->get();
        if ($versions->contains(fn ($version): bool => ! in_array((int) $version->version, [1, 2], true))) {
            throw new RuntimeException('M6A found an unknown rubric version and failed closed.');
        }

        $v1 = $versions->firstWhere('version', 1);
        if ($v1) {
            $this->assertRubric($v1, [
                ['code' => 'pertinence', 'label' => 'Pertinencia', 'weight' => '20.0000', 'sort_order' => 1],
                ['code' => 'clarity', 'label' => 'Claridad', 'weight' => '20.0000', 'sort_order' => 2],
                ['code' => 'feasibility', 'label' => 'Viabilidad', 'weight' => '25.0000', 'sort_order' => 3],
                ['code' => 'impact', 'label' => 'Impacto', 'weight' => '25.0000', 'sort_order' => 4],
                ['code' => 'coherence', 'label' => 'Coherencia', 'weight' => '10.0000', 'sort_order' => 5],
            ]);
        }

        $v2 = $versions->firstWhere('version', 2);
        if ($v2) {
            $this->assertRubric($v2, self::V2_CRITERIA, self::V2_TITLE);
            if ($v2->status !== 'active' || (int) $v2->active_slot !== 1) {
                throw new RuntimeException('M6A found legal rubric v2 in a divergent lifecycle state.');
            }

            return;
        }

        $now = now('UTC');
        if ($v1?->status === 'active') {
            DB::table('rubric_versions')->where('id', $v1->id)->update([
                'status' => 'superseded',
                'active_slot' => null,
                'superseded_at' => $now,
                'superseded_by_user_id' => null,
                'superseded_source' => 'migration',
                'updated_at' => $now,
            ]);
        } elseif ($versions->contains(fn ($version): bool => $version->status === 'active')) {
            throw new RuntimeException('M6A found an unknown active rubric and failed closed.');
        }

        $v2Id = DB::table('rubric_versions')->insertGetId([
            'public_id' => (string) Str::ulid(),
            'competition_id' => $competition->id,
            'version' => 2,
            'title' => self::V2_TITLE,
            'status' => 'active',
            'criterion_score_min' => '0.0000',
            'criterion_score_max' => '10.0000',
            'criterion_score_step' => '0.5000',
            'total_weight' => '100.0000',
            'total_score_min' => '0.0000',
            'total_score_max' => '100.0000',
            'internal_decimal_places' => 4,
            'display_decimal_places' => 2,
            'rounding_mode' => 'HALF_UP',
            'general_comment_min_characters' => 100,
            'general_comment_max_characters' => 2000,
            'criterion_comment_max_characters' => 1000,
            'active_slot' => 1,
            'created_by_user_id' => null,
            'last_edited_by_user_id' => null,
            'activated_at' => $now,
            'activated_by_user_id' => null,
            'activation_source' => 'migration',
            'activation_reason' => self::MIGRATION_ACTIVATION_REASON,
            'superseded_at' => null,
            'superseded_by_user_id' => null,
            'superseded_source' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach (self::V2_CRITERIA as $criterion) {
            DB::table('rubric_criteria')->insert([
                'rubric_version_id' => $v2Id,
                ...$criterion,
                'description' => null,
                'min_score' => '0.0000',
                'max_score' => '10.0000',
                'score_step' => '0.5000',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function assertExistingInstallationCanBeReconciled(): void
    {
        $otherCompetitionHasRubric = DB::table('rubric_versions')
            ->join('competitions', 'competitions.id', '=', 'rubric_versions.competition_id')
            ->where('competitions.slug', '<>', self::COMPETITION_SLUG)
            ->exists();
        if ($otherCompetitionHasRubric) {
            throw new RuntimeException('M6A cannot infer a legal rubric for a different competition.');
        }

        $competition = DB::table('competitions')->where('slug', self::COMPETITION_SLUG)->first();
        if (! $competition) {
            return;
        }

        $versions = DB::table('rubric_versions')->where('competition_id', $competition->id)->get();
        if ($versions->contains(fn ($version): bool => ! in_array((int) $version->version, [1, 2], true))) {
            throw new RuntimeException('M6A found an unknown rubric version and failed closed.');
        }

        $v1 = $versions->firstWhere('version', 1);
        if ($v1) {
            $this->assertRubric($v1, [
                ['code' => 'pertinence', 'label' => 'Pertinencia', 'weight' => '20.0000', 'sort_order' => 1],
                ['code' => 'clarity', 'label' => 'Claridad', 'weight' => '20.0000', 'sort_order' => 2],
                ['code' => 'feasibility', 'label' => 'Viabilidad', 'weight' => '25.0000', 'sort_order' => 3],
                ['code' => 'impact', 'label' => 'Impacto', 'weight' => '25.0000', 'sort_order' => 4],
                ['code' => 'coherence', 'label' => 'Coherencia', 'weight' => '10.0000', 'sort_order' => 5],
            ]);
        }

        $v2 = $versions->firstWhere('version', 2);
        if ($v2) {
            $this->assertRubric($v2, self::V2_CRITERIA, self::V2_TITLE);
            if ($v2->status !== 'active' || (int) $v2->active_slot !== 1) {
                throw new RuntimeException('M6A found legal rubric v2 in a divergent lifecycle state.');
            }
        }

        if ($versions->contains(fn ($version): bool => $version->status === 'active' && (int) $version->version !== 1 && (int) $version->version !== 2)) {
            throw new RuntimeException('M6A found an unknown active rubric and failed closed.');
        }
    }

    /** @param list<array{code:string,label:string,weight:string,sort_order:int}> $criteria */
    private function assertRubric(object $rubric, array $criteria, ?string $title = null): void
    {
        if ($title !== null && $rubric->title !== $title) {
            throw new RuntimeException('The persisted rubric title diverges from the M6A contract.');
        }
        foreach ([
            'criterion_score_min' => '0.0000',
            'criterion_score_max' => '10.0000',
            'criterion_score_step' => '0.5000',
            'total_weight' => '100.0000',
            'total_score_min' => '0.0000',
            'total_score_max' => '100.0000',
        ] as $field => $expected) {
            if (bccomp((string) $rubric->{$field}, $expected, 4) !== 0) {
                throw new RuntimeException('The persisted rubric numeric contract diverges from M6A.');
            }
        }

        $actual = DB::table('rubric_criteria')->where('rubric_version_id', $rubric->id)->orderBy('sort_order')->get();
        if ($actual->count() !== count($criteria)) {
            throw new RuntimeException('The persisted rubric criterion count diverges from M6A.');
        }
        foreach ($criteria as $index => $expected) {
            $row = $actual[$index];
            if ($row->code !== $expected['code'] || $row->label !== $expected['label']
                || (int) $row->sort_order !== $expected['sort_order']
                || bccomp((string) $row->weight, $expected['weight'], 4) !== 0
                || $row->description !== null) {
                throw new RuntimeException('The persisted rubric criteria diverge from M6A.');
            }
        }
    }
};
