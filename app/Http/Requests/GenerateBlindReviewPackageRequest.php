<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBlindReviewPackageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) $user?->hasExactRoles(['admin']) && $user->can('manage blind review packages');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }
}
