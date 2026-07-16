<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_representative' => 'boolean'];
    }
}
