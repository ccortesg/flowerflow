<?php

namespace App\Services;

use App\Enums\BlindReviewFileClass;
use App\Exceptions\BlindReviewPackageRejected;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;

final class BlindReviewPackageBuilder
{
    public const SCHEMA_VERSION = 1;

    public function __construct(
        private SubmissionContentSanitizer $sanitizer,
        private CanonicalJson $canonicalJson,
        private BlindReviewFileIntegrityVerifier $integrity,
    ) {}

    /**
     * @return array{schema_version:int,payload:array<string,mixed>,payload_sha256:string,files:list<array<string,mixed>>}
     */
    public function build(SubmissionVersion $version): array
    {
        $snapshot = $version->snapshot;
        if (! is_array($snapshot) || ($snapshot['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            throw new BlindReviewPackageRejected('unsupported_snapshot_schema', 'La versión de la propuesta no usa un esquema compatible con M5.');
        }

        $category = $this->requiredMap($snapshot, 'category');
        $submission = $this->requiredMap($snapshot, 'submission');
        $externalLinks = $snapshot['external_links'] ?? null;
        $snapshotFiles = $snapshot['files'] ?? null;
        if (! is_array($externalLinks) || ! array_is_list($externalLinks)
            || ! is_array($snapshotFiles) || ! array_is_list($snapshotFiles)) {
            throw new BlindReviewPackageRejected('invalid_snapshot_shape', 'La versión final no contiene listas válidas de vínculos y anexos.');
        }

        $payload = [
            'category' => [
                'slug' => $this->requiredString($category, 'slug'),
                'name' => $this->requiredString($category, 'name'),
            ],
            'submission' => [
                'participation_type' => $this->participationType($submission),
                'title' => $this->requiredString($submission, 'title'),
                'summary' => $this->requiredString($submission, 'summary'),
                'description_html' => $this->projectHtml($this->requiredString($submission, 'description_html')),
                'description_text' => $this->requiredString($submission, 'description_text'),
            ],
            'external_links' => array_map(fn (mixed $link): array => $this->externalLink($link), $externalLinks),
        ];

        $files = $this->files($version, $snapshotFiles);

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'payload' => $payload,
            'payload_sha256' => $this->canonicalJson->hash($payload),
            'files' => $files,
        ];
    }

    private function projectHtml(string $html): string
    {
        $safe = $this->sanitizer->sanitize($html);

        // El editor vigente no incrusta imágenes en el HTML. Cualquier etiqueta media
        // inesperada se elimina; las imágenes capturadas se sirven desde el inventario M5.
        return preg_replace('#<(?:img|picture|source|video|audio)\b[^>]*>(?:</(?:picture|video|audio)>)?#i', '', $safe) ?? '';
    }

    /** @return array{kind:string,url:string,normalized_host:string} */
    private function externalLink(mixed $value): array
    {
        if (! is_array($value)) {
            throw new BlindReviewPackageRejected('invalid_external_link', 'Un vínculo capturado no tiene la estructura esperada.');
        }

        $kind = $this->requiredString($value, 'kind');
        $url = $this->requiredString($value, 'url');
        $host = strtolower($this->requiredString($value, 'normalized_host'));
        $parsed = parse_url($url);

        if (! in_array($kind, ['youtube', 'public_folder'], true)
            || ! is_array($parsed)
            || strtolower((string) ($parsed['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parsed['host'] ?? '')) !== $host
            || isset($parsed['user']) || isset($parsed['pass'])) {
            throw new BlindReviewPackageRejected('invalid_external_link', 'Un vínculo capturado no cumple el contrato HTTPS inmutable.');
        }

        return ['kind' => $kind, 'url' => $url, 'normalized_host' => $host];
    }

    /** @param list<mixed> $snapshotFiles
     * @return list<array<string,mixed>>
     */
    private function files(SubmissionVersion $version, array $snapshotFiles): array
    {
        $liveFiles = SubmissionFile::query()
            ->where('submission_id', $version->submission_id)
            ->orderBy('id')
            ->get()
            ->keyBy('public_id');

        if ($liveFiles->count() !== count($snapshotFiles)) {
            throw new BlindReviewPackageRejected('file_inventory_drift', 'El inventario vivo no coincide exactamente con la versión capturada.');
        }

        $seen = [];
        $kindCounters = ['document' => 0, 'editor_image' => 0];
        $result = [];
        foreach ($snapshotFiles as $index => $snapshotFile) {
            if (! is_array($snapshotFile)) {
                throw new BlindReviewPackageRejected('invalid_snapshot_file', 'Un anexo capturado no tiene la estructura esperada.');
            }

            $publicId = $this->requiredString($snapshotFile, 'public_id');
            if (isset($seen[$publicId])) {
                throw new BlindReviewPackageRejected('duplicate_snapshot_file', 'La versión contiene un anexo duplicado.');
            }
            $seen[$publicId] = true;

            /** @var SubmissionFile|null $file */
            $file = $liveFiles->get($publicId);
            if (! $file || $file->submission_id !== $version->submission_id) {
                throw new BlindReviewPackageRejected('crossed_snapshot_file', 'Un anexo no pertenece a la propuesta fijada.');
            }

            $kind = $this->requiredString($snapshotFile, 'kind');
            if (! array_key_exists($kind, $kindCounters)) {
                throw new BlindReviewPackageRejected('invalid_snapshot_file_kind', 'La clase del anexo no es evaluable.');
            }

            $expected = [
                'kind' => $kind,
                'mime_type' => $this->requiredString($snapshotFile, 'mime_type'),
                'extension' => strtolower($this->requiredString($snapshotFile, 'extension')),
                'size_bytes' => $this->requiredInteger($snapshotFile, 'size_bytes'),
                'sha256' => strtolower($this->requiredHash($snapshotFile, 'sha256')),
            ];
            $this->assertSafeFileMetadata($expected);
            $this->integrity->verify($file, $expected);

            $kindCounters[$kind]++;
            $number = str_pad((string) $kindCounters[$kind], 2, '0', STR_PAD_LEFT);
            $label = ($kind === BlindReviewFileClass::Document->value ? 'Documento ' : 'Imagen de apoyo ')
                .$number.'.'.$expected['extension'];

            $result[] = [
                'submission_file_id' => $file->id,
                'display_order' => $index + 1,
                'file_class' => $kind,
                'neutral_label' => $label,
                'expected_mime' => $expected['mime_type'],
                'expected_extension' => $expected['extension'],
                'expected_size_bytes' => $expected['size_bytes'],
                'expected_sha256' => $expected['sha256'],
            ];
        }

        if (count($seen) !== $liveFiles->count()) {
            throw new BlindReviewPackageRejected('file_inventory_drift', 'La versión no captura exactamente todos los anexos evaluables.');
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function requiredMap(array $source, string $key): array
    {
        if (! isset($source[$key]) || ! is_array($source[$key]) || array_is_list($source[$key])) {
            throw new BlindReviewPackageRejected('invalid_snapshot_shape', "Falta la sección {$key} de la versión final.");
        }

        return $source[$key];
    }

    private function requiredString(array $source, string $key): string
    {
        if (! array_key_exists($key, $source) || ! is_string($source[$key]) || trim($source[$key]) === '') {
            throw new BlindReviewPackageRejected('invalid_snapshot_field', "El campo {$key} no es válido en la versión final.");
        }

        return $source[$key];
    }

    private function requiredInteger(array $source, string $key): int
    {
        if (! array_key_exists($key, $source) || ! is_int($source[$key]) || $source[$key] < 0) {
            throw new BlindReviewPackageRejected('invalid_snapshot_field', "El campo {$key} no es válido en un anexo.");
        }

        return $source[$key];
    }

    private function requiredHash(array $source, string $key): string
    {
        $hash = $this->requiredString($source, $key);
        if (! preg_match('/^[0-9a-f]{64}$/i', $hash)) {
            throw new BlindReviewPackageRejected('invalid_snapshot_field', 'El hash capturado no es válido.');
        }

        return $hash;
    }

    private function participationType(array $submission): string
    {
        $type = $this->requiredString($submission, 'participation_type');
        if (! in_array($type, ['individual', 'team'], true)) {
            throw new BlindReviewPackageRejected('invalid_participation_type', 'La modalidad capturada no es válida.');
        }

        return $type;
    }

    /** @param array{kind:string,mime_type:string,extension:string,size_bytes:int,sha256:string} $expected */
    private function assertSafeFileMetadata(array $expected): void
    {
        $allowedExtensions = $expected['kind'] === BlindReviewFileClass::EditorImage->value
            ? config('flowerflow.allowed_editor_image_extensions', [])
            : config('flowerflow.allowed_document_extensions', []);

        if (! preg_match('/^[a-z0-9]{1,16}$/', $expected['extension'])
            || ! in_array($expected['extension'], $allowedExtensions, true)
            || ! preg_match('#^[a-z0-9][a-z0-9.+-]*/[a-z0-9][a-z0-9.+-]*$#i', $expected['mime_type'])) {
            throw new BlindReviewPackageRejected('unsafe_snapshot_file_metadata', 'Los metadatos técnicos del anexo no son seguros.');
        }
    }
}
