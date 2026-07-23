<?php

namespace App\Http\Requests;

use App\Models\ClarificationRequest;
use App\Services\PrivateEvidenceFileInspector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RespondClarificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $clarification = $this->route('clarification');

        return $clarification instanceof ClarificationRequest && (bool) $this->user()?->can('respond', $clarification);
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:'.config('flowerflow.admissibility.clarification_response_characters')],
            'files' => ['nullable', 'array', 'max:'.config('flowerflow.admissibility.files_per_person_request')],
            'files.*' => ['file', 'max:'.config('flowerflow.admissibility.files_total_kib_per_person_request')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $clarification = $this->route('clarification');
            if (! $clarification instanceof ClarificationRequest) {
                return;
            }

            $existingCount = $clarification->responses()->withCount('files')->get()->sum('files_count');
            $existingBytes = $clarification->responses()->with('files:id,clarification_response_id,size_bytes')->get()
                ->flatMap->files->sum('size_bytes');
            $newFiles = $this->file('files', []);
            $newBytes = collect($newFiles)->sum(fn ($file) => $file->getSize());
            $maxFiles = config('flowerflow.admissibility.files_per_person_request');
            $maxBytes = config('flowerflow.admissibility.files_total_kib_per_person_request') * 1024;

            if ($existingCount + count($newFiles) > $maxFiles) {
                $validator->errors()->add('files', 'Puedes adjuntar como máximo tres archivos en esta aclaración.');
            }
            if ($existingBytes + $newBytes > $maxBytes) {
                $validator->errors()->add('files', 'Los archivos de esta aclaración no pueden superar 10 MiB acumulados.');
            }

            $inspector = app(PrivateEvidenceFileInspector::class);
            foreach ($newFiles as $index => $file) {
                if ($error = $inspector->inspect($file)) {
                    $validator->errors()->add("files.$index", $error);
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Escribe tu respuesta antes de enviarla.',
            'response.max' => 'La respuesta no puede exceder 2,000 caracteres.',
            'files.max' => 'Puedes adjuntar como máximo tres archivos.',
            'files.*.max' => 'Cada archivo debe pesar 10 MiB o menos.',
        ];
    }
}
