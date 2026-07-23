<?php

namespace App\Http\Requests;

use App\Enums\ResidencyDocumentType;
use App\Models\ResidencyDocumentRequest;
use App\Services\PrivateEvidenceFileInspector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UploadResidencyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $residencyRequest = $this->route('residencyRequest');

        return $residencyRequest instanceof ResidencyDocumentRequest
            && (bool) $this->user()?->can('upload', $residencyRequest);
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::enum(ResidencyDocumentType::class)],
            'equivalent_description' => ['nullable', 'required_if:document_type,equivalent', 'string', 'max:1000'],
            'documents' => ['required', 'array', 'min:1', 'max:'.config('flowerflow.admissibility.files_per_person_request')],
            'documents.*' => ['required', 'file', 'max:'.config('flowerflow.admissibility.files_total_kib_per_person_request')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $request = $this->route('residencyRequest');
            if (! $request instanceof ResidencyDocumentRequest) {
                return;
            }

            $files = $this->file('documents', []);
            $existingCount = $request->documents()->count();
            $existingBytes = (int) $request->documents()->sum('size_bytes');
            $newBytes = collect($files)->sum(fn ($file) => $file->getSize());

            if ($existingCount + count($files) > config('flowerflow.admissibility.files_per_person_request')) {
                $validator->errors()->add('documents', 'Esta persona ya alcanzó el máximo de tres archivos para la solicitud.');
            }
            if ($existingBytes + $newBytes > config('flowerflow.admissibility.files_total_kib_per_person_request') * 1024) {
                $validator->errors()->add('documents', 'Los documentos de esta persona no pueden superar 10 MiB acumulados.');
            }

            $inspector = app(PrivateEvidenceFileInspector::class);
            foreach ($files as $index => $file) {
                if ($error = $inspector->inspect($file)) {
                    $validator->errors()->add("documents.$index", $error);
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => 'Selecciona el tipo de comprobante.',
            'equivalent_description.required_if' => 'Describe por qué consideras que este documento es equivalente.',
            'documents.required' => 'Selecciona al menos un archivo.',
            'documents.max' => 'Puedes cargar como máximo tres archivos por persona y solicitud.',
            'documents.*.max' => 'Cada archivo debe pesar 10 MiB o menos.',
        ];
    }
}
