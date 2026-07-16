<?php

namespace App\Http\Requests;

use App\Models\Submission;
use App\Services\UploadedFileInspector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmissionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return ! ($submission instanceof Submission) || (bool) $this->user()?->can('update', $submission);
    }

    protected function prepareForValidation(): void
    {
        if ((int) $this->input('wizard_step') !== 1) {
            return;
        }

        $members = collect($this->input('team_members', []))
            ->filter(fn ($member) => filled($member['full_name'] ?? null) || filled($member['email'] ?? null))
            ->values()
            ->all();

        $this->merge(['team_members' => $members]);
    }

    public function rules(): array
    {
        $baseRules = [
            'wizard_step' => ['required', 'integer', Rule::in([1, 2, 3])],
            'wizard_action' => ['required', 'string', Rule::in(['save', 'continue'])],
        ];

        return [
            ...$baseRules,
            ...match ($this->wizardStep()) {
                2 => $this->stepTwoRules(),
                3 => $this->stepThreeRules(),
                default => $this->stepOneRules(),
            },
        ];
    }

    public function messages(): array
    {
        return [
            'category_public_id.required' => 'Selecciona la categoría de tu propuesta.',
            'participation_type.required' => 'Selecciona si participarás de forma individual o en equipo.',
            'team_name.required_if' => 'Escribe el nombre del equipo.',
            'team_eligibility.required' => 'Confirma la elegibilidad de todas las personas del equipo.',
            'team_eligibility.accepted' => 'Confirma la elegibilidad de todas las personas del equipo.',
            'team_members.max' => 'Puedes agregar hasta cuatro integrantes además de la persona representante.',
            'team_members.*.full_name.required' => 'Escribe el nombre de cada integrante agregado.',
            'title.required' => 'Escribe el título del proyecto.',
            'summary.required' => 'Escribe un resumen breve del proyecto.',
            'description_delta.required' => 'Escribe la descripción detallada del proyecto.',
            'description_html.required' => 'Escribe la descripción detallada del proyecto.',
            'description_text.required' => 'Escribe la descripción detallada del proyecto antes de continuar.',
            'documents.*.mimes' => 'El documento seleccionado no tiene un formato permitido.',
            'editor_images.*.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WebP.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_public_id' => 'categoría',
            'participation_type' => 'modalidad de participación',
            'team_name' => 'nombre del equipo',
            'team_members.*.full_name' => 'nombre del integrante',
            'team_members.*.email' => 'correo del integrante',
            'team_eligibility' => 'declaración de elegibilidad',
            'title' => 'título del proyecto',
            'summary' => 'resumen breve',
            'description_text' => 'descripción detallada',
            'youtube_url' => 'video de YouTube',
            'public_folder_url' => 'carpeta pública',
            'documents.*' => 'archivo del proyecto',
            'editor_images.*' => 'imagen de apoyo',
        ];
    }

    public function after(): array
    {
        if ($this->wizardStep() !== 3) {
            return [];
        }

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
                $validator->errors()->add('documents', 'El total acumulado de documentos e imágenes no puede superar 10 MiB.');
            }
        }];
    }

    public function wizardStep(): int
    {
        $step = (int) $this->input('wizard_step', 1);

        return in_array($step, [1, 2, 3], true) ? $step : 1;
    }

    public function wizardAction(): string
    {
        return $this->input('wizard_action') === 'continue' ? 'continue' : 'save';
    }

    private function stepOneRules(): array
    {
        return [
            'category_public_id' => ['required', 'exists:categories,public_id'],
            'participation_type' => ['required', Rule::in(['individual', 'team'])],
            'team_name' => ['nullable', 'required_if:participation_type,team', 'string', 'max:180'],
            'team_members' => ['nullable', 'array', 'max:'.max(config('flowerflow.limits.team_members') - 1, 0)],
            'team_members.*.full_name' => ['required', 'string', 'max:180'],
            'team_members.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'team_eligibility' => ['exclude_unless:participation_type,team', 'required', 'accepted'],
            'title' => ['required', 'string', 'max:'.config('flowerflow.limits.submission_title_characters')],
            'summary' => ['required', 'string', 'max:'.config('flowerflow.limits.submission_summary_characters')],
        ];
    }

    private function stepTwoRules(): array
    {
        $presence = $this->wizardAction() === 'continue' ? 'required' : 'nullable';

        return [
            'description_delta' => [$presence, 'json'],
            'description_html' => [$presence, 'string', 'max:'.config('flowerflow.limits.submission_description_html_characters')],
            'description_text' => [$presence, 'string', 'max:'.config('flowerflow.limits.submission_description_text_characters')],
        ];
    }

    private function stepThreeRules(): array
    {
        $extensions = implode(',', config('flowerflow.allowed_document_extensions'));

        return [
            'youtube_url' => ['nullable', 'url:https', 'max:2048'],
            'public_folder_url' => ['nullable', 'url:https', 'max:2048'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'max:'.config('flowerflow.limits.upload_kib'), 'mimes:'.$extensions],
            'editor_images' => ['nullable', 'array', 'max:10'],
            'editor_images.*' => ['image', 'max:'.config('flowerflow.limits.upload_kib'), 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    private function validateHost(Validator $validator, string $field, array $hosts): void
    {
        if (! $this->filled($field)) {
            return;
        }

        $url = $this->string($field)->toString();
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $hasCredentials = filled($parts['user'] ?? null) || filled($parts['pass'] ?? null);

        if (($parts['scheme'] ?? null) !== 'https' || $hasCredentials || ! in_array($host, $hosts, true)) {
            $validator->errors()->add($field, 'Usa un enlace HTTPS de uno de los proveedores permitidos, sin credenciales integradas.');
        }
    }
}
