<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasPublicId;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['eligibility_declared_at' => 'datetime'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }
}
