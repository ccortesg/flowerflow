<?php

namespace App\Actions\Judges;

use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\JudgeSetupLink;
use App\Models\User;
use App\Notifications\JudgeAccountSetupNotification;
use App\Services\AuditLogger;
use App\Services\CommunicationMessageRegistry;
use App\Services\ResilientMailDispatcher;
use App\Support\MailDispatchStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class SendJudgeSetupNotification
{
    public function __construct(
        private EnsureJudgeAdministrationActor $ensureActor,
        private AuditLogger $audit,
        private ResilientMailDispatcher $mail,
        private MailDispatchStatus $status,
    ) {}

    public function execute(JudgeProfile $profile, User $actor): bool
    {
        $this->ensureActor->execute($actor, 'manage judges');
        if (! config('flowerflow.judge_notifications.account_setup_enabled')) {
            throw ValidationException::withMessages(['judge' => 'El envío de configuración de cuentas de juez está deshabilitado globalmente.']);
        }

        try {
            [$link, $token] = DB::transaction(function () use ($profile, $actor): array {
                $lockedProfile = JudgeProfile::query()->whereKey($profile->id)->lockForUpdate()->firstOrFail();
                $judge = User::query()->with('roles')->whereKey($lockedProfile->user_id)->lockForUpdate()->firstOrFail();
                if ($lockedProfile->status !== JudgeProfileStatus::PendingSetup || ! $judge->hasExactRoles(['judge'])) {
                    throw ValidationException::withMessages(['judge' => 'El enlace sólo puede emitirse para un juez exacto con configuración pendiente.']);
                }

                $now = now('UTC');
                DB::table('judge_setup_links')
                    ->where('judge_profile_id', $lockedProfile->id)
                    ->where('active_slot', 1)
                    ->update([
                        'active_slot' => null,
                        'invalidated_at' => $now,
                        'updated_at' => $now,
                    ]);

                $token = Str::random(64);
                $link = new JudgeSetupLink;
                $link->forceFill([
                    'judge_profile_id' => $lockedProfile->id,
                    'token_hash' => hash('sha256', $token),
                    'email_fingerprint' => CommunicationMessageRegistry::recipientFingerprint((string) $judge->email),
                    'active_slot' => 1,
                    'issued_at' => $now,
                    'expires_at' => $now->copy()->addMinutes((int) config('flowerflow.judge_notifications.setup_link_ttl_minutes')),
                    'issued_by_user_id' => $actor->id,
                ])->save();

                $this->audit->record('judge.setup_link.issued', $link, $actor, [
                    'judge_profile_id' => $lockedProfile->id,
                    'setup_link_id' => $link->id,
                    'notification_requested' => true,
                ]);

                return [$link, $token];
            }, 3);

            $sent = $this->mail->notify(
                $profile->user,
                new JudgeAccountSetupNotification($link->id, $token),
                'La cuenta existe, pero no pudimos programar el correo de configuración. Puedes reintentarlo desde el detalle del juez.',
                'judge-setup-link:'.$link->public_id,
            );
            if (! $sent) {
                $this->audit->record('judge.setup_link.rejected', $link, $actor, [
                    'judge_profile_id' => $profile->id,
                    'setup_link_id' => $link->id,
                    'reason_code' => 'mail_enqueue_failed',
                ]);
            }

            return $sent;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->status->markFailed('La cuenta existe, pero no pudimos generar el correo de configuración. Puedes reintentarlo desde el detalle del juez.');
            $this->audit->record('judge.setup_link.rejected', $profile, $actor, [
                'judge_profile_id' => $profile->id,
                'reason_code' => 'setup_link_generation_failed',
            ]);

            return false;
        }
    }
}
