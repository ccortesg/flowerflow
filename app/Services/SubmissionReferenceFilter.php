<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

final class SubmissionReferenceFilter
{
    public function apply(Builder $query, string $value): Builder
    {
        $escaped = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            trim($value),
        );

        if ($escaped === '') {
            return $query;
        }

        $pattern = '%'.$escaped.'%';

        return $query->where(function (Builder $reference) use ($pattern): void {
            $reference
                ->where('submissions.folio', 'like', $pattern)
                ->orWhere('submissions.public_id', 'like', $pattern);
        });
    }
}
