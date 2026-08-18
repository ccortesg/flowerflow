<?php

namespace App\Actions\BlindReview;

use App\Enums\BlindReviewPackageStatus;
use App\Exceptions\BlindReviewPackageRejected;
use App\Models\BlindReviewPackage;
use App\Models\BlindReviewPackageFile;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Services\AssignmentEligibility;
use App\Services\AuditLogger;
use App\Services\BlindReviewPackageBuilder;
use App\Services\JudgeAssignmentCoverage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GenerateBlindReviewPackageDraft
{
    public function __construct(
        private EnsureBlindReviewAdministrator $ensureActor,
        private AssignmentEligibility $eligibility,
        private JudgeAssignmentCoverage $coverage,
        private BlindReviewPackageBuilder $builder,
        private AuditLogger $audit,
    ) {}

    public function execute(Submission $submission, User $actor, string $reason): BlindReviewPackage
    {
        $this->ensureActor->execute($actor, 'manage blind review packages');

        try {
            return DB::transaction(function () use ($submission, $actor, $reason): BlindReviewPackage {
                $version = $this->eligibility->requireCurrentVersion($submission, true);
                SubmissionVersion::query()->whereKey($version->id)->lockForUpdate()->firstOrFail();
                $assignments = JudgeAssignment::query()
                    ->where('submission_version_id', $version->id)
                    ->with('replacementAssignment:id,replaces_assignment_id,status')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if (! $this->coverage->fromAssignments($assignments)['complete']) {
                    throw new BlindReviewPackageRejected('assignment_coverage_incomplete', 'La propuesta no conserva cobertura completa de cuatro evaluaciones.');
                }

                SubmissionFile::query()
                    ->where('submission_id', $version->submission_id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $built = $this->builder->build($version);
                $package = BlindReviewPackage::query()
                    ->where('submission_version_id', $version->id)
                    ->lockForUpdate()
                    ->first();

                if ($package?->status === BlindReviewPackageStatus::Active) {
                    if ($this->matches($package, $built)) {
                        return $package->load('files');
                    }

                    throw new BlindReviewPackageRejected('active_package_diverged', 'El paquete activo diverge y no puede sobrescribirse.');
                }

                if ($package?->status === BlindReviewPackageStatus::Invalidated) {
                    throw new BlindReviewPackageRejected('invalidated_package_terminal', 'El paquete invalidado es evidencia terminal y no puede regenerarse.');
                }

                $now = now('UTC');
                if ($package) {
                    DB::table('blind_review_package_files')->where('blind_review_package_id', $package->id)->delete();
                    DB::table('blind_review_packages')->where('id', $package->id)->update([
                        'schema_version' => $built['schema_version'],
                        'payload' => json_encode($built['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'payload_sha256' => $built['payload_sha256'],
                        'generated_by_user_id' => $actor->id,
                        'generation_reason' => trim($reason),
                        'generated_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $package->refresh();
                } else {
                    $package = new BlindReviewPackage;
                    $package->forceFill([
                        'submission_version_id' => $version->id,
                        'schema_version' => $built['schema_version'],
                        'status' => BlindReviewPackageStatus::Draft->value,
                        'payload' => $built['payload'],
                        'payload_sha256' => $built['payload_sha256'],
                        'generated_by_user_id' => $actor->id,
                        'generation_reason' => trim($reason),
                        'generated_at' => $now,
                    ])->save();
                }

                foreach ($built['files'] as $attributes) {
                    $file = new BlindReviewPackageFile;
                    $file->forceFill([
                        'blind_review_package_id' => $package->id,
                        ...$attributes,
                        'status' => BlindReviewPackageStatus::Draft->value,
                    ])->save();
                }

                $package->load('files');
                $this->audit->record('blind_review_package.draft_generated', $package, $actor, [
                    'submission_version_id' => $version->id,
                    'schema_version' => $package->schema_version,
                    'payload_sha256' => $package->payload_sha256,
                    'file_ids' => $package->files->pluck('id')->all(),
                    'file_count' => $package->files->count(),
                ]);

                return $package;
            }, 5);
        } catch (BlindReviewPackageRejected $exception) {
            $this->audit->record('blind_review_package.generation_rejected', $submission, $actor, [
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['package' => $exception->getMessage()]);
        }
    }

    /** @param array{schema_version:int,payload:array<string,mixed>,payload_sha256:string,files:list<array<string,mixed>>} $built */
    private function matches(BlindReviewPackage $package, array $built): bool
    {
        $files = $package->files()->get();

        return $package->schema_version === $built['schema_version']
            && hash_equals($package->payload_sha256, $built['payload_sha256'])
            && $files->every(fn (BlindReviewPackageFile $file): bool => $file->status === BlindReviewPackageStatus::Active)
            && $this->fileManifest($package) === $built['files'];
    }

    /** @return list<array<string,mixed>> */
    private function fileManifest(BlindReviewPackage $package): array
    {
        return $package->files()->get()->map(fn (BlindReviewPackageFile $file): array => [
            'submission_file_id' => $file->submission_file_id,
            'display_order' => $file->display_order,
            'file_class' => $file->file_class->value,
            'neutral_label' => $file->neutral_label,
            'expected_mime' => $file->expected_mime,
            'expected_extension' => $file->expected_extension,
            'expected_size_bytes' => $file->expected_size_bytes,
            'expected_sha256' => $file->expected_sha256,
        ])->all();
    }
}
