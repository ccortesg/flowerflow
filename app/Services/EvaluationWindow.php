<?php

namespace App\Services;

use App\Exceptions\EvaluationDraftRejected;
use App\Models\JudgeAssignment;
use Carbon\CarbonImmutable;
use Throwable;

final class EvaluationWindow
{
    public const TIMEZONE = 'America/Hermosillo';

    public const REOPEN_CLOSE_LOCAL = '2026-08-27 20:00:00';

    public const REOPEN_CLOSE_UTC = '2026-08-28 03:00:00';

    public const EVALUATION_CLOSE_LOCAL = '2026-08-27 23:59:59';

    public const EVALUATION_CLOSE_UTC = '2026-08-28 06:59:59';

    public function assertAssignment(JudgeAssignment $assignment, bool $forMutation): void
    {
        $evaluationClose = $this->configuredClose(
            'flowerflow.evaluation_close_at',
            self::EVALUATION_CLOSE_LOCAL,
            self::EVALUATION_CLOSE_UTC,
        );

        if (! $assignment->due_at?->utc()->equalTo($evaluationClose)) {
            throw new EvaluationDraftRejected('assignment_due_at_diverged', 'El plazo de la asignación no coincide con el contrato aprobado.');
        }
        if ($forMutation && now('UTC')->greaterThan($evaluationClose)) {
            throw new EvaluationDraftRejected('assignment_due_at_expired', 'El plazo para modificar o enviar la evaluación ya terminó.');
        }
    }

    public function assertReopeningOpen(): void
    {
        $reopenClose = $this->configuredClose(
            'flowerflow.evaluation_reopen_close_at',
            self::REOPEN_CLOSE_LOCAL,
            self::REOPEN_CLOSE_UTC,
        );
        if (now('UTC')->greaterThan($reopenClose)) {
            throw new EvaluationDraftRejected('evaluation_reopen_window_expired', 'La ventana para reabrir evaluaciones ya terminó.');
        }
    }

    public function evaluationCloseUtc(): CarbonImmutable
    {
        return $this->configuredClose(
            'flowerflow.evaluation_close_at',
            self::EVALUATION_CLOSE_LOCAL,
            self::EVALUATION_CLOSE_UTC,
        );
    }

    private function configuredClose(string $key, string $expectedLocal, string $expectedUtc): CarbonImmutable
    {
        try {
            $expected = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $expectedUtc, 'UTC');
            $configured = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i:s',
                (string) config($key),
                (string) config('flowerflow.timezone'),
            )?->utc();
        } catch (Throwable) {
            $configured = null;
            $expected = null;
        }

        if (config('flowerflow.timezone') !== self::TIMEZONE
            || config($key) !== $expectedLocal
            || ! $configured
            || ! $expected
            || ! $configured->equalTo($expected)) {
            throw new EvaluationDraftRejected('evaluation_window_configuration_diverged', 'La ventana de evaluación no coincide con el contrato aprobado.');
        }

        return $expected;
    }
}
