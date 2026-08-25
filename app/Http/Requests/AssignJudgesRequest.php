<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;

class AssignJudgesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'notify_judges' => $this->boolean('notify_judges'),
        ]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', JudgeAssignment::class);
    }

    public function rules(): array
    {
        return [
            'judge_profiles' => ['required', 'array', 'min:1'],
            'judge_profiles.*' => ['required', 'string', 'ulid', 'distinct'],
            'notify_judges' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }
}
