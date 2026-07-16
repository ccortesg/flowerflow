<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accept_call_rules' => ['accepted'],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
        ];
    }
}
