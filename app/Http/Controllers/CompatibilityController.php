<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DestinyCalculationService;
use App\Services\AppraisalService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class CompatibilityController extends Controller
{
    /**
     * 相性鑑定画面を表示
     */
    public function index(): Response
    {
        return Inertia::render('Analysis/Compatibility');
    }

    /**
     * 相性判定API実行
     * 引数にServiceを指定することで、Laravelが10個の依存関係を自動で解決します
     */
    public function analyze(Request $request, DestinyCalculationService $calc, AppraisalService $appraisal): JsonResponse
    {
        // 1人目の鑑定
        $res1 = $calc->analyze(
            $request->person1['birthday'], 
            (float)$request->person1['longitude'], 
            $request->person1['gender']
        );
        
        // 2人目の鑑定
        $res2 = $calc->analyze(
            $request->person2['birthday'], 
            (float)$request->person2['longitude'], 
            $request->person2['gender']
        );

        // AppraisalServiceのcompareDestinyで比較
        $compatibility = $appraisal->compareDestiny($res1, $res2);

        return response()->json([
            'status' => 'success',
            'data' => [
                'person1' => $res1,
                'person2' => $res2,
                'compatibility' => $compatibility
            ]
        ]);
    }
}
