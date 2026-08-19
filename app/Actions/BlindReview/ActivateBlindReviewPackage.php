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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ActivateBlindReviewPackage
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

                $package = BlindReviewPackage::query()
                    ->where('submission_version_id', $version->id)
                    ->lockForUpdate()
                    ->first();
                if (! $package) {
                    throw new BlindReviewPackageRejected('package_draft_missing', 'Primero genera y valida el borrador del paquete ciego.');
                }

                $files = BlindReviewPackageFile::query()
                    ->where('blind_review_package_id', $package->id)
                    ->orderBy('display_order')
                    ->lockForUpdate()
                    ->get();
                $built = $this->builder->build($version);
                if (! $this->matches($package, $files, $built)) {
                    throw new BlindReviewPackageRejected('package_integrity_diverged', 'El borrador ya no coincide con la versión y sus anexos.');
                }

                if ($package->status === BlindReviewPackageStatus::Active) {
                    if (! $files->every(fn (BlindReviewPackageFile $file): bool => $file->status === BlindReviewPackageStatus::Active)) {
                        throw new BlindReviewPackageRejected('package_file_state_diverged', 'El estado del inventario activo es inconsistente.');
                    }

                    return $package->setRelation('files', $files);
                }
                if ($package->status !== BlindReviewPackageStatus::Draft) {
                    throw new BlindReviewPackageRejected('package_not_activatable', 'El paquete ya no está disponible para activación.');
                }
                if (! $files->every(fn (BlindReviewPackageFile $file): bool => $file->status === BlindReviewPackageStatus::Draft)) {
                    throw new BlindReviewPackageRejected('package_file_state_diverged', 'El estado del inventario borrador es inconsistente.');
                }

                $now = now('UTC');
                DB::table('blind_review_packages')->where('id', $package->id)->update([
                    'status' => BlindReviewPackageStatus::Active->value,
                    'activated_by_user_id' => $actor->id,
                    'activation_reason' => trim($reason),
                    'activated_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('blind_review_package_files')->where('blind_review_package_id', $package->id)->update([
                    'status' => BlindReviewPackageStatus::Active->value,
                    'updated_at' => $now,
                ]);

                $package->refresh()->load('files');
                $this->audit->record('blind_review_package.activated', $package, $actor, [
                    'submission_version_id' => $version->id,
                    'schema_version' => $package->schema_version,
                    'payload_sha256' => $package->payload_sha256,
                    'file_ids' => $package->files->pluck('id')->all(),
                    'file_count' => $package->files->count(),
                    'status' => BlindReviewPackageStatus::Active->value,
                ]);

                return $package;
            }, 5);
        } catch (BlindReviewPackageRejected $exception) {
            $this->audit->record('blind_review_package.activation_rejected', $submission, $actor, [
                'reason_code' => $exception->reasonCode,
            ]);

            throw ValidationException::withMessages(['package' => $exception->getMessage()]);
        }
    }

    /**
     * @param  Collection<int, BlindReviewPackageFile>  $files
     * @param  array{schema_version:int,payload:array<string,mixed>,payload_sha256:string,files:list<array<string,mixed>>}  $built
     */
    private function matches(BlindReviewPackage $package, Collection $files, array $built): bool
    {
        $manifest = $files->map(fn (BlindReviewPackageFile $file): array => [
            'submission_file_id' => $file->submission_file_id,
            'display_order' => $file->display_order,
            'file_class' => $file->file_class->value,
            'neutral_label' => $file->neutral_label,
            'expected_mime' => $file->expected_mime,
            'expected_extension' => $file->expected_extension,
            'expected_size_bytes' => $file->expected_size_bytes,
            'expected_sha256' => $file->expected_sha256,
        ])->all();

        return $package->schema_version === $built['schema_version']
            && hash_equals($package->payload_sha256, $built['payload_sha256'])
            && $manifest === $built['files'];
    }
}
