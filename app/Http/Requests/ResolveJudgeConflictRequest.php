<?php

namespace App\Http\Requests;

use App\Models\JudgeConflict;
use Illuminate\Foundation\Http\FormRequest;

class ResolveJudgeConflictRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'substitute_judge_profile' => trim((string) $this->input('substitute_judge_profile')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    public function authorize(): bool
    {
        $conflict = $this->route('judgeConflict');

        return $conflict instanceof JudgeConflict
            && (bool) $this->user()?->can('resolve', $conflict);
    }

    public function rules(): array
    {
        return [
            'substitute_judge_profile' => ['required', 'string', 'ulid'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'substitute_judge_profile.required' => 'Selecciona uno de los dos jueces sustitutos operativos.',
            'substitute_judge_profile.ulid' => 'El juez sustituto seleccionado no es válido.',
        ];
    }
}
