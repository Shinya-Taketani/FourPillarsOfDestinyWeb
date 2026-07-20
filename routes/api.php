<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CalendarCoverageController;
use App\Http\Controllers\CompatibilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/analyze', [AnalysisController::class, 'store']);
Route::post('/analyze-compatibility', [CompatibilityController::class, 'analyze']);
Route::get('/calendar-coverage', CalendarCoverageController::class);
