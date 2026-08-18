<?php

namespace App\Actions\Fortify;

use App\Actions\Judges\InitializeJudgePassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private InitializeJudgePassword $initializeJudgePassword) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $sendJudgeVerification = DB::transaction(function () use ($user, $input): bool {
            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();

            if ($user->hasExactRoles(['judge'])) {
                $initialized = $this->initializeJudgePassword->execute($user);

                return $initialized && ! $user->hasVerifiedEmail();
            }

            return false;
        });

        if ($sendJudgeVerification) {
            $user->sendEmailVerificationNotification();
        }
    }
}
