<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarCoverageService;
use Illuminate\Http\JsonResponse;

class CalendarCoverageController extends Controller
{
    public function __invoke(CalendarCoverageService $coverageService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $coverageService->getCoverage(),
        ]);
    }
}
