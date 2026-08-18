<?php

namespace App\Http\Controllers\Panel;

use App\Actions\Rubrics\ActivateRubricVersion;
use App\Actions\Rubrics\CreateRubricDraft;
use App\Actions\Rubrics\UpdateRubricDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateRubricVersionRequest;
use App\Http\Requests\StoreRubricVersionRequest;
use App\Http\Requests\UpdateRubricVersionRequest;
use App\Models\Competition;
use App\Models\RubricVersion;
use App\Services\EvaluationRubricContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RubricVersionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', RubricVersion::class);
        $competition = $this->competition();

        return view('panel.rubrics.index', [
            'competition' => $competition,
            'rubrics' => RubricVersion::query()
                ->where('competition_id', $competition->id)
                ->withCount('criteria')
                ->orderByDesc('version')
                ->paginate(20),
        ]);
    }

    public function create(EvaluationRubricContract $contract): View
    {
        Gate::authorize('create', RubricVersion::class);
        $competition = $this->competition();

        return view('panel.rubrics.create', [
            'competition' => $competition,
            'nextVersion' => ((int) $competition->rubricVersions()->max('version')) + 1,
            'versionAttributes' => $contract->versionAttributes(),
            'criteria' => $contract->criteria(),
        ]);
    }

    public function store(StoreRubricVersionRequest $request, CreateRubricDraft $create): RedirectResponse
    {
        $rubric = $create->execute(
            $request->user(),
            $this->competition(),
            $request->integer('version'),
            $request->string('title')->toString(),
            $request->versionAttributes(),
            $request->criteriaAttributes(),
        );

        return redirect()->route('panel.rubrics.show', $rubric)
            ->with('status', 'La versión quedó creada como borrador. No se activó automáticamente.');
    }

    public function show(RubricVersion $rubricVersion): View
    {
        Gate::authorize('view', $rubricVersion);

        return view('panel.rubrics.show', [
            'rubric' => $rubricVersion->load([
                'competition:id,name,slug',
                'criteria',
                'createdBy:id,name',
                'lastEditedBy:id,name',
                'activatedBy:id,name',
                'supersededBy:id,name',
            ]),
        ]);
    }

    public function edit(RubricVersion $rubricVersion): View
    {
        Gate::authorize('update', $rubricVersion);

        return view('panel.rubrics.edit', [
            'rubric' => $rubricVersion->load('criteria'),
            'versionAttributes' => collect(app(EvaluationRubricContract::class)->versionAttributes())
                ->mapWithKeys(fn ($value, string $field) => [$field => $rubricVersion->getAttribute($field)])
                ->all(),
            'criteria' => $rubricVersion->criteria->map(fn ($criterion) => [
                'code' => $criterion->code,
                'label' => $criterion->label,
                'description' => $criterion->description,
                'weight' => $criterion->weight,
                'min_score' => $criterion->min_score,
                'max_score' => $criterion->max_score,
                'score_step' => $criterion->score_step,
                'sort_order' => $criterion->sort_order,
            ])->all(),
        ]);
    }

    public function update(
        UpdateRubricVersionRequest $request,
        RubricVersion $rubricVersion,
        UpdateRubricDraft $update,
    ): RedirectResponse {
        $rubric = $update->execute(
            $rubricVersion,
            $request->user(),
            $request->string('title')->toString(),
            $request->versionAttributes(),
            $request->criteriaAttributes(),
        );

        return redirect()->route('panel.rubrics.show', $rubric)
            ->with('status', 'El borrador quedó actualizado dentro del contrato aprobado.');
    }

    public function activate(
        ActivateRubricVersionRequest $request,
        RubricVersion $rubricVersion,
        ActivateRubricVersion $activate,
    ): RedirectResponse {
        $rubric = $activate->execute(
            $rubricVersion,
            $request->user(),
            $request->string('reason')->toString(),
        );

        return redirect()->route('panel.rubrics.show', $rubric)
            ->with('status', 'La versión quedó activa y la versión activa anterior, si existía, fue conservada como sustituida.');
    }

    private function competition(): Competition
    {
        return Competition::query()
            ->where('slug', 'hermosillo-florece-2026')
            ->where('active', true)
            ->firstOrFail();
    }
}
