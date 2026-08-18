<?php

namespace App\Http\Requests;

use App\Enums\JudgeAssignmentRole;
use App\Models\JudgeProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreJudgeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'assignment_role' => trim((string) $this->input('assignment_role')),
        ]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', JudgeProfile::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'assignment_role' => ['required', Rule::enum(JudgeAssignmentRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre del juez.',
            'email.required' => 'Escribe el correo del juez.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.unique' => 'Ya existe una cuenta con este correo. No se modificó ningún rol ni perfil.',
            'assignment_role.required' => 'Selecciona si el juez será principal o sustituto.',
            'assignment_role.enum' => 'Selecciona un tipo de juez válido.',
        ];
    }
}
