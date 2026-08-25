<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class ReviewBulkJudgeAssignmentsRequest extends FormRequest
{
    private const ALLOWED_KEYS = [
        '_token',
        'judge_profile',
        'submissions',
        'participant_reason',
        'internal_notes',
        'package_reason',
        'assignment_reason',
        'notify_judge',
        'current_password',
    ];

    protected function prepareForValidation(): void
    {
        $this->merge([
            'participant_reason' => trim((string) $this->input('participant_reason')),
            'internal_notes' => trim((string) $this->input('internal_notes')) ?: null,
            'package_reason' => trim((string) $this->input('package_reason')),
            'assignment_reason' => trim((string) $this->input('assignment_reason')),
            'notify_judge' => $this->boolean('notify_judge'),
        ]);
    }

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
            'judge_profile' => ['required', 'string', 'ulid'],
            'submissions' => ['required', 'array', 'min:1', 'max:'.config('flowerflow.bulk_judge_assignment.limit')],
            'submissions.*' => ['required', 'string', 'ulid', 'distinct'],
            'participant_reason' => ['required', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'package_reason' => ['required', 'string', 'min:20', 'max:1000'],
            'assignment_reason' => ['required', 'string', 'min:20', 'max:1000'],
            'notify_judge' => ['required', 'boolean'],
            'current_password' => ['required', 'string', 'current_password'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $unexpected = array_diff(array_keys($this->all()), self::ALLOWED_KEYS);
            if ($unexpected !== []) {
                $validator->errors()->add('request', 'La solicitud contiene campos no permitidos.');
            }
            if ($this->boolean('notify_judge')
                && (! config('flowerflow.flags.communication_ledger')
                    || ! config('flowerflow.judge_notifications.assignment_enabled'))) {
                $validator->errors()->add('notify_judge', 'La bitácora y las notificaciones de asignación deben estar habilitadas para solicitar el correo consolidado.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'submissions.max' => 'Selecciona como máximo '.config('flowerflow.bulk_judge_assignment.limit').' propuestas.',
            'participant_reason.required' => 'Escribe el motivo público que se registrará en los expedientes nuevos.',
            'package_reason.min' => 'La razón del paquete debe tener al menos 20 caracteres.',
            'assignment_reason.min' => 'La razón de asignación debe tener al menos 20 caracteres.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
        ];
    }
}
