<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;

class CancelJudgeAssignmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $assignment = $this->route('judgeAssignment');

        return $assignment instanceof JudgeAssignment && (bool) $this->user()?->can('cancel', $assignment);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }
}
