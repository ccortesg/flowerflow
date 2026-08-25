<?php

namespace App\Actions\Judges;

use App\Enums\JudgeProfileStatus;
use App\Exceptions\JudgeSetupLinkRejected;
use App\Models\JudgeProfile;
use App\Models\JudgeSetupLink;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\JudgeSetupLinkValidator;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class ConsumeJudgeSetupLink
{
    public function __construct(
        private JudgeSetupLinkValidator $validator,
        private AuditLogger $audit,
    ) {}

    public function execute(JudgeSetupLink $setupLink, string $token, string $password): User
    {
        try {
            return DB::transaction(function () use ($setupLink, $token, $password): User {
                $link = JudgeSetupLink::query()->whereKey($setupLink->id)->lockForUpdate()->firstOrFail();
                $profile = JudgeProfile::query()->whereKey($link->judge_profile_id)->lockForUpdate()->firstOrFail();
                $judge = User::query()->with('roles')->whereKey($profile->user_id)->lockForUpdate()->firstOrFail();
                $link->setRelation('judgeProfile', $profile->setRelation('user', $judge));
                $this->validator->assertValid($link, $token);

                $now = now('UTC');
                $wasVerified = $judge->hasVerifiedEmail();
                $judge->forceFill([
                    'password' => Hash::make($password),
                    'email_verified_at' => $judge->email_verified_at ?? $now,
                    'remember_token' => null,
                ])->save();
                DB::table('judge_profiles')->where('id', $profile->id)->update([
                    'password_initialized_at' => $profile->password_initialized_at ?? $now,
                    'status' => JudgeProfileStatus::Active->value,
                    'activated_at' => $profile->activated_at ?? $now,
                    'updated_at' => $now,
                ]);
                DB::table('judge_setup_links')->where('id', $link->id)->update([
                    'active_slot' => null,
                    'consumed_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('judge_setup_links')
                    ->where('judge_profile_id', $profile->id)
                    ->where('active_slot', 1)
                    ->where('id', '<>', $link->id)
                    ->update([
                        'active_slot' => null,
                        'invalidated_at' => $now,
                        'updated_at' => $now,
                    ]);

                $link->refresh();
                $this->audit->record('judge.setup_link.consumed', $link, $judge, [
                    'judge_profile_id' => $profile->id,
                    'setup_link_id' => $link->id,
                    'email_was_already_verified' => $wasVerified,
                ]);
                if (! $wasVerified) {
                    DB::afterCommit(fn () => event(new Verified($judge)));
                }

                return $judge->refresh();
            }, 3);
        } catch (JudgeSetupLinkRejected $exception) {
            $this->audit->record('judge.setup_link.rejected', $setupLink, metadata: [
                'judge_profile_id' => $setupLink->judge_profile_id,
                'setup_link_id' => $setupLink->id,
                'reason_code' => $exception->reasonCode,
            ]);

            throw $exception;
        }
    }
}
