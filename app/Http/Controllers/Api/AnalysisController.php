<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\CalendarDataUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppraisalAnalyzeRequest;
use App\Services\AnalysisLogService;
use App\Services\DestinyCalculationService;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    public function __construct(
        private DestinyCalculationService $calculationService,
        private AnalysisLogService $analysisLogService,
    ) {}

    /**
     * 鑑定リクエストを受け取る
     */
    public function store(AppraisalAnalyzeRequest $request): JsonResponse
    {
        $validated = $request->validatedForAnalysis();

        try {
            $result = $this->calculationService->analyze(
                $validated['birth_datetime'],
                $validated['longitude'],
                $validated['gender'],
                $validated['target_datetime'],
            );
        } catch (CalendarDataUnavailableException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->analysisLogService->storeAppraisalIfAuthenticated($validated, $result);

        return response()->json([
            'status' => 'success',
            'target_name' => $validated['name'],
            'data' => $result,
        ]);
    }
}
