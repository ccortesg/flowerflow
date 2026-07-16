<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeSubmission;
use App\Http\Requests\SubmissionDraftRequest;
use App\Http\Requests\SubmitSubmissionRequest;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Services\SubmissionContentSanitizer;
use App\Services\SubmissionFileStore;
use Illuminate\Http\RedirectResponse;
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
        $competition = Competition::query()->with('categories')->where('active', true)->firstOrFail();

        return view('submissions.form', ['submission' => new Submission, 'competition' => $competition]);
    }

    public function store(
        SubmissionDraftRequest $request,
        SubmissionContentSanitizer $sanitizer,
        SubmissionFileStore $fileStore
    ): RedirectResponse {
        $this->assertProfileReady();
        $user = $request->user();
        if ($user->submissions()->count() >= config('flowerflow.limits.submissions_per_user')) {
            throw ValidationException::withMessages(['category_public_id' => 'Ya alcanzaste el máximo de tres propuestas.']);
        }

        $competition = Competition::query()->where('active', true)->firstOrFail();
        $category = Category::query()->where('public_id', $request->string('category_public_id'))->whereBelongsTo($competition)->firstOrFail();

        if ($user->submissions()->whereBelongsTo($competition)->whereBelongsTo($category)->exists()) {
            throw ValidationException::withMessages(['category_public_id' => 'Sólo puedes registrar una propuesta por categoría.']);
        }

        $submission = DB::transaction(function () use ($request, $user, $competition, $category, $sanitizer, $fileStore): Submission {
            $team = $this->syncTeam($request, null);
            $submission = $user->submissions()->create([
                'competition_id' => $competition->id,
                'category_id' => $category->id,
                'team_id' => $team?->id,
                ...$this->draftAttributes($request, $sanitizer),
            ]);
            $this->syncLinks($submission, $request);
            foreach ($request->file('documents', []) as $file) {
                $fileStore->store($submission, $file);
            }
            foreach ($request->file('editor_images', []) as $file) {
                $fileStore->store($submission, $file, 'editor_image');
            }
            $submission->events()->create(['actor_user_id' => $user->id, 'event' => 'draft_created', 'created_at' => now('UTC')]);

            return $submission;
        });

        return redirect()->route('submissions.edit', $submission)->with('status', 'Borrador guardado.');
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
        $competition = Competition::query()->with('categories')->findOrFail($submission->competition_id);

        return view('submissions.form', compact('submission', 'competition'));
    }

    public function update(
        SubmissionDraftRequest $request,
        Submission $submission,
        SubmissionContentSanitizer $sanitizer,
        SubmissionFileStore $fileStore
    ): RedirectResponse {
        $this->authorize('update', $submission);
        $category = Category::query()->where('public_id', $request->string('category_public_id'))
            ->where('competition_id', $submission->competition_id)->firstOrFail();

        $duplicate = $request->user()->submissions()->where('competition_id', $submission->competition_id)
            ->where('category_id', $category->id)->whereKeyNot($submission->id)->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['category_public_id' => 'Sólo puedes registrar una propuesta por categoría.']);
        }

        DB::transaction(function () use ($request, $submission, $category, $sanitizer, $fileStore): void {
            $submission = Submission::query()->lockForUpdate()->findOrFail($submission->id);
            $incomingBytes = collect([...$request->file('documents', []), ...$request->file('editor_images', [])])
                ->sum(fn ($file) => $file->getSize());
            if ($submission->files()->sum('size_bytes') + $incomingBytes > config('flowerflow.limits.upload_kib') * 1024) {
                throw ValidationException::withMessages(['documents' => 'Los archivos de la propuesta no pueden superar 10 MiB acumulados.']);
            }
            $team = $this->syncTeam($request, $submission->team);
            $submission->update([
                'category_id' => $category->id,
                'team_id' => $team?->id,
                ...$this->draftAttributes($request, $sanitizer),
            ]);
            $this->syncLinks($submission, $request);
            foreach ($request->file('documents', []) as $file) {
                $fileStore->store($submission, $file);
            }
            foreach ($request->file('editor_images', []) as $file) {
                $fileStore->store($submission, $file, 'editor_image');
            }
            $submission->events()->create(['actor_user_id' => $request->user()->id, 'event' => 'draft_updated', 'created_at' => now('UTC')]);
        });

        return back()->with('status', 'Cambios guardados.');
    }

    public function submit(SubmitSubmissionRequest $request, Submission $submission, FinalizeSubmission $action): RedirectResponse
    {
        $this->authorize('submit', $submission);
        $result = $action->execute($submission, $request->user(), $request->validated(), $request->header('Idempotency-Key'));

        return redirect()->route('submissions.show', $result)->with('status', 'Propuesta enviada con folio '.$result->folio.'.');
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

    private function draftAttributes(SubmissionDraftRequest $request, SubmissionContentSanitizer $sanitizer): array
    {
        return [
            'participation_type' => $request->string('participation_type'),
            'title' => $request->string('title')->trim(),
            'summary' => $request->string('summary')->trim(),
            'description_delta' => json_decode($request->input('description_delta') ?: '{}', true, flags: JSON_THROW_ON_ERROR),
            'description_html' => $sanitizer->sanitize($request->string('description_html')->toString()),
            'description_text' => trim($request->string('description_text')->toString()),
        ];
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
