<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\CompatibilityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/analyze', [AnalysisController::class, 'store']);
Route::post('/analyze-compatibility', [CompatibilityController::class, 'analyze']);
