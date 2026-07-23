<?php

namespace App\Http\Requests;

use App\Enums\EligibilityReviewStatus;
use App\Models\EligibilityReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideEligibilityReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof EligibilityReview && (bool) $this->user()?->can('decide', $review);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([
                EligibilityReviewStatus::Admitted->value,
                EligibilityReviewStatus::NotAdmitted->value,
            ])],
            'participant_reason' => ['required', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'confirm_resolution' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Selecciona la resolución de admisibilidad.',
            'participant_reason.required' => 'Escribe un motivo claro que pueda consultar la persona participante.',
            'confirm_resolution.accepted' => 'Confirma expresamente que deseas registrar esta resolución.',
        ];
    }
}
