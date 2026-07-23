<?php

namespace App\Http\Requests;

use App\Models\EligibilityReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof EligibilityReview && (bool) $this->user()?->can('review', $review);
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::in(['representative', 'team_member'])],
            'subject_team_member_id' => ['nullable', 'required_if:subject_type,team_member', 'integer', 'exists:team_members,id'],
            'clarification_public_id' => ['nullable', 'exists:clarification_requests,public_id'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_type.required' => 'Selecciona a la persona cuya residencia debe verificarse.',
            'subject_team_member_id.required_if' => 'Selecciona un integrante del equipo.',
            'subject_team_member_id.exists' => 'El integrante seleccionado no existe.',
        ];
    }
}
