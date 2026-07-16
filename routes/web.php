<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Panel\DashboardController as PanelDashboardController;
use App\Http\Controllers\Panel\SubmissionController as PanelSubmissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::view('/documentos', 'public.documents')->name('documents');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/inicio', DashboardController::class)->name('dashboard');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/propuestas', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/propuestas/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('/propuestas/{submission}/archivos/{file}', [SubmissionController::class, 'download'])->name('submissions.files.download');

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

Route::prefix('panel')->name('panel.')->middleware(['panel.enabled', 'auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/', PanelDashboardController::class)->name('dashboard');
    Route::get('/propuestas', [PanelSubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/propuestas/{submission}', [PanelSubmissionController::class, 'show'])->name('submissions.show');
    Route::view('/cuenta', 'panel.account')->name('account');
});
