<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Models\Concerns\ImmutableRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClarificationResponse extends Model
{
    use HasPublicId, ImmutableRecord;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function clarification(): BelongsTo
    {
        return $this->belongsTo(ClarificationRequest::class, 'clarification_request_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClarificationResponseFile::class);
    }
}
