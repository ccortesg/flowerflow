<?php

namespace App\Http\Requests;

use App\Models\RubricVersion;
use Illuminate\Foundation\Http\FormRequest;

class ActivateRubricVersionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $rubric = $this->route('rubricVersion');

        return $rubric instanceof RubricVersion && (bool) $this->user()?->can('activate', $rubric);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Escribe la razón de activación.',
            'reason.min' => 'La razón debe tener al menos 20 caracteres.',
            'current_password.current_password' => 'La contraseña administrativa no es correcta.',
        ];
    }
}
