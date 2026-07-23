<?php

namespace App\Http\Controllers;

use App\Enums\ResidencyVerificationStatus;
use App\Http\Requests\RespondClarificationRequest;
use App\Http\Requests\UploadResidencyDocumentRequest;
use App\Models\ClarificationRequest;
use App\Models\ClarificationResponseFile;
use App\Models\ResidencyDocument;
use App\Models\ResidencyDocumentRequest;
use App\Services\AuditLogger;
use App\Services\EligibilityReviewWorkflow;
use App\Services\PrivateEvidenceFileStore;
use App\Support\MailDispatchStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdmissibilityParticipantController extends Controller
{
    public function respond(
        RespondClarificationRequest $request,
        ClarificationRequest $clarification,
        EligibilityReviewWorkflow $workflow
    ): RedirectResponse {
        $workflow->respondToClarification(
            $clarification,
            $request->user(),
            $request->string('response')->trim()->toString(),
            $request->file('files', [])
        );

        return $this->mailAwareResponse('Recibimos tu respuesta. El equipo de revisión podrá consultarla sin modificar el contenido enviado.');
    }

    public function uploadResidency(
        UploadResidencyDocumentRequest $request,
        ResidencyDocumentRequest $residencyRequest,
        PrivateEvidenceFileStore $store,
        AuditLogger $audit
    ): RedirectResponse {
        $documents = collect();
        try {
            DB::transaction(function () use ($request, $residencyRequest, $store, $audit, $documents): void {
                $locked = ResidencyDocumentRequest::query()->lockForUpdate()->findOrFail($residencyRequest->id);
                if (! in_array($locked->status, [ResidencyVerificationStatus::Requested, ResidencyVerificationStatus::Rejected], true)) {
                    throw ValidationException::withMessages(['documents' => 'Esta solicitud ya no admite nuevos documentos.']);
                }
                $incomingFiles = $request->file('documents', []);
                $incomingBytes = collect($incomingFiles)->sum(fn ($file) => $file->getSize());
                if ($locked->documents()->count() + count($incomingFiles) > config('flowerflow.admissibility.files_per_person_request')) {
                    throw ValidationException::withMessages(['documents' => 'Esta persona ya alcanzó el máximo de tres archivos para la solicitud.']);
                }
                if ($locked->documents()->sum('size_bytes') + $incomingBytes > config('flowerflow.admissibility.files_total_kib_per_person_request') * 1024) {
                    throw ValidationException::withMessages(['documents' => 'Los documentos de esta persona no pueden superar 10 MiB acumulados.']);
                }

                foreach ($incomingFiles as $file) {
                    $document = $store->storeResidency(
                        $locked,
                        $request->user(),
                        $file,
                        $request->string('document_type')->toString(),
                        $request->string('equivalent_description')->trim()->toString() ?: null
                    );
                    $documents->push($document);
                    $audit->record('residency.document_uploaded', $document, $request->user(), [
                        'request_public_id' => $locked->public_id,
                        'document_type' => $document->document_type->value,
                        'sha256' => $document->sha256,
                        'size_bytes' => $document->size_bytes,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($documents as $document) {
                Storage::disk($document->disk)->delete($document->path);
            }
            throw $exception;
        }

        return back()->with('status', 'Los documentos se cargaron de forma privada y quedaron listos para revisión.');
    }

    public function downloadClarificationFile(
        ClarificationResponseFile $file,
        AuditLogger $audit
    ): StreamedResponse {
        $this->authorize('download', $file);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);
        $audit->record('clarification.document_downloaded', $file, request()->user(), [
            'sha256' => $file->sha256,
            'size_bytes' => $file->size_bytes,
        ]);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function downloadResidencyDocument(
        ResidencyDocument $document,
        AuditLogger $audit
    ): StreamedResponse {
        $this->authorize('download', $document);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);
        $audit->record('residency.document_downloaded', $document, request()->user(), [
            'request_public_id' => $document->request->public_id,
            'sha256' => $document->sha256,
            'size_bytes' => $document->size_bytes,
        ]);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    private function mailAwareResponse(string $message): RedirectResponse
    {
        $response = back()->with('status', $message);
        $status = app(MailDispatchStatus::class);

        return $status->failed() ? $response->with('warning', $status->warning()) : $response;
    }
}
