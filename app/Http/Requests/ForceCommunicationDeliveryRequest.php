<?php

namespace App\Http\Requests;

use App\Enums\CommunicationDeliveryStatus;
use App\Models\CommunicationDelivery;
use Illuminate\Foundation\Http\FormRequest;

class ForceCommunicationDeliveryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        $delivery = $this->route('communicationDelivery');

        return $delivery instanceof CommunicationDelivery
            && (bool) $this->user()?->can('manage', $delivery);
    }

    public function rules(): array
    {
        $delivery = $this->route('communicationDelivery');
        $requiresRiskAcknowledgement = $delivery instanceof CommunicationDelivery
            && $delivery->status === CommunicationDeliveryStatus::Unknown;

        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:20', 'max:1000'],
            'confirm_processing' => ['required', 'accepted'],
            'duplicate_risk_acknowledged' => $requiresRiskAcknowledgement
                ? ['required', 'accepted']
                : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'La razón administrativa es obligatoria.',
            'reason.min' => 'La razón debe tener al menos 20 caracteres.',
            'confirm_processing.required' => 'Debes confirmar la acción antes de continuar.',
            'confirm_processing.accepted' => 'Confirma la acción antes de continuar.',
            'duplicate_risk_acknowledged.required' => 'Debes reconocer expresamente el posible envío duplicado.',
            'duplicate_risk_acknowledged.accepted' => 'Debes reconocer expresamente el posible envío duplicado.',
        ];
    }
}
