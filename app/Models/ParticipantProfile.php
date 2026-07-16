<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'whatsapp_opt_in' => 'boolean',
            'adult_declared_at' => 'datetime',
            'hermosillo_resident_declared_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        return filled($this->first_names)
            && filled($this->last_names)
            && preg_match('/^\+[1-9]\d{7,14}$/', $this->mobile_e164) === 1
            && $this->birth_date?->isBefore(now()->subYears(18)->addDay())
            && filled($this->neighborhood)
            && $this->adult_declared_at
            && $this->hermosillo_resident_declared_at;
    }
}
