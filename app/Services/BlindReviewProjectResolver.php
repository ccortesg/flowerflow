<?php

namespace App\Services;

use App\Actions\Evaluations\EnsureEvaluationDraftContext;
use App\Enums\BlindReviewPackageStatus;
use App\Exceptions\BlindReviewPackageRejected;
use App\Exceptions\EvaluationDraftRejected;
use App\Models\BlindReviewPackage;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class BlindReviewProjectResolver
{
    public function __construct(
        private EnsureEvaluationDraftContext $evaluationContext,
        private CanonicalJson $canonicalJson,
    ) {}

    public function resolve(JudgeAssignment $assignment, User $actor): BlindReviewPackage
    {
        Gate::forUser($actor)->authorize('view', $assignment);

        if (! Evaluation::query()->where('judge_assignment_id', $assignment->id)->exists()) {
            throw new BlindReviewPackageRejected('evaluation_not_started', 'Primero inicia explícitamente la evaluación.');
        }

        try {
            $context = $this->evaluationContext->execute($assignment, $actor, false, false);
        } catch (EvaluationDraftRejected $exception) {
            throw new BlindReviewPackageRejected($exception->reasonCode, $exception->getMessage());
        }

        $package = $context['package']->load('files');
        Gate::forUser($actor)->authorize('consume', [$package, $context['assignment']]);

        if ($package->status !== BlindReviewPackageStatus::Active
            || $package->schema_version !== BlindReviewPackageBuilder::SCHEMA_VERSION
            || ! is_array($package->payload)
            || $this->canonicalJson->hash($package->payload) !== $package->payload_sha256
            || $package->files->contains(fn ($file): bool => $file->status !== BlindReviewPackageStatus::Active
                || $file->blind_review_package_id !== $package->id)) {
            throw new BlindReviewPackageRejected('package_integrity_diverged', 'El paquete ciego no conserva su integridad aprobada.');
        }

        $this->assertPayload($package->payload);

        return $package;
    }

    /** @param array<string,mixed> $payload */
    private function assertPayload(array $payload): void
    {
        if (array_diff(array_keys($payload), ['category', 'submission', 'external_links']) !== []
            || ! is_array($payload['category'] ?? null)
            || ! is_array($payload['submission'] ?? null)
            || ! is_array($payload['external_links'] ?? null)
            || ! array_is_list($payload['external_links'])
            || ! $this->hasExactKeys($payload['category'], ['slug', 'name'])
            || ! $this->hasExactKeys($payload['submission'], ['participation_type', 'title', 'summary', 'description_html', 'description_text'])) {
            throw new BlindReviewPackageRejected('package_payload_invalid', 'El contenido del paquete ciego no respeta el esquema aprobado.');
        }

        foreach (['slug', 'name'] as $key) {
            if (! is_string($payload['category'][$key] ?? null) || trim($payload['category'][$key]) === '') {
                throw new BlindReviewPackageRejected('package_payload_invalid', 'La categoría del paquete ciego no es válida.');
            }
        }
        foreach (['participation_type', 'title', 'summary', 'description_html', 'description_text'] as $key) {
            if (! is_string($payload['submission'][$key] ?? null)
                || trim($payload['submission'][$key]) === '') {
                throw new BlindReviewPackageRejected('package_payload_invalid', 'El proyecto del paquete ciego no es válido.');
            }
        }
        if (! in_array($payload['submission']['participation_type'], ['individual', 'team'], true)) {
            throw new BlindReviewPackageRejected('package_payload_invalid', 'La modalidad del paquete ciego no es válida.');
        }

        foreach ($payload['external_links'] as $link) {
            if (! is_array($link)
                || array_diff(array_keys($link), ['kind', 'url', 'normalized_host']) !== []
                || ! in_array($link['kind'] ?? null, ['youtube', 'public_folder'], true)
                || ! is_string($link['url'] ?? null)
                || ! is_string($link['normalized_host'] ?? null)) {
                throw new BlindReviewPackageRejected('package_payload_invalid', 'Un enlace del paquete ciego no es válido.');
            }
            $parts = parse_url($link['url']);
            if (! is_array($parts)
                || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
                || strtolower((string) ($parts['host'] ?? '')) !== strtolower($link['normalized_host'])
                || isset($parts['user'])
                || isset($parts['pass'])) {
                throw new BlindReviewPackageRejected('package_payload_invalid', 'Un enlace del paquete ciego no cumple el contrato HTTPS.');
            }
        }
    }

    /** @param list<string> $expected */
    private function hasExactKeys(array $value, array $expected): bool
    {
        $keys = array_keys($value);
        sort($keys);
        sort($expected);

        return $keys === $expected;
    }
}
