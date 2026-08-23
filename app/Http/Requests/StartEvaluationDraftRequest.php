<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StartEvaluationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('judgeAssignment');

        return $assignment instanceof JudgeAssignment
            && (bool) $this->user()?->can('startEvaluationDraft', $assignment);
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token']) !== []) {
                $validator->errors()->add('evaluation', 'La solicitud contiene campos no autorizados.');
            }
        }];
    }
}
