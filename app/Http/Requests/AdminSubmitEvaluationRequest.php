<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminSubmitEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');

        return $evaluation instanceof Evaluation && (bool) $this->user()?->can('manageReopened', $evaluation);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'current_password' => ['required', 'string', 'current_password'],
            'confirm_submission' => ['required', 'accepted'],
            'confirm_acting_on_behalf' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token', 'lock_version', 'current_password', 'confirm_submission', 'confirm_acting_on_behalf']) !== []) {
                $validator->errors()->add('evaluation', 'La confirmación administrativa contiene campos no autorizados.');
            }
        }];
    }
}
