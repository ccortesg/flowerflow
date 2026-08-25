<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReopenEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');

        return $evaluation instanceof Evaluation && (bool) $this->user()?->can('reopen', $evaluation);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
            'confirm_reopen' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token', 'lock_version', 'reason', 'current_password', 'confirm_reopen']) !== []) {
                $validator->errors()->add('evaluation', 'La reapertura contiene campos no autorizados.');
            }
        }];
    }
}
