<?php

namespace App\Http\Requests;

use App\Enums\JudgeConflictType;
use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeclareJudgeConflictRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['explanation' => trim((string) $this->input('explanation')) ?: null]);
    }

    public function authorize(): bool
    {
        $assignment = $this->route('judgeAssignment');

        return $assignment instanceof JudgeAssignment
            && (bool) $this->user()?->can('declareConflict', $assignment);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(JudgeConflictType::class)],
            'explanation' => [
                'nullable',
                'string',
                'max:1000',
                Rule::requiredIf(fn (): bool => $this->input('type') === JudgeConflictType::Other->value),
                Rule::when($this->input('type') === JudgeConflictType::Other->value, ['min:20'], ['prohibited']),
            ],
        ];
    }
}
