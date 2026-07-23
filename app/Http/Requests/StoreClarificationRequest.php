<?php

namespace App\Http\Requests;

use App\Models\EligibilityReview;
use Illuminate\Foundation\Http\FormRequest;

class StoreClarificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof EligibilityReview && (bool) $this->user()?->can('requestClarification', $review);
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'due_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Explica claramente qué información debe aclararse.',
            'message.max' => 'La solicitud no puede exceder 2,000 caracteres.',
            'due_at.date_format' => 'La fecha límite no tiene un formato válido.',
        ];
    }
}
