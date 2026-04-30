<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DestinyCalculationService;
use App\Services\AppraisalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;
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

    /**
     * 相性鑑定PDFをダウンロード
     */
    public function downloadPdf(Request $request, DestinyCalculationService $calc, AppraisalService $appraisal)
    {
        // データの再算出（安全のため）
        $res1 = $calc->analyze($request->person1['birthday'], (float)$request->person1['longitude'], $request->person1['gender']);
        $res2 = $calc->analyze($request->person2['birthday'], (float)$request->person2['longitude'], $request->person2['gender']);
        $compatibility = $appraisal->compareDestiny($res1, $res2);

        $data = [
            'person1' => [
                'name' => $request->person1['name'],
                'birthday' => \Carbon\Carbon::parse($request->person1['birthday'])->format('Y年m月d日H:i') . '生まれ',
                'result' => $res1
            ],
            'person2' => [
                'name' => $request->person2['name'],
                'birthday' => \Carbon\Carbon::parse($request->person2['birthday'])->format('Y年m月d日H:i') . '生まれ',
                'result' => $res2
            ],
            'compatibility' => $compatibility
        ];

        // PDFオプション設定
        Pdf::setOption([
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'defaultFont' => 'NotoSansJP',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'chroot' => base_path(),
        ]);

        $pdf = Pdf::loadView('pdf.compatibility', $data)->setPaper('a4', 'portrait');

        $fileName = '相性鑑定書_' . $request->person1['name'] . '_' . $request->person2['name'] . '.pdf';
        return $pdf->download($fileName);
    }
}
