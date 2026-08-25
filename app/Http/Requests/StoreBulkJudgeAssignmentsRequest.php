<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreBulkJudgeAssignmentsRequest extends FormRequest
{
    private const ALLOWED_KEYS = [
        '_token',
        'intent',
        'confirm_admission',
        'confirm_packages',
        'confirm_assignment',
    ];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->hasExactRoles(['admin'])
            && $user->can('decide admissibility')
            && $user->can('manage blind review packages')
            && $user->can('manage evaluation assignments');
    }

    public function rules(): array
    {
        return [
            'intent' => ['required', 'string'],
            'confirm_admission' => ['required', 'accepted'],
            'confirm_packages' => ['required', 'accepted'],
            'confirm_assignment' => ['required', 'accepted'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), self::ALLOWED_KEYS) !== []) {
                $validator->errors()->add('request', 'La solicitud contiene campos no permitidos.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'confirm_admission.accepted' => 'Confirma que deseas admitir los expedientes elegibles.',
            'confirm_packages.accepted' => 'Confirma que deseas generar o activar los paquetes ciegos.',
            'confirm_assignment.accepted' => 'Confirma que deseas crear las asignaciones.',
        ];
    }
}
