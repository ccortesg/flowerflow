<?php

namespace App\Services;

use App\Models\ClarificationResponse;
use App\Models\ClarificationResponseFile;
use App\Models\ResidencyDocument;
use App\Models\ResidencyDocumentRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class PrivateEvidenceFileStore
{
    public function storeResidency(
        ResidencyDocumentRequest $request,
        User $actor,
        UploadedFile $file,
        string $documentType,
        ?string $equivalentDescription
    ): ResidencyDocument {
        return $this->store(
            config('flowerflow.admissibility.residency_disk'),
            "requests/{$request->public_id}",
            $file,
            fn (array $attributes) => $request->documents()->create([
                ...$attributes,
                'uploader_user_id' => $actor->id,
                'document_type' => $documentType,
                'equivalent_description' => $equivalentDescription,
            ])
        );
    }

    public function storeClarification(
        ClarificationResponse $response,
        User $actor,
        UploadedFile $file
    ): ClarificationResponseFile {
        return $this->store(
            config('flowerflow.admissibility.clarification_disk'),
            "responses/{$response->public_id}",
            $file,
            fn (array $attributes) => $response->files()->create([
                ...$attributes,
                'uploader_user_id' => $actor->id,
            ])
        );
    }

    private function store(string $disk, string $directory, UploadedFile $file, callable $persist)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::ulid().'.'.$extension;
        $path = "$directory/$storedName";
        Storage::disk($disk)->putFileAs($directory, $file, $storedName);

        try {
            return $persist([
                'disk' => $disk,
                'path' => $path,
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'stored_name' => $storedName,
                'mime_type' => (string) $file->getMimeType(),
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
                'created_at' => now('UTC'),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }
}
