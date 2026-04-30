<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnalysisWebController;
use App\Http\Controllers\CompatibilityController;
use App\Http\Controllers\AppraisalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/analysis', [AnalysisWebController::class, 'index'])->name('analysis.index');
Route::get('/compatibility', [CompatibilityController::class, 'index'])->name('compatibility.index');
Route::post('/appraisal/pdf', [AppraisalController::class, 'downloadPdf'])->name('appraisal.pdf');
Route::post('/compatibility/pdf', [CompatibilityController::class, 'downloadPdf'])->name('compatibility.pdf');

require __DIR__.'/auth.php';
