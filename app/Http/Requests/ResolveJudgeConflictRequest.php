<?php

namespace App\Http\Requests;

use App\Models\JudgeConflict;
use Illuminate\Foundation\Http\FormRequest;

class ResolveJudgeConflictRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'judge_profile' => trim((string) $this->input('judge_profile')),
            'notify_judge' => $this->boolean('notify_judge'),
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
            'judge_profile' => ['required', 'string', 'ulid'],
            'notify_judge' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'judge_profile.required' => 'Selecciona un juez operativo.',
            'judge_profile.ulid' => 'El juez seleccionado no es válido.',
        ];
    }
}
