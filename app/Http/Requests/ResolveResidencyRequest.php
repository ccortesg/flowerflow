<?php

namespace App\Http\Requests;

use App\Enums\ResidencyVerificationStatus;
use App\Models\ResidencyDocumentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveResidencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('residencyRequest');

        return $request instanceof ResidencyDocumentRequest && (bool) $this->user()?->can('review', $request);
    }

    public function rules(): array
    {
        return [
            'residency_status' => ['required', Rule::in([
                ResidencyVerificationStatus::Verified->value,
                ResidencyVerificationStatus::Rejected->value,
                ResidencyVerificationStatus::Cancelled->value,
            ])],
            'review_reason' => ['nullable', 'string', 'max:2000'],
            'confirm_resolution' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_resolution.accepted' => 'Confirma expresamente la resolución de residencia.',
        ];
    }
}
