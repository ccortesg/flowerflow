<?php

namespace App\Http\Requests;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;

class AdministrativeFinalizeSubmissionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission
            && (bool) $this->user()?->can('administrativelyFinalize', $submission);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'confirm_administrative_finalization' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'La razón administrativa es obligatoria.',
            'reason.min' => 'La razón debe tener al menos 20 caracteres.',
            'confirm_administrative_finalization.required' => 'Debes confirmar que comprendes la excepción administrativa.',
            'confirm_administrative_finalization.accepted' => 'Confirma que comprendes la excepción administrativa antes de continuar.',
        ];
    }
}
