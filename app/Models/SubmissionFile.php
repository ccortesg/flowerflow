<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionFile extends Model
{
    use HasPublicId;

    protected $guarded = [];

    public function blindReviewPackageFiles(): HasMany
    {
        return $this->hasMany(BlindReviewPackageFile::class);
    }
}
