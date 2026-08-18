<?php

namespace App\Models;

use App\Enums\JudgeAssignmentRole;
use App\Enums\JudgeProfileStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudgeProfile extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'assignment_role' => JudgeAssignmentRole::class,
            'status' => JudgeProfileStatus::class,
            'max_active_assignments' => 'integer',
            'password_initialized_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'reactivated_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by_user_id');
    }

    public function reactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reactivated_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === JudgeProfileStatus::Active;
    }
}
