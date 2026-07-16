<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Services\UploadedFileInspector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmissionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $members = collect($this->input('team_members', []))
            ->filter(fn ($member) => filled($member['full_name'] ?? null) || filled($member['email'] ?? null))
            ->values()->all();

        $this->merge(['team_members' => $members]);
    }

    public function rules(): array
    {
        $extensions = implode(',', config('flowerflow.allowed_document_extensions'));

        return [
            'category_public_id' => ['required', 'exists:categories,public_id'],
            'participation_type' => ['required', 'in:individual,team'],
            'team_name' => ['nullable', 'required_if:participation_type,team', 'string', 'max:180'],
            'team_members' => ['nullable', 'array', 'max:'.max(config('flowerflow.limits.team_members') - 1, 0)],
            'team_members.*.full_name' => ['required_with:team_members', 'string', 'max:180'],
            'team_members.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'team_eligibility' => ['exclude_unless:participation_type,team', 'required', 'accepted'],
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'description_delta' => ['nullable', 'json'],
            'description_html' => ['required', 'string', 'max:20000'],
            'description_text' => ['required', 'string', 'max:12000'],
            'youtube_url' => ['nullable', 'url:https', 'max:2048'],
            'public_folder_url' => ['nullable', 'url:https', 'max:2048'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'max:'.config('flowerflow.limits.upload_kib'), 'mimes:'.$extensions],
            'editor_images' => ['nullable', 'array', 'max:10'],
            'editor_images.*' => ['image', 'max:'.config('flowerflow.limits.upload_kib'), 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $inspector = app(UploadedFileInspector::class);
            foreach ($this->file('documents', []) as $index => $file) {
                if ($error = $inspector->inspect($file, 'document')) {
                    $validator->errors()->add("documents.$index", $error);
                }
            }
            foreach ($this->file('editor_images', []) as $index => $file) {
                if ($error = $inspector->inspect($file, 'editor_image')) {
                    $validator->errors()->add("editor_images.$index", $error);
                }
            }

            $this->validateHost($validator, 'youtube_url', config('flowerflow.external_links.video_hosts'));
            $this->validateHost($validator, 'public_folder_url', config('flowerflow.external_links.folder_hosts'));

            $existing = $this->route('submission');
            $existingBytes = $existing instanceof Submission ? (int) $existing->files()->sum('size_bytes') : 0;
            $newBytes = collect([
                ...$this->file('documents', []),
                ...$this->file('editor_images', []),
            ])->sum(fn ($file) => $file->getSize());
            $limit = config('flowerflow.limits.upload_kib') * 1024;

            if ($existingBytes + $newBytes > $limit) {
                $validator->errors()->add('documents', 'Los archivos de la propuesta no pueden superar 10 MiB acumulados.');
            }
        }];
    }

    private function validateHost(Validator $validator, string $field, array $hosts): void
    {
        if (! $this->filled($field)) {
            return;
        }

        $host = strtolower((string) parse_url($this->string($field)->toString(), PHP_URL_HOST));
        if (! in_array($host, $hosts, true)) {
            $validator->errors()->add($field, 'El dominio del enlace no está permitido.');
        }
    }
}
