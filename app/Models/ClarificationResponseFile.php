<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClarificationResponseFile extends Model
{
    use HasPublicId;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(ClarificationResponse::class, 'clarification_response_id');
    }
}
