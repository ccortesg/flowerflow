<?php

namespace App\Actions\Judges;

use App\Actions\AssignExclusiveBusinessRole;
use App\Enums\BusinessRole;
use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\JudgeProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateJudgeAccount
{
    public function __construct(
        private EnsureJudgeAdministrationActor $ensureActor,
        private AssignExclusiveBusinessRole $assignRole,
        private AuditLogger $audit,
        private SendJudgeSetupNotification $sendSetupNotification,
    ) {}

    public function execute(
        User $actor,
        string $name,
        string $email,
        JudgeAssignmentRole $assignmentRole,
        bool $sendSetupNotification = true,
    ): JudgeProfile {
        $this->ensureActor->execute($actor, 'manage judges');
        if ($sendSetupNotification && ! config('flowerflow.judge_notifications.account_setup_enabled')) {
            throw ValidationException::withMessages([
                'send_setup_notification' => 'La notificación de configuración está deshabilitada globalmente.',
            ]);
        }
        $normalizedEmail = Str::lower(trim($email));

        try {
            $profile = DB::transaction(function () use ($actor, $name, $normalizedEmail, $assignmentRole): JudgeProfile {
                if (User::query()->where('email', $normalizedEmail)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ya existe una cuenta con este correo. No se modificó ningún rol ni perfil.',
                    ]);
                }

                $user = User::query()->create([
                    'name' => trim($name),
                    'email' => $normalizedEmail,
                    'password' => Hash::make(Str::random(64)),
                ]);
                $this->assignRole->execute($user, BusinessRole::Judge);

                $profile = new JudgeProfile;
                $profile->forceFill([
                    'user_id' => $user->id,
                    'assignment_role' => $assignmentRole,
                    'status' => JudgeProfileStatus::PendingSetup,
                    'max_active_assignments' => null,
                    'created_by_user_id' => $actor->id,
                ]);
                $profile->save();

                $this->audit->record('judge.account_created', $profile, $actor, [
                    'status' => JudgeProfileStatus::PendingSetup->value,
                    'assignment_role' => $assignmentRole->value,
                    'max_active_assignments' => null,
                ]);

                return $profile->load('user');
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'email' => 'No fue posible crear la cuenta porque el correo o el perfil ya existe. No se realizaron cambios parciales.',
            ]);
        }

        if ($sendSetupNotification) {
            $this->sendSetupNotification->execute($profile, $actor);
        }

        return $profile;
    }
}
