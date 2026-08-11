<?php

use App\Http\Controllers\AdmissibilityParticipantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Panel\AccountSecurityController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Panel\EligibilityReviewController as PanelEligibilityReviewController;
use App\Http\Controllers\Panel\SubmissionController as PanelSubmissionController;
use App\Http\Controllers\Panel\SubmissionExportController as PanelSubmissionExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::view('/documentos', 'public.documents')->name('documents');
Route::view('/correo-verificado', 'auth.email-verified')->name('verification.success');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inicio', DashboardController::class)->name('dashboard');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/propuestas', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/propuestas/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/propuestas/{submission}/archivos/{file}', [SubmissionController::class, 'download'])->name('submissions.files.download');
    Route::post('/propuestas/{submission}/reenviar-confirmacion', [SubmissionController::class, 'resendConfirmation'])
        ->middleware('throttle:3,10')->name('submissions.confirmation.resend');

    Route::middleware('admissibility.enabled')->group(function () {
        Route::post('/revision/aclaraciones/{clarification}/respuestas', [AdmissibilityParticipantController::class, 'respond'])
            ->middleware('throttle:panel-mutations')->name('admissibility.clarifications.respond');
        Route::post('/revision/residencia/{residencyRequest}/documentos', [AdmissibilityParticipantController::class, 'uploadResidency'])
            ->middleware('throttle:panel-mutations')->name('admissibility.residency.upload');
        Route::get('/revision/aclaraciones/archivos/{file}', [AdmissibilityParticipantController::class, 'downloadClarificationFile'])
            ->name('admissibility.clarification-files.download');
        Route::get('/revision/residencia/documentos/{document}', [AdmissibilityParticipantController::class, 'downloadResidencyDocument'])
            ->name('admissibility.residency-documents.download');
    });

    Route::middleware('submissions.open')->group(function () {
        Route::get('/propuestas/nueva/crear', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('/propuestas', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('/propuestas/{submission}/editar', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('/propuestas/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::post('/propuestas/{submission}/enviar', [SubmissionController::class, 'submit'])->name('submissions.submit');
        Route::delete('/propuestas/{submission}/archivos/{file}', [SubmissionController::class, 'destroyFile'])->name('submissions.files.destroy');
    });
});

Route::get('/panel/login', fn () => view('auth.login', ['panel' => true]))
    ->middleware('guest')->name('panel.login');

Route::prefix('panel')->name('panel.')->middleware(['panel.enabled', 'auth', 'verified', 'permission:view panel'])->group(function () {
    Route::get('/', PanelDashboardController::class)->name('dashboard');
    Route::middleware('permission:view submissions')->group(function () {
        Route::get('/propuestas', [PanelSubmissionController::class, 'index'])->name('submissions.index');
        Route::middleware('permission:export submissions')->group(function () {
            Route::get('/propuestas/exportaciones/nueva', [PanelSubmissionExportController::class, 'create'])
                ->middleware('password.confirm')->name('submissions.exports.create');
            Route::post('/propuestas/exportaciones', [PanelSubmissionExportController::class, 'store'])
                ->middleware('throttle:panel-mutations')->name('submissions.exports.store');
            Route::get('/propuestas/exportaciones/{submissionExport}/descargar', [PanelSubmissionExportController::class, 'download'])
                ->middleware('password.confirm')->name('submissions.exports.download');
        });
        Route::get('/propuestas/{submission}', [PanelSubmissionController::class, 'show'])->name('submissions.show');
    });

    Route::prefix('admisibilidad')->name('admissibility.')->middleware('admissibility.enabled')->group(function () {
        Route::get('/', [PanelEligibilityReviewController::class, 'index'])->name('index');
        Route::get('/{review}', [PanelEligibilityReviewController::class, 'show'])->name('show');
        Route::middleware('throttle:panel-mutations')->group(function () {
            Route::post('/{review}/iniciar', [PanelEligibilityReviewController::class, 'start'])->name('start');
            Route::post('/{review}/aclaraciones', [PanelEligibilityReviewController::class, 'requestClarification'])->name('clarifications.store');
            Route::post('/{review}/aclaraciones/{clarification}/cerrar', [PanelEligibilityReviewController::class, 'closeClarification'])->name('clarifications.close');
            Route::post('/{review}/residencia', [PanelEligibilityReviewController::class, 'requestResidency'])->name('residency.store');
            Route::post('/{review}/residencia/{residencyRequest}/revisar', [PanelEligibilityReviewController::class, 'markResidencyUnderReview'])->name('residency.review');
            Route::post('/{review}/residencia/{residencyRequest}/resolver', [PanelEligibilityReviewController::class, 'resolveResidency'])->name('residency.resolve');
            Route::post('/{review}/resolver', [PanelEligibilityReviewController::class, 'decide'])->name('decide');
        });
    });
    Route::get('/cuenta', [AccountSecurityController::class, 'show'])->name('account');
    Route::prefix('cuenta/2fa')->name('account.two-factor.')->middleware('throttle:account-security')->group(function () {
        Route::post('/activar', [AccountSecurityController::class, 'enableTwoFactor'])->name('enable');
        Route::post('/confirmar', [AccountSecurityController::class, 'confirmTwoFactor'])->name('confirm');
        Route::post('/recuperacion', [AccountSecurityController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
        Route::delete('/', [AccountSecurityController::class, 'disableTwoFactor'])->name('disable');
    });
});
