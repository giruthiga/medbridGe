<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\MappingController;
use App\Http\Controllers\CleaningController;
use App\Http\Controllers\ReadinessController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    // Global pages
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // Uploads
    Route::get('/uploads', [UploadController::class, 'index'])->name('uploads.index');
    Route::get('/uploads/create', [UploadController::class, 'create'])->name('uploads.create');
    Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');
    Route::post('/uploads/generate-sample', [UploadController::class, 'generateSample'])->name('uploads.generate-sample');

    // Per-upload actions (must come AFTER create/generate-sample)
    Route::get('/uploads/{upload}/map', [MappingController::class, 'show'])->name('mapping.show');
    Route::post('/uploads/{upload}/map', [MappingController::class, 'store'])->name('mapping.store');
    Route::post('/uploads/{upload}/clean', [CleaningController::class, 'run'])->name('cleaning.run');
    Route::get('/uploads/{upload}/cleaned', [CleaningController::class, 'preview'])->name('cleaning.preview');
    Route::post('/uploads/{upload}/score', [ReadinessController::class, 'run'])->name('readiness.run');
    Route::get('/uploads/{upload}/readiness', [ReadinessController::class, 'show'])->name('readiness.show');

    // Export & Gap Analysis
    Route::get('/uploads/{upload}/export', [ExportController::class, 'index'])->name('export.index');
    Route::get('/uploads/{upload}/export/csv', [ExportController::class, 'downloadCsv'])->name('export.csv');
    Route::get('/uploads/{upload}/export/medpro', [ExportController::class, 'downloadMedProCsv'])->name('export.medpro');
    Route::get('/uploads/{upload}/export/json', [ExportController::class, 'downloadJson'])->name('export.json');
    Route::get('/uploads/{upload}/export/report', [ExportController::class, 'downloadReport'])->name('export.report');

    // Show (must come LAST — it's the catch-all)
    Route::get('/uploads/{upload}', [UploadController::class, 'show'])->name('uploads.show');
});

require __DIR__.'/auth.php';