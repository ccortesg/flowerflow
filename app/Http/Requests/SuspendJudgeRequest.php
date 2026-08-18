<?php

namespace App\Http\Requests;

use App\Models\JudgeProfile;
use Illuminate\Foundation\Http\FormRequest;

class SuspendJudgeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $profile = $this->route('judgeProfile');

        return $profile instanceof JudgeProfile && (bool) $this->user()?->can('manage', $profile);
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
            'reason.min' => 'La razón debe tener al menos 20 caracteres.',
            'current_password.current_password' => 'La contraseña administrativa no es correcta.',
        ];
    }
}
