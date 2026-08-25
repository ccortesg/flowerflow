<?php

namespace App\Services;

use App\Exceptions\BulkJudgeAssignmentRejected;
use App\Models\JudgeProfile;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use JsonException;

final class BulkJudgeAssignmentIntent
{
    public function __construct(private BulkJudgeAssignmentEligibility $eligibility) {}

    /**
     * @param  array<string, mixed>  $input
     * @param  iterable<int, Submission>  $submissions
     */
    public function issue(User $actor, JudgeProfile $judge, iterable $submissions, array $input): string
    {
        $states = [];
        $submissionIds = [];
        foreach ($submissions as $submission) {
            $submissionIds[] = $submission->public_id;
            $states[$submission->public_id] = $this->eligibility->snapshot($submission, $judge);
        }

        $payload = [
            'schema' => 1,
            'operation_id' => (string) Str::ulid(),
            'actor_id' => $actor->id,
            'judge_profile' => $judge->public_id,
            'submissions' => $submissionIds,
            'states' => $states,
            'participant_reason' => $input['participant_reason'],
            'internal_notes' => $input['internal_notes'],
            'package_reason' => $input['package_reason'],
            'assignment_reason' => $input['assignment_reason'],
            'notify_judge' => (bool) $input['notify_judge'],
            'expires_at' => now('UTC')->addMinutes((int) config('flowerflow.bulk_judge_assignment.intent_ttl_minutes'))->getTimestamp(),
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, mixed> */
    public function decode(string $intent, User $actor): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($intent), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            throw new BulkJudgeAssignmentRejected('intent_invalid', 'La confirmación está alterada o ya no es válida.');
        }

        if (! is_array($payload)
            || ($payload['schema'] ?? null) !== 1
            || ($payload['actor_id'] ?? null) !== $actor->id
            || ! is_string($payload['operation_id'] ?? null)
            || ! Str::isUlid($payload['operation_id'])
            || ! is_string($payload['judge_profile'] ?? null)
            || ! is_array($payload['submissions'] ?? null)
            || ! is_array($payload['states'] ?? null)
            || ! is_int($payload['expires_at'] ?? null)) {
            throw new BulkJudgeAssignmentRejected('intent_context_invalid', 'La confirmación no pertenece a esta cuenta u operación.');
        }
        if ($payload['expires_at'] < now('UTC')->getTimestamp()) {
            throw new BulkJudgeAssignmentRejected('intent_expired', 'La revisión expiró. Vuelve a preparar la operación.');
        }

        return $payload;
    }
}
