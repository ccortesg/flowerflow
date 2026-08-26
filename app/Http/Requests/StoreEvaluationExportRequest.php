<?php

namespace App\Http\Requests;

use App\Enums\EvaluationExportScope;
use App\Models\EvaluationExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EvaluationExport::class) ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'scope_version' => ['nullable', Rule::enum(EvaluationExportScope::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scope_version.enum' => 'Selecciona un alcance de exportación válido.',
        ];
    }

    public function exportScope(): EvaluationExportScope
    {
        return EvaluationExportScope::tryFrom((string) ($this->validated()['scope_version'] ?? ''))
            ?? EvaluationExportScope::AllRevisions;
    }
}
