<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SubmissionFileStore
{
    public function store(Submission $submission, UploadedFile $file, string $kind = 'document'): SubmissionFile
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::ulid().'.'.$extension;
        $path = "submissions/{$submission->public_id}/{$storedName}";

        $storedPath = Storage::disk('local')->putFileAs("submissions/{$submission->public_id}", $file, $storedName);
        if ($storedPath === false) {
            throw new RuntimeException('No fue posible guardar el archivo privado de la propuesta.');
        }

        try {
            return $submission->files()->create([
                'actor_user_id' => auth()->id(),
                'kind' => $kind,
                'format_category' => $this->formatCategory($extension, $kind),
                'disk' => 'local',
                'path' => $path,
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'stored_name' => $storedName,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    private function formatCategory(string $extension, string $kind): string
    {
        if ($kind === 'editor_image') {
            return 'image';
        }

        return match ($extension) {
            'pdf' => 'pdf',
            'doc', 'docx', 'odt' => 'document',
            'ppt', 'pptx', 'odp' => 'presentation',
            'xls', 'xlsx', 'ods' => 'spreadsheet',
            default => 'other',
        };
    }
}
