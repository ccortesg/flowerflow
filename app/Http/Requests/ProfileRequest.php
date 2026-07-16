<?php

namespace App\Http\Requests;

use App\Support\MexicoPhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_names' => ['required', 'string', 'max:120'],
            'last_names' => ['required', 'string', 'max:120'],
            'mobile_national' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (MexicoPhoneNumber::toE164((string) $value) === null) {
                        $fail('Escribe los 10 dígitos de tu celular, por ejemplo 662 123 4567.');
                    }
                },
            ],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'future_activities_opt_in' => ['nullable', 'boolean'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'neighborhood' => ['required', 'string', 'max:180'],
            'adult_declaration' => ['accepted'],
            'resident_declaration' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'birth_date.before_or_equal' => 'Debes tener 18 años cumplidos.',
            'adult_declaration.accepted' => 'Debes declarar que eres mayor de edad.',
            'resident_declaration.accepted' => 'Debes declarar residencia en Hermosillo.',
        ];
    }
}
