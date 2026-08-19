<?php

namespace App\Services;

use App\Exceptions\BlindReviewPackageRejected;
use App\Models\BlindReviewPackageFile;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\Storage;

final class BlindReviewFileIntegrityVerifier
{
    public function __construct(private UploadedFileInspector $inspector) {}

    public function verifyPackageFile(BlindReviewPackageFile $packageFile): string
    {
        $packageFile->loadMissing('submissionFile');

        return $this->verify($packageFile->submissionFile, [
            'mime_type' => $packageFile->expected_mime,
            'extension' => $packageFile->expected_extension,
            'size_bytes' => $packageFile->expected_size_bytes,
            'sha256' => $packageFile->expected_sha256,
            'kind' => $packageFile->file_class->value,
        ]);
    }

    /** @param array{mime_type:string,extension:string,size_bytes:int,sha256:string,kind:string} $expected */
    public function verify(SubmissionFile $file, array $expected): string
    {
        if ($file->kind !== $expected['kind']
            || $file->mime_type !== $expected['mime_type']
            || strtolower($file->extension) !== strtolower($expected['extension'])
            || (int) $file->size_bytes !== (int) $expected['size_bytes']
            || ! hash_equals(strtolower($file->sha256), strtolower($expected['sha256']))) {
            throw new BlindReviewPackageRejected('file_metadata_drift', 'El anexo ya no coincide con la versión capturada.');
        }

        $disk = Storage::disk($file->disk);
        if (! $disk->exists($file->path)) {
            throw new BlindReviewPackageRejected('file_missing', 'El anexo capturado no está disponible.');
        }

        $path = $disk->path($file->path);
        if (! is_readable($path)
            || filesize($path) !== (int) $expected['size_bytes']
            || ! hash_equals(strtolower((string) hash_file('sha256', $path)), strtolower($expected['sha256']))) {
            throw new BlindReviewPackageRejected('file_binary_drift', 'La integridad del anexo no pudo comprobarse.');
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        if (! hash_equals(strtolower($expected['mime_type']), strtolower($detectedMime))) {
            throw new BlindReviewPackageRejected('file_mime_drift', 'El tipo real del anexo no coincide con la versión capturada.');
        }

        if ($this->inspector->inspectPath($path, $expected['extension'], $expected['kind']) !== null) {
            throw new BlindReviewPackageRejected('file_signature_drift', 'La firma del anexo no es válida.');
        }

        return $path;
    }
}
