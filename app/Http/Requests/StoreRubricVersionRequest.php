<?php

namespace App\Http\Requests;

use App\Models\RubricVersion;
use App\Services\EvaluationRubricContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRubricVersionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title'))]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', RubricVersion::class);
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'criterion_score_min' => ['required', 'numeric'],
            'criterion_score_max' => ['required', 'numeric'],
            'criterion_score_step' => ['required', 'numeric'],
            'total_weight' => ['required', 'numeric'],
            'total_score_min' => ['required', 'numeric'],
            'total_score_max' => ['required', 'numeric'],
            'internal_decimal_places' => ['required', 'integer'],
            'display_decimal_places' => ['required', 'integer'],
            'rounding_mode' => ['required', 'string', 'max:24'],
            'general_comment_min_characters' => ['required', 'integer'],
            'general_comment_max_characters' => ['required', 'integer'],
            'criterion_comment_max_characters' => ['required', 'integer'],
            'criteria' => ['required', 'array', 'size:5'],
            'criteria.*' => ['required', 'array:code,label,weight,min_score,max_score,score_step,sort_order'],
            'criteria.*.code' => ['required', 'string', 'max:32'],
            'criteria.*.label' => ['required', 'string', 'max:100'],
            'criteria.*.description' => ['prohibited'],
            'criteria.*.weight' => ['required', 'numeric'],
            'criteria.*.min_score' => ['required', 'numeric'],
            'criteria.*.max_score' => ['required', 'numeric'],
            'criteria.*.score_step' => ['required', 'numeric'],
            'criteria.*.sort_order' => ['required', 'integer'],
            'status' => ['prohibited'],
            'active_slot' => ['prohibited'],
            'competition_id' => ['prohibited'],
            'created_by_user_id' => ['prohibited'],
            'last_edited_by_user_id' => ['prohibited'],
            'activated_at' => ['prohibited'],
            'activated_by_user_id' => ['prohibited'],
            'activation_reason' => ['prohibited'],
            'superseded_at' => ['prohibited'],
            'superseded_by_user_id' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $errors = app(EvaluationRubricContract::class)->payloadErrors(
                $this->versionAttributes(),
                (array) $this->input('criteria', []),
            );
            foreach ($errors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        }];
    }

    /** @return array<string, mixed> */
    public function versionAttributes(): array
    {
        return collect(app(EvaluationRubricContract::class)->versionAttributes())
            ->mapWithKeys(fn ($value, string $field) => [$field => $this->input($field)])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function criteriaAttributes(): array
    {
        return array_values((array) $this->input('criteria', []));
    }

    public function messages(): array
    {
        return [
            'version.required' => 'Escribe el número de versión.',
            'version.min' => 'La versión debe ser un entero positivo.',
            'title.required' => 'Escribe el título interno de la rúbrica.',
            'criteria.size' => 'La rúbrica debe contener exactamente cinco criterios.',
            'criteria.*.description.prohibited' => 'La descripción permanece POR_CONFIRMAR y no puede capturarse todavía.',
            '*.prohibited' => 'Este campo de ciclo o auditoría no puede enviarse desde el formulario.',
        ];
    }
}
