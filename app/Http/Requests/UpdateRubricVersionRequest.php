<?php

namespace App\Http\Requests;

use App\Models\RubricVersion;

class UpdateRubricVersionRequest extends StoreRubricVersionRequest
{
    public function authorize(): bool
    {
        $rubric = $this->route('rubricVersion');

        return $rubric instanceof RubricVersion && (bool) $this->user()?->can('update', $rubric);
    }

    public function rules(): array
    {
        return [...parent::rules(), 'version' => ['prohibited']];
    }
}
