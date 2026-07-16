<?php

namespace App\Http\Requests;

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
            'mobile_e164' => ['required', 'regex:/^\+[1-9]\d{7,14}$/'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'neighborhood' => ['required', 'string', 'max:180'],
            'adult_declaration' => ['accepted'],
            'resident_declaration' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_e164.regex' => 'Usa formato internacional E.164, por ejemplo +526621234567.',
            'birth_date.before_or_equal' => 'Debes tener 18 años cumplidos.',
            'adult_declaration.accepted' => 'Debes declarar que eres mayor de edad.',
            'resident_declaration.accepted' => 'Debes declarar residencia en Hermosillo.',
        ];
    }
}
