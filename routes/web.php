<?php

use App\Http\Controllers\AdmissibilityParticipantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Judge\AccessStatusController as JudgeAccessStatusController;
use App\Http\Controllers\Judge\AssignmentController as JudgeAssignmentController;
use App\Http\Controllers\Judge\BlindReviewPackageFileController as JudgeBlindReviewPackageFileController;
use App\Http\Controllers\Judge\DashboardController as JudgeDashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Panel\AccountSecurityController;
use App\Http\Controllers\Panel\AssignmentController as PanelAssignmentController;
use App\Http\Controllers\Panel\BlindReviewPackageController as PanelBlindReviewPackageController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Panel\EligibilityReviewController as PanelEligibilityReviewController;
use App\Http\Controllers\Panel\JudgeController as PanelJudgeController;
use App\Http\Controllers\Panel\RubricVersionController as PanelRubricVersionController;
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
    Route::view('/cuenta/acceso', 'account.restricted')->name('account.restricted');

    Route::middleware('business.role:participant')->group(function () {
        Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/propuestas', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('/propuestas/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::post('/propuestas/{submission}/reenviar-confirmacion', [SubmissionController::class, 'resendConfirmation'])
            ->middleware('throttle:3,10')->name('submissions.confirmation.resend');

        Route::middleware('admissibility.enabled')->group(function () {
            Route::post('/revision/aclaraciones/{clarification}/respuestas', [AdmissibilityParticipantController::class, 'respond'])
                ->middleware('throttle:panel-mutations')->name('admissibility.clarifications.respond');
            Route::post('/revision/residencia/{residencyRequest}/documentos', [AdmissibilityParticipantController::class, 'uploadResidency'])
                ->middleware('throttle:panel-mutations')->name('admissibility.residency.upload');
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

    Route::middleware('business.role:participant,reviewer,admin')->group(function () {
        Route::get('/propuestas/{submission}/archivos/{file}', [SubmissionController::class, 'download'])->name('submissions.files.download');
        Route::middleware('admissibility.enabled')->group(function () {
            Route::get('/revision/aclaraciones/archivos/{file}', [AdmissibilityParticipantController::class, 'downloadClarificationFile'])
                ->name('admissibility.clarification-files.download');
            Route::get('/revision/residencia/documentos/{document}', [AdmissibilityParticipantController::class, 'downloadResidencyDocument'])
                ->name('admissibility.residency-documents.download');
        });
    });
});

Route::prefix('juez')->name('judge.')->middleware([
    'auth',
    'business.role:judge',
    'permission:access judge workspace',
    'evaluation.enabled',
])->group(function () {
    Route::get('/estado', JudgeAccessStatusController::class)->name('status');
    Route::middleware(['verified', 'judge.active'])->group(function () {
        Route::get('/', JudgeDashboardController::class)->name('dashboard');
        Route::get('/asignaciones', [JudgeAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/asignaciones/{judgeAssignment}', [JudgeAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/asignaciones/{judgeAssignment}/anexos/{blindReviewPackageFile}', JudgeBlindReviewPackageFileController::class)
            ->name('assignments.packages.files.download');
        Route::post('/asignaciones/{judgeAssignment}/conflicto', [JudgeAssignmentController::class, 'declare'])
            ->middleware(['permission:declare own evaluation conflicts', 'throttle:panel-mutations'])
            ->name('assignments.conflicts.store');
    });
});

Route::get('/panel/login', fn () => view('auth.login', ['panel' => true]))
    ->middleware('guest')->name('panel.login');

Route::prefix('panel')->name('panel.')->middleware(['panel.enabled', 'auth', 'verified', 'business.role:reviewer,admin', 'permission:view panel'])->group(function () {
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
    Route::prefix('jueces')->name('judges.')->middleware(['business.role:admin', 'permission:view judges'])->group(function () {
        Route::get('/', [PanelJudgeController::class, 'index'])->name('index');
        Route::get('/nuevo', [PanelJudgeController::class, 'create'])
            ->middleware('permission:manage judges')->name('create');
        Route::post('/', [PanelJudgeController::class, 'store'])
            ->middleware(['permission:manage judges', 'throttle:panel-mutations'])->name('store');
        Route::get('/{judgeProfile}', [PanelJudgeController::class, 'show'])->name('show');
        Route::middleware('throttle:panel-mutations')->group(function () {
            Route::post('/{judgeProfile}/reenviar-configuracion', [PanelJudgeController::class, 'resendSetup'])
                ->middleware('permission:manage judges')->name('setup.resend');
            Route::post('/{judgeProfile}/suspender', [PanelJudgeController::class, 'suspend'])
                ->middleware('permission:manage judges')->name('suspend');
            Route::post('/{judgeProfile}/reactivar', [PanelJudgeController::class, 'reactivate'])
                ->middleware('permission:manage judges')->name('reactivate');
            Route::post('/{judgeProfile}/recuperar-2fa', [PanelJudgeController::class, 'recoverTwoFactor'])
                ->middleware('permission:recover judge two factor')->name('two-factor.recover');
        });
    });
    Route::prefix('rubricas')->name('rubrics.')->middleware(['business.role:admin', 'permission:view evaluation rubrics'])->group(function () {
        Route::get('/', [PanelRubricVersionController::class, 'index'])->name('index');
        Route::get('/nueva', [PanelRubricVersionController::class, 'create'])
            ->middleware('permission:manage evaluation rubrics')->name('create');
        Route::post('/', [PanelRubricVersionController::class, 'store'])
            ->middleware(['permission:manage evaluation rubrics', 'throttle:panel-mutations'])->name('store');
        Route::get('/{rubricVersion}', [PanelRubricVersionController::class, 'show'])->name('show');
        Route::get('/{rubricVersion}/editar', [PanelRubricVersionController::class, 'edit'])
            ->middleware('permission:manage evaluation rubrics')->name('edit');
        Route::put('/{rubricVersion}', [PanelRubricVersionController::class, 'update'])
            ->middleware(['permission:manage evaluation rubrics', 'throttle:panel-mutations'])->name('update');
        Route::post('/{rubricVersion}/activar', [PanelRubricVersionController::class, 'activate'])
            ->middleware(['permission:manage evaluation rubrics', 'throttle:panel-mutations'])->name('activate');
    });
    Route::prefix('asignaciones')->name('assignments.')->middleware(['business.role:admin', 'permission:view evaluation assignments'])->group(function () {
        Route::get('/', [PanelAssignmentController::class, 'index'])->name('index');
        Route::get('/{submission}', [PanelAssignmentController::class, 'show'])->name('show');
        Route::post('/{submission}/activar', [PanelAssignmentController::class, 'activate'])
            ->middleware(['permission:manage evaluation assignments', 'throttle:panel-mutations'])
            ->name('activate');
        Route::post('/conflictos/{judgeConflict}/resolver', [PanelAssignmentController::class, 'resolve'])
            ->middleware(['permission:resolve evaluation conflicts', 'throttle:panel-mutations'])
            ->name('conflicts.resolve');
    });
    Route::prefix('paquetes-ciegos')->name('blind-review-packages.')->middleware(['business.role:admin', 'permission:view blind review packages'])->group(function () {
        Route::get('/', [PanelBlindReviewPackageController::class, 'index'])->name('index');
        Route::get('/{submission}', [PanelBlindReviewPackageController::class, 'show'])->name('show');
        Route::post('/{submission}/generar', [PanelBlindReviewPackageController::class, 'generate'])
            ->middleware(['permission:manage blind review packages', 'throttle:panel-mutations'])->name('generate');
        Route::post('/{submission}/activar', [PanelBlindReviewPackageController::class, 'activate'])
            ->middleware(['permission:manage blind review packages', 'throttle:panel-mutations'])->name('activate');
    });
    Route::get('/cuenta', [AccountSecurityController::class, 'show'])->name('account');
    Route::prefix('cuenta/2fa')->name('account.two-factor.')->middleware('throttle:account-security')->group(function () {
        Route::post('/activar', [AccountSecurityController::class, 'enableTwoFactor'])->name('enable');
        Route::post('/confirmar', [AccountSecurityController::class, 'confirmTwoFactor'])->name('confirm');
        Route::post('/recuperacion', [AccountSecurityController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
        Route::delete('/', [AccountSecurityController::class, 'disableTwoFactor'])->name('disable');
    });
});
