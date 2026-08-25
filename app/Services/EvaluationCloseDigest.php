<?php

namespace App\Services;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\JudgeAssignmentType;
use App\Enums\JudgeProfileStatus;
use App\Exceptions\CommunicationCancelledException;
use App\Exceptions\EvaluationCloseDigestRejected;
use App\Exceptions\EvaluationDraftRejected;
use App\Models\Competition;
use App\Models\JudgeAssignment;
use App\Models\JudgeProfile;
use App\Notifications\EvaluationCloseDigestNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EvaluationCloseDigest
{
    public const COMPETITION_SLUG = 'hermosillo-florece-2026';

    public const CATCHUP_HOURS = 24;

    public function __construct(
        private EvaluationWindow $window,
        private ResilientMailDispatcher $mail,
        private AuditLogger $audit,
    ) {}

    /** @return array{status:string,reason_code:string,judges_considered:int,deliveries_requested:int,deliveries_skipped:int} */
    public function queue(bool $execute): array
    {
        if (! config('flowerflow.flags.communication_ledger')) {
            return $this->result('skipped', 'communication_ledger_disabled');
        }
        if (! config('flowerflow.flags.evaluation_notifications')) {
            return $this->result('skipped', 'evaluation_notifications_disabled');
        }
        if (! config('flowerflow.evaluation_notifications.close_digest_enabled')) {
            return $this->result('skipped', 'evaluation_close_digest_disabled');
        }

        try {
            [$competition, $profiles] = $this->preflight(CarbonImmutable::instance(now('UTC')));
        } catch (EvaluationCloseDigestRejected $exception) {
            if ($exception->reasonCode === 'evaluation_close_digest_window_not_open') {
                return $this->result('skipped', $exception->reasonCode);
            }

            throw $exception;
        }
        if (! $execute) {
            return $this->result('dry_run', 'eligible', $profiles->count(), 0, 0);
        }

        $requested = 0;
        $skipped = 0;
        foreach ($profiles as $profile) {
            $recipient = $profile->user;
            if (! $this->eligibleJudge($profile)) {
                $skipped++;
                $this->audit->record('evaluation.close_digest_skipped', $profile, metadata: [
                    'competition_id' => $competition->id,
                    'judge_profile_id' => $profile->id,
                    'variant' => 'subject_judge',
                    'reason_code' => 'subject_judge_ineligible',
                ]);

                continue;
            }

            $counts = $this->counts($profile, $competition);
            $queued = $this->mail->recordAndDispatch(
                $recipient,
                new EvaluationCloseDigestNotification(
                    $profile->id,
                    $competition->id,
                    $counts['submitted'],
                    $counts['pending'],
                    $counts['conflicts_replacements'],
                    $counts['cancelled'],
                ),
                'El cierre quedó registrado, pero no fue posible programar su resumen.',
                implode(':', [
                    'evaluation-close-digest',
                    $competition->public_id,
                    $profile->public_id,
                    $this->window->evaluationCloseUtc()->format('YmdHis'),
                ]),
            );
            if ($queued) {
                $requested++;
            } else {
                $skipped++;
            }
            $this->audit->record(
                $queued ? 'evaluation.close_digest_requested' : 'evaluation.close_digest_skipped',
                $profile,
                metadata: [
                    'competition_id' => $competition->id,
                    'judge_profile_id' => $profile->id,
                    'variant' => 'subject_judge',
                    ...$counts,
                    'reason_code' => $queued ? 'queued' : 'mail_enqueue_failed',
                ],
            );
        }

        return $this->result('queued', 'completed', $profiles->count(), $requested, $skipped);
    }

    /** @return array{submitted:int,pending:int,conflicts_replacements:int,cancelled:int} */
    public function counts(JudgeProfile $profile, Competition $competition): array
    {
        $assignments = JudgeAssignment::query()
            ->where('competition_id', $competition->id)
            ->where('judge_profile_id', $profile->id)
            ->with(['evaluation.currentRevision', 'conflict'])
            ->get();

        return [
            'submitted' => $assignments->filter(
                fn (JudgeAssignment $assignment): bool => $assignment->evaluation?->status === EvaluationStatus::Submitted
            )->count(),
            'pending' => $assignments->filter(
                fn (JudgeAssignment $assignment): bool => $assignment->status === JudgeAssignmentStatus::Active
                    && ($assignment->evaluation === null
                        || in_array($assignment->evaluation->status, [EvaluationStatus::Draft, EvaluationStatus::Reopened], true))
            )->count(),
            'conflicts_replacements' => $assignments->filter(
                fn (JudgeAssignment $assignment): bool => $assignment->status === JudgeAssignmentStatus::ConflictDeclared
                    || $assignment->type === JudgeAssignmentType::Replacement
                    || $assignment->conflict !== null
            )->count(),
            'cancelled' => $assignments->where('status', JudgeAssignmentStatus::Cancelled)->count(),
        ];
    }

    /** @throws CommunicationCancelledException */
    /** @param array{submitted:int,pending:int,conflicts_replacements:int,cancelled:int} $expectedCounts */
    public function assertDeliveryContext(JudgeProfile $profile, Competition $competition, array $expectedCounts): void
    {
        if (! config('flowerflow.flags.communication_ledger')
            || ! config('flowerflow.flags.evaluation_notifications')
            || ! config('flowerflow.evaluation_notifications.close_digest_enabled')) {
            throw new CommunicationCancelledException('evaluation_close_digest_disabled');
        }

        try {
            [, $profiles] = $this->preflight(CarbonImmutable::instance(now('UTC')));
        } catch (EvaluationCloseDigestRejected $exception) {
            throw new CommunicationCancelledException($exception->reasonCode);
        }

        if (! $this->eligibleJudge($profile)
            || $competition->slug !== self::COMPETITION_SLUG
            || ! $profiles->contains(fn (JudgeProfile $candidate): bool => $candidate->id === $profile->id)) {
            throw new CommunicationCancelledException('evaluation_close_digest_recipient_invalid');
        }
        if ($this->counts($profile, $competition) !== $expectedCounts) {
            throw new CommunicationCancelledException('evaluation_close_digest_counts_changed');
        }
    }

    /** @return array{Competition,Collection<int,JudgeProfile>} */
    private function preflight(CarbonImmutable $now): array
    {
        if ((int) config('flowerflow.evaluation_notifications.close_digest_catchup_hours') !== self::CATCHUP_HOURS) {
            throw new EvaluationCloseDigestRejected('evaluation_close_digest_configuration_diverged');
        }

        try {
            $close = $this->window->evaluationCloseUtc();
        } catch (EvaluationDraftRejected) {
            throw new EvaluationCloseDigestRejected('evaluation_window_configuration_diverged');
        }

        $opensAt = $close->addSecond();
        $closesAt = $opensAt->addHours(self::CATCHUP_HOURS);
        if ($now->lessThan($opensAt)) {
            throw new EvaluationCloseDigestRejected('evaluation_close_digest_window_not_open');
        }
        if ($now->greaterThanOrEqualTo($closesAt)) {
            throw new EvaluationCloseDigestRejected('evaluation_close_digest_catchup_expired');
        }

        $competition = Competition::query()->where('slug', self::COMPETITION_SLUG)->first();
        if (! $competition) {
            throw new EvaluationCloseDigestRejected('evaluation_close_digest_competition_missing');
        }

        $expected = $close->format('Y-m-d H:i:s');
        if (JudgeAssignment::query()
            ->where('competition_id', $competition->id)
            ->where(function ($query) use ($expected): void {
                $query->whereNull('due_at')->orWhere('due_at', '<>', $expected);
            })
            ->exists()) {
            throw new EvaluationCloseDigestRejected('assignment_due_at_diverged');
        }

        $profiles = JudgeProfile::query()
            ->whereHas('assignments', fn ($query) => $query->where('competition_id', $competition->id))
            ->with('user.roles')
            ->orderBy('id')
            ->get();

        return [$competition, $profiles];
    }

    private function eligibleJudge(JudgeProfile $profile): bool
    {
        return $profile->status === JudgeProfileStatus::Active
            && $profile->user !== null
            && $profile->user->hasExactRoles(['judge'])
            && $profile->user->hasVerifiedEmail()
            && $profile->user->can('access judge workspace');
    }

    /** @return array{status:string,reason_code:string,judges_considered:int,deliveries_requested:int,deliveries_skipped:int} */
    private function result(
        string $status,
        string $reasonCode,
        int $judges = 0,
        int $requested = 0,
        int $skipped = 0,
    ): array {
        return [
            'status' => $status,
            'reason_code' => $reasonCode,
            'judges_considered' => $judges,
            'deliveries_requested' => $requested,
            'deliveries_skipped' => $skipped,
        ];
    }
}
