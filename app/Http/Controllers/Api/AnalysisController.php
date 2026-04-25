<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DestinyCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    public function __construct(
        private DestinyCalculationService $calculationService
    ) {}

    /**
     * 鑑定リクエストを受け取る
     */
    public function store(Request $request): JsonResponse
    {
        // バリデーション
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'birthday'  => 'required|date',
            'longitude' => 'required|numeric|between:120,150', // 日本近海
        ]);

        // 鑑定実行
        $result = $this->calculationService->analyze(
            $validated['birthday'],
            (float) $validated['longitude']
        );

        return response()->json([
            'status' => 'success',
            'target_name' => $validated['name'],
            'data' => $result
        ]);
    }
}
