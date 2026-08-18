<?php

namespace App\Actions\Judges;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class RevokeUserSessions
{
    public function execute(User $user): int
    {
        if (config('session.driver') !== 'database') {
            throw new RuntimeException('Targeted session revocation requires the database session driver.');
        }

        $table = (string) config('session.table', 'sessions');
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new RuntimeException('The configured session table is invalid.');
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return DB::connection(config('session.connection'))
            ->table($table)
            ->where('user_id', $user->getKey())
            ->delete();
    }
}
