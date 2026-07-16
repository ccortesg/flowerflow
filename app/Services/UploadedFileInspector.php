<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class UploadedFileInspector
{
    public function inspect(UploadedFile $file, string $kind): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($kind === 'editor_image') {
            $info = @getimagesize($path);
            if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
                return 'La imagen no tiene una firma JPEG, PNG o WebP válida.';
            }

            return null;
        }

        if ($extension === 'pdf' && ! str_starts_with((string) file_get_contents($path, false, null, 0, 5), '%PDF-')) {
            return 'El archivo PDF no tiene una firma válida.';
        }

        if (in_array($extension, ['doc', 'xls', 'ppt'], true)) {
            $signature = bin2hex((string) file_get_contents($path, false, null, 0, 8));
            if ($signature !== 'd0cf11e0a1b11ae1') {
                return 'El archivo Office binario no tiene una firma válida.';
            }
            $contents = (string) file_get_contents($path);
            if (preg_match('/VBA|_VBA_PROJECT|Macros/i', $contents)) {
                return 'No se permiten archivos Office que contengan macros.';
            }
        }

        if (in_array($extension, ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'], true)) {
            return $this->inspectArchive($path);
        }

        return null;
    }

    private function inspectArchive(string $path): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            return 'El documento no contiene una estructura ZIP válida.';
        }

        $compressed = 0;
        $uncompressed = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));
            if (str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name)) {
                $zip->close();

                return 'El documento contiene rutas internas inseguras.';
            }
            if (str_ends_with(strtolower($name), 'vbaproject.bin')) {
                $zip->close();

                return 'No se permiten documentos Office con macros.';
            }
            $compressed += (int) ($stat['comp_size'] ?? 0);
            $uncompressed += (int) ($stat['size'] ?? 0);
        }
        $zip->close();

        if ($uncompressed > 50 * 1024 * 1024 || ($compressed > 0 && $uncompressed / $compressed > 100)) {
            return 'El documento comprimido excede los límites seguros de expansión.';
        }

        return null;
    }
}
