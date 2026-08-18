<?php

namespace App\Models;

use App\Enums\BlindReviewPackageStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class BlindReviewPackage extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(function (self $package): void {
            if ($package->getOriginal('status') !== BlindReviewPackageStatus::Draft->value) {
                throw new LogicException('Active or invalidated blind review packages are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Blind review packages cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'status' => BlindReviewPackageStatus::class,
            'schema_version' => 'integer',
            'payload' => 'array',
            'generated_at' => 'immutable_datetime',
            'activated_at' => 'immutable_datetime',
            'invalidated_at' => 'immutable_datetime',
        ];
    }

    public function submissionVersion(): BelongsTo
    {
        return $this->belongsTo(SubmissionVersion::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(BlindReviewPackageFile::class)->orderBy('display_order');
    }
}
