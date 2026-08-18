<?php

namespace App\Models;

use App\Enums\BlindReviewFileClass;
use App\Enums\BlindReviewPackageStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class BlindReviewPackageFile extends Model
{
    use HasPublicId;

    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(function (self $file): void {
            if ($file->getOriginal('status') !== BlindReviewPackageStatus::Draft->value) {
                throw new LogicException('Active or invalidated blind review package files are immutable.');
            }
        });
        static::deleting(function (self $file): void {
            if ($file->status !== BlindReviewPackageStatus::Draft) {
                throw new LogicException('Active or invalidated blind review package files cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'file_class' => BlindReviewFileClass::class,
            'status' => BlindReviewPackageStatus::class,
            'display_order' => 'integer',
            'expected_size_bytes' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(BlindReviewPackage::class, 'blind_review_package_id');
    }

    public function submissionFile(): BelongsTo
    {
        return $this->belongsTo(SubmissionFile::class);
    }
}
