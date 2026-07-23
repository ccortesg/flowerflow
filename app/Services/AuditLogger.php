<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AuditLogger
{
    public function record(string $action, Model $subject, ?User $actor = null, array $metadata = []): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();
        $key = (string) config('app.key');

        return AuditLog::query()->create([
            'public_id' => (string) Str::ulid(),
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'ip_hash' => $request?->ip() ? hash_hmac('sha256', (string) $request->ip(), $key) : null,
            'user_agent_hash' => $request?->userAgent() ? hash_hmac('sha256', (string) $request->userAgent(), $key) : null,
            'metadata' => $metadata ?: null,
            'occurred_at' => now('UTC'),
        ]);
    }
}
