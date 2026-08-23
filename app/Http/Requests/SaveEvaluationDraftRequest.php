<?php

namespace App\Http\Requests;

use App\Models\JudgeAssignment;
use App\Models\RubricCriterion;
use App\Services\EvaluationDraftCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class SaveEvaluationDraftRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $criteria = $this->input('criteria');
        if (is_array($criteria)) {
            $criteria = array_map(function ($criterion) {
                if (! is_array($criterion)) {
                    return $criterion;
                }
                if (array_key_exists('score', $criterion) && $criterion['score'] === '') {
                    $criterion['score'] = null;
                }
                if (array_key_exists('comment', $criterion) && $criterion['comment'] === '') {
                    $criterion['comment'] = null;
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
        $assignment = $this->route('judgeAssignment');

        return $assignment instanceof JudgeAssignment
            && (bool) $this->user()?->can('updateEvaluationDraft', $assignment);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'general_comment' => ['present', 'nullable', 'string', 'max:2000'],
            'criteria' => ['present', 'array', 'max:5'],
            'criteria.*' => ['required', 'array:code,score,comment'],
            'criteria.*.code' => ['required', 'string'],
            'criteria.*.score' => [
                'present',
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        app(EvaluationDraftCalculator::class)->normalizeScore($value);
                    } catch (InvalidArgumentException $exception) {
                        $fail($exception->getMessage());
                    }
                },
            ],
            'criteria.*.comment' => ['present', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (array_diff(array_keys($this->all()), ['_token', '_method', 'lock_version', 'general_comment', 'criteria']) !== []) {
                $validator->errors()->add('evaluation', 'El borrador contiene campos no autorizados.');
            }

            $criteria = $this->input('criteria');
            if (! is_array($criteria)) {
                return;
            }

            $codes = collect($criteria)->filter(fn ($criterion): bool => is_array($criterion) && isset($criterion['code']))
                ->pluck('code');
            if ($codes->duplicates()->isNotEmpty()) {
                $validator->errors()->add('criteria', 'Cada código de criterio puede aparecer una sola vez.');
            }

            $assignment = $this->route('judgeAssignment');
            if ($assignment instanceof JudgeAssignment) {
                $allowed = RubricCriterion::query()
                    ->where('rubric_version_id', $assignment->rubric_version_id)
                    ->pluck('code');
                if ($codes->diff($allowed)->isNotEmpty()) {
                    $validator->errors()->add('criteria', 'La solicitud contiene un criterio desconocido o ajeno a la rúbrica fijada.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'lock_version.required' => 'La versión de bloqueo es obligatoria. Recarga la asignación.',
            'general_comment.max' => 'El comentario general admite hasta 2,000 caracteres.',
            'criteria.max' => 'La rúbrica contiene exactamente cinco criterios.',
            'criteria.*.comment.max' => 'Cada comentario de criterio admite hasta 1,000 caracteres.',
        ];
    }
}
