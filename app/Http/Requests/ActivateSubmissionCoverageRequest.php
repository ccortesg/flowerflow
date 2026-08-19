<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;

class ActivateSubmissionCoverageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', JudgeAssignment::class);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }
}
