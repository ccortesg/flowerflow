<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeSubmission;
use App\Http\Requests\SubmissionDraftRequest;
use App\Http\Requests\SubmitSubmissionRequest;
use App\Mail\SubmissionReceived;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Services\ResilientMailDispatcher;
use App\Services\SubmissionContentSanitizer;
use App\Services\SubmissionFileStore;
use App\Support\MailDispatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmissionController extends Controller
{
    public function index(): View
    {
        return view('submissions.index', [
            'submissions' => request()->user()->submissions()->with('category')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        $this->assertProfileReady();
        $competition = Competition::query()
            ->with(['categories' => fn ($query) => $query->where('active', true)->orderBy('sort_order')])
            ->where('active', true)
            ->firstOrFail();

        return view('submissions.form', [
            'submission' => new Submission,
            'competition' => $competition,
            'wizardStep' => 1,
        ]);
    }

    public function store(SubmissionDraftRequest $request): RedirectResponse
    {
        $this->assertProfileReady();
        $user = $request->user();
        if ($user->submissions()->count() >= config('flowerflow.limits.submissions_per_user')) {
            throw ValidationException::withMessages(['category_public_id' => 'Ya alcanzaste el máximo de tres propuestas.']);
        }

        $competition = Competition::query()->where('active', true)->firstOrFail();
        $category = $this->categoryForCompetition($competition->id, $request->string('category_public_id')->toString());

        if ($user->submissions()->whereBelongsTo($competition)->whereBelongsTo($category)->exists()) {
            throw ValidationException::withMessages(['category_public_id' => 'Sólo puedes registrar una propuesta por categoría.']);
        }

        $submission = DB::transaction(function () use ($request, $user, $competition, $category): Submission {
            $team = $this->syncTeam($request, null);
            $submission = $user->submissions()->create([
                'competition_id' => $competition->id,
                'category_id' => $category->id,
                'team_id' => $team?->id,
                ...$this->stepOneAttributes($request),
            ]);
            $submission->events()->create(['actor_user_id' => $user->id, 'event' => 'draft_created', 'created_at' => now('UTC')]);

            return $submission;
        });

        $nextStep = $request->wizardAction() === 'continue' ? 2 : 1;

        return redirect()->route('submissions.edit', ['submission' => $submission, 'step' => $nextStep])
            ->with('status', 'Borrador guardado.');
    }

    public function show(Submission $submission, SubmissionContentSanitizer $sanitizer): View
    {
        $this->authorize('view', $submission);
        $submission->load(['category', 'competition', 'team.members', 'files', 'externalLinks']);

        $safeHtml = $sanitizer->sanitize($submission->description_html ?? '');

        return view('submissions.show', compact('submission', 'safeHtml'));
    }

    public function edit(Submission $submission): View
    {
        $this->authorize('update', $submission);
        $submission->load(['team.members', 'files', 'externalLinks']);
        $competition = Competition::query()
            ->with(['categories' => fn ($query) => $query->where('active', true)->orderBy('sort_order')])
            ->findOrFail($submission->competition_id);
        $wizardStep = $this->requestedWizardStep();

        return view('submissions.form', compact('submission', 'competition', 'wizardStep'));
    }

    public function update(
        SubmissionDraftRequest $request,
        Submission $submission,
        SubmissionContentSanitizer $sanitizer,
        SubmissionFileStore $fileStore
    ): RedirectResponse {
        $this->authorize('update', $submission);
        $step = $request->wizardStep();
        $category = null;

        if ($step === 1) {
            $category = $this->categoryForCompetition(
                $submission->competition_id,
                $request->string('category_public_id')->toString()
            );

            $duplicate = $request->user()->submissions()->where('competition_id', $submission->competition_id)
                ->where('category_id', $category->id)->whereKeyNot($submission->id)->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['category_public_id' => 'Sólo puedes registrar una propuesta por categoría.']);
            }
        }

        DB::transaction(function () use ($request, $submission, $category, $sanitizer, $fileStore, $step): void {
            $submission = Submission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($step === 1) {
                $team = $this->syncTeam($request, $submission->team);
                $submission->update([
                    'category_id' => $category->id,
                    'team_id' => $team?->id,
                    ...$this->stepOneAttributes($request),
                ]);
            }

            if ($step === 2) {
                $submission->update($this->stepTwoAttributes($request, $sanitizer));
            }

            if ($step === 3) {
                $this->storeStepThree($request, $submission, $fileStore);
            }

            $submission->events()->create([
                'actor_user_id' => $request->user()->id,
                'event' => 'draft_updated',
                'metadata' => ['wizard_step' => $step, 'wizard_action' => $request->wizardAction()],
                'created_at' => now('UTC'),
            ]);
        });

        if ($request->wizardAction() === 'continue' && $step === 3) {
            return redirect()->route('submissions.show', $submission)->with('status', 'Borrador guardado. Revisa todo antes de enviarlo.');
        }

        $nextStep = $request->wizardAction() === 'continue' ? min($step + 1, 3) : $step;

        return redirect()->route('submissions.edit', ['submission' => $submission, 'step' => $nextStep])
            ->with('status', 'Borrador guardado.');
    }

    public function submit(
        SubmitSubmissionRequest $request,
        Submission $submission,
        FinalizeSubmission $action,
        MailDispatchStatus $mailStatus
    ): RedirectResponse {
        $this->authorize('submit', $submission);
        $result = $action->execute($submission, $request->user(), $request->validated(), $request->header('Idempotency-Key'));

        $response = redirect()->route('submissions.show', $result)
            ->with('status', 'Propuesta enviada con folio '.$result->folio.'. Programamos el correo de confirmación.');

        return $mailStatus->failed()
            ? $response->with('warning', $mailStatus->warning())
            : $response;
    }

    public function resendConfirmation(
        Request $request,
        Submission $submission,
        ResilientMailDispatcher $mailDispatcher,
        MailDispatchStatus $mailStatus
    ): RedirectResponse {
        $this->authorize('view', $submission);
        abort_unless($submission->user_id === $request->user()->id && $submission->status === 'submitted', 403);

        $mailDispatcher->queue(
            $request->user(),
            new SubmissionReceived($submission),
            'No pudimos programar nuevamente el correo de confirmación. Conserva tu folio e inténtalo más tarde.'
        );

        return $mailStatus->failed()
            ? back()->with('warning', $mailStatus->warning())
            : back()->with('status', 'Programamos nuevamente el correo de confirmación. Revisa también el correo no deseado.');
    }

    public function download(Submission $submission, SubmissionFile $file): BinaryFileResponse
    {
        $this->authorize('view', $submission);
        abort_unless($file->submission_id === $submission->id, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroyFile(Submission $submission, SubmissionFile $file): RedirectResponse
    {
        $this->authorize('update', $submission);
        abort_unless($file->submission_id === $submission->id, 404);
        Storage::disk($file->disk)->delete($file->path);
        $submission->events()->create([
            'actor_user_id' => request()->user()->id,
            'event' => 'file_deleted',
            'metadata' => ['public_id' => $file->public_id, 'sha256' => $file->sha256, 'size_bytes' => $file->size_bytes],
            'created_at' => now('UTC'),
        ]);
        $file->delete();

        return back()->with('status', 'Archivo eliminado.');
    }

    private function assertProfileReady(): void
    {
        if (! request()->user()->profile?->isComplete()) {
            throw ValidationException::withMessages(['profile' => 'Completa tu perfil antes de registrar propuestas.']);
        }
    }

    private function stepOneAttributes(SubmissionDraftRequest $request): array
    {
        return [
            'participation_type' => $request->string('participation_type'),
            'title' => $request->string('title')->trim(),
            'summary' => $request->string('summary')->trim(),
        ];
    }

    private function stepTwoAttributes(
        SubmissionDraftRequest $request,
        SubmissionContentSanitizer $sanitizer
    ): array {
        return [
            'description_delta' => $request->filled('description_delta')
                ? json_decode($request->input('description_delta'), true, flags: JSON_THROW_ON_ERROR)
                : null,
            'description_html' => $sanitizer->sanitize($request->string('description_html')->toString()),
            'description_text' => trim($request->string('description_text')->toString()),
        ];
    }

    private function storeStepThree(
        SubmissionDraftRequest $request,
        Submission $submission,
        SubmissionFileStore $fileStore
    ): void {
        $incomingBytes = collect([...$request->file('documents', []), ...$request->file('editor_images', [])])
            ->sum(fn ($file) => $file->getSize());

        if ($submission->files()->sum('size_bytes') + $incomingBytes > config('flowerflow.limits.upload_kib') * 1024) {
            throw ValidationException::withMessages([
                'documents' => 'El total acumulado de documentos e imágenes no puede superar 10 MiB.',
            ]);
        }

        $this->syncLinks($submission, $request);
        foreach ($request->file('documents', []) as $file) {
            $fileStore->store($submission, $file);
        }
        foreach ($request->file('editor_images', []) as $file) {
            $fileStore->store($submission, $file, 'editor_image');
        }
    }

    private function categoryForCompetition(int $competitionId, string $publicId): Category
    {
        $category = Category::query()
            ->where('public_id', $publicId)
            ->where('competition_id', $competitionId)
            ->where('active', true)
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_public_id' => 'Selecciona una categoría activa de esta convocatoria.',
            ]);
        }

        return $category;
    }

    private function requestedWizardStep(): int
    {
        $step = request()->integer('step', 1);

        return in_array($step, [1, 2, 3], true) ? $step : 1;
    }

    private function syncTeam(SubmissionDraftRequest $request, ?Team $team): ?Team
    {
        if ($request->string('participation_type')->toString() !== 'team') {
            $team?->delete();

            return null;
        }

        $team ??= Team::create(['owner_user_id' => $request->user()->id, 'name' => $request->string('team_name')]);
        $team->update(['name' => $request->string('team_name'), 'eligibility_declared_at' => now('UTC')]);
        $team->members()->delete();
        $team->members()->create([
            'full_name' => $request->user()->name,
            'email' => $request->user()->email,
            'is_representative' => true,
        ]);
        foreach ($request->input('team_members', []) as $member) {
            $team->members()->create([...$member, 'is_representative' => false]);
        }

        return $team;
    }

    private function syncLinks(Submission $submission, SubmissionDraftRequest $request): void
    {
        foreach (['youtube_url' => 'youtube', 'public_folder_url' => 'public_folder'] as $field => $kind) {
            if ($request->filled($field)) {
                $url = $request->string($field)->toString();
                $submission->externalLinks()->updateOrCreate(['kind' => $kind], [
                    'url' => $url,
                    'normalized_host' => strtolower((string) parse_url($url, PHP_URL_HOST)),
                ]);
            } else {
                $submission->externalLinks()->where('kind', $kind)->delete();
            }
        }
    }
}
