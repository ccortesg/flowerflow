<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('judgeAssignment');

        return $assignment instanceof JudgeAssignment
            && (bool) $this->user()?->can('submitEvaluation', $assignment);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'confirm_submission' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token', 'lock_version', 'confirm_submission']) !== []) {
                $validator->errors()->add('evaluation', 'La confirmación contiene campos no autorizados.');
            }
        }];
    }
}
