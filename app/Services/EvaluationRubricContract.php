<?php

namespace App\Services;

use App\Models\RubricVersion;
use Illuminate\Validation\ValidationException;
use LogicException;

final class EvaluationRubricContract
{
    public const INITIAL_VERSION = 1;

    public const INITIAL_TITLE = 'Rúbrica de evaluación Flower Flow 2026';

    /** @return array<string, int|string> */
    public function versionAttributes(): array
    {
        return [
            'criterion_score_min' => '0.0000',
            'criterion_score_max' => '10.0000',
            'criterion_score_step' => '0.5000',
            'total_weight' => '100.0000',
            'total_score_min' => '0.0000',
            'total_score_max' => '100.0000',
            'internal_decimal_places' => 4,
            'display_decimal_places' => 2,
            'rounding_mode' => 'HALF_UP',
            'general_comment_min_characters' => 100,
            'general_comment_max_characters' => 2000,
            'criterion_comment_max_characters' => 1000,
        ];
    }

    /** @return list<array<string, int|string|null>> */
    public function criteria(): array
    {
        return [
            $this->criterion('pertinence', 'Pertinencia', '20.0000', 1),
            $this->criterion('clarity', 'Claridad', '20.0000', 2),
            $this->criterion('feasibility', 'Viabilidad', '25.0000', 3),
            $this->criterion('impact', 'Impacto', '25.0000', 4),
            $this->criterion('coherence', 'Coherencia', '10.0000', 5),
        ];
    }

    /** @return array<string, string> */
    public function payloadErrors(array $versionAttributes, array $criteria): array
    {
        $errors = [];
        $unexpectedVersionFields = array_diff(array_keys($versionAttributes), array_keys($this->versionAttributes()));
        if ($unexpectedVersionFields !== []) {
            $errors['contract'] = 'El contrato contiene campos de versión no autorizados.';
        }

        foreach ($this->versionAttributes() as $field => $expected) {
            $actual = $versionAttributes[$field] ?? null;
            $valid = is_int($expected)
                ? filter_var($actual, FILTER_VALIDATE_INT) !== false && (int) $actual === $expected
                : ($field === 'rounding_mode'
                    ? $actual === $expected
                    : $this->decimalEquals($actual, $expected));

            if (! $valid) {
                $errors[$field] = "El valor de {$field} no coincide con el contrato aprobado.";
            }
        }

        $criteria = array_values($criteria);
        $expectedCriteria = $this->criteria();
        if (count($criteria) !== count($expectedCriteria)) {
            $errors['criteria'] = 'La rúbrica debe contener exactamente cinco criterios.';
        }

        foreach ($expectedCriteria as $index => $expected) {
            $actual = $criteria[$index] ?? [];
            $unexpectedCriterionFields = array_diff(array_keys($actual), array_keys($expected));
            if ($unexpectedCriterionFields !== []) {
                $errors["criteria.{$index}"] = 'El criterio contiene campos no autorizados.';
            }
            foreach (['code', 'label', 'sort_order'] as $field) {
                if (($actual[$field] ?? null) != $expected[$field]) {
                    $errors["criteria.{$index}.{$field}"] = 'El código, etiqueta y orden deben coincidir exactamente con el contrato aprobado.';
                }
            }

            if (array_key_exists('description', $actual) && $actual['description'] !== null && $actual['description'] !== '') {
                $errors["criteria.{$index}.description"] = 'La descripción permanece POR_CONFIRMAR y debe ser nula.';
            }

            foreach (['weight', 'min_score', 'max_score', 'score_step'] as $field) {
                if (! $this->decimalEquals($actual[$field] ?? null, (string) $expected[$field])) {
                    $errors["criteria.{$index}.{$field}"] = 'El valor numérico no coincide con el contrato aprobado.';
                }
            }
        }

        return $errors;
    }

    /** @throws ValidationException */
    public function assertPayload(array $versionAttributes, array $criteria): void
    {
        $errors = $this->payloadErrors($versionAttributes, $criteria);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @throws LogicException */
    public function assertPersisted(RubricVersion $rubric): void
    {
        $rubric->loadMissing('criteria');
        $versionAttributes = collect($this->versionAttributes())
            ->mapWithKeys(fn ($value, string $field) => [$field => $rubric->getAttribute($field)])
            ->all();
        $criteria = $rubric->criteria->map(fn ($criterion) => [
            'code' => $criterion->code,
            'label' => $criterion->label,
            'description' => $criterion->description,
            'weight' => $criterion->weight,
            'min_score' => $criterion->min_score,
            'max_score' => $criterion->max_score,
            'score_step' => $criterion->score_step,
            'sort_order' => $criterion->sort_order,
        ])->all();
        $errors = $this->payloadErrors($versionAttributes, $criteria);

        if ($errors !== []) {
            throw new LogicException('The persisted rubric diverges from the approved contract: '.implode(' ', $errors));
        }
    }

    /** @return array<string, int|string|null> */
    private function criterion(string $code, string $label, string $weight, int $order): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'description' => null,
            'weight' => $weight,
            'min_score' => '0.0000',
            'max_score' => '10.0000',
            'score_step' => '0.5000',
            'sort_order' => $order,
        ];
    }

    private function decimalEquals(mixed $actual, string $expected): bool
    {
        if ((! is_int($actual) && ! is_float($actual) && ! is_string($actual)) || ! is_numeric($actual)) {
            return false;
        }

        $actual = (string) $actual;
        if (preg_match('/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/D', $actual) !== 1) {
            return false;
        }

        return bccomp($actual, $expected, 10) === 0;
    }
}
