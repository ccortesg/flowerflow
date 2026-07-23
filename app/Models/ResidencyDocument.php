<?php

namespace App\Models;

use App\Enums\ResidencyDocumentType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidencyDocument extends Model
{
    use HasPublicId;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['document_type' => ResidencyDocumentType::class, 'created_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ResidencyDocumentRequest::class, 'residency_document_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_user_id');
    }
}
