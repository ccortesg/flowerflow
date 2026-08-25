<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use App\Models\RubricCriterion;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class AdminSaveReopenedEvaluationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $criteria = $this->input('criteria');
        if (is_array($criteria)) {
            $criteria = array_map(function ($criterion) {
                if (is_array($criterion)) {
                    $criterion['score'] = ($criterion['score'] ?? null) === '' ? null : ($criterion['score'] ?? null);
                    $criterion['comment'] = ($criterion['comment'] ?? null) === '' ? null : ($criterion['comment'] ?? null);
                }

                return $criterion;
            }, $criteria);
        }
        $this->merge([
            'general_comment' => $this->input('general_comment') === '' ? null : $this->input('general_comment'),
            'criteria' => $criteria,
        ]);
    }

    public function authorize(): bool
    {
        $evaluation = $this->route('evaluation');

        return $evaluation instanceof Evaluation && (bool) $this->user()?->can('manageReopened', $evaluation);
    }

    public function rules(): array
    {
        $evaluation = $this->route('evaluation');
        $count = $evaluation instanceof Evaluation
            ? RubricCriterion::query()->where('rubric_version_id', $evaluation->rubric_version_id)->count()
            : 0;

        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'general_comment' => ['present', 'nullable', 'string', 'max:2000'],
            'criteria' => ['present', 'array', 'max:'.$count],
            'criteria.*' => ['required', 'array:code,score,comment'],
            'criteria.*.code' => ['required', 'string'],
            'criteria.*.score' => ['present', 'nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    app(EvaluationDraftCalculator::class)->normalizeScore($value);
                } catch (InvalidArgumentException $exception) {
                    $fail($exception->getMessage());
                }
            }],
            'criteria.*.comment' => ['present', 'nullable', 'string', 'max:1000'],
            'current_password' => ['required', 'string', 'current_password'],
            'intent' => ['sometimes', 'string', 'in:save,review'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token', '_method', 'lock_version', 'general_comment', 'criteria', 'current_password', 'intent']) !== []) {
                $validator->errors()->add('evaluation', 'El borrador contiene campos no autorizados.');
            }
            $criteria = collect(is_array($this->input('criteria')) ? $this->input('criteria') : []);
            $codes = $criteria->filter(fn ($item) => is_array($item) && isset($item['code']))->pluck('code');
            if ($codes->duplicates()->isNotEmpty()) {
                $validator->errors()->add('criteria', 'Cada código de criterio puede aparecer una sola vez.');
            }
            $evaluation = $this->route('evaluation');
            if ($evaluation instanceof Evaluation) {
                $allowed = RubricCriterion::query()->where('rubric_version_id', $evaluation->rubric_version_id)->pluck('code');
                if ($codes->diff($allowed)->isNotEmpty()) {
                    $validator->errors()->add('criteria', 'La solicitud contiene un criterio desconocido.');
                }
            }
        }];
    }
}
