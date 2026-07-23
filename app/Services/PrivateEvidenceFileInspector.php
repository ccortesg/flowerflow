<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

final class PrivateEvidenceFileInspector
{
    public function inspect(UploadedFile $file): ?string
    {
        $name = $file->getClientOriginalName();
        if ($name !== basename(str_replace('\\', '/', $name))
            || str_contains($name, '..')
            || preg_match('/[\x00-\x1F\x7F]/u', $name)
            || str_starts_with($name, '.')) {
            return 'El nombre del archivo contiene caracteres o rutas no permitidas.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, config('flowerflow.admissibility.file_extensions'), true)) {
            return 'El archivo debe ser PDF, JPEG, PNG o WebP.';
        }

        $path = $file->getRealPath();
        $mime = (string) $file->getMimeType();

        if ($extension === 'pdf') {
            $contents = (string) file_get_contents($path);
            if (! str_starts_with($contents, '%PDF-') || $mime !== 'application/pdf') {
                return 'El archivo no tiene una firma PDF válida.';
            }
            if (preg_match('/\/Encrypt\b/', $contents)) {
                return 'No se aceptan archivos PDF cifrados o protegidos con contraseña.';
            }
            if (preg_match('/\/(JavaScript|JS|Launch|EmbeddedFile|OpenAction)\b/i', $contents)) {
                return 'El PDF contiene elementos activos o adjuntos no permitidos.';
            }

            return null;
        }

        $image = @getimagesize($path);
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];

        if (! $image || ($image['mime'] ?? null) !== $allowedMimes[$extension] || $mime !== $allowedMimes[$extension]) {
            return 'La imagen no tiene una firma JPEG, PNG o WebP válida para su extensión.';
        }

        return null;
    }
}
