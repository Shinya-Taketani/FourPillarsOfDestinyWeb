<?php

namespace App\Http\Controllers;

use App\Exceptions\CalendarDataUnavailableException;
use App\Http\Requests\CompatibilityAnalyzeRequest;
use App\Http\Requests\CompatibilityPdfRequest;
use App\Services\DestinyCalculationService;
use App\Services\AppraisalService;
use App\Support\PdfFileNameSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public function analyze(CompatibilityAnalyzeRequest $request, DestinyCalculationService $calc, AppraisalService $appraisal): JsonResponse
    {
        $validated = $request->validatedForCompatibility();

        try {
            $res1 = $calc->analyze(
                $validated['person1']['birth_datetime'],
                $validated['person1']['longitude'],
                $validated['person1']['gender'],
                $validated['target_datetime'],
            );

            $res2 = $calc->analyze(
                $validated['person2']['birth_datetime'],
                $validated['person2']['longitude'],
                $validated['person2']['gender'],
                $validated['target_datetime'],
            );
        } catch (CalendarDataUnavailableException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

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
    public function downloadPdf(CompatibilityPdfRequest $request, DestinyCalculationService $calc, AppraisalService $appraisal)
    {
        $validated = $request->validatedForCompatibility();

        $res1 = $calc->analyze(
            $validated['person1']['birth_datetime'],
            $validated['person1']['longitude'],
            $validated['person1']['gender'],
            $validated['target_datetime'],
        );
        $res2 = $calc->analyze(
            $validated['person2']['birth_datetime'],
            $validated['person2']['longitude'],
            $validated['person2']['gender'],
            $validated['target_datetime'],
        );
        $compatibility = $appraisal->compareDestiny($res1, $res2);

        $data = [
            'person1' => [
                'name' => $validated['person1']['name'],
                'birthday' => \Carbon\Carbon::parse($validated['person1']['birth_datetime'])->format('Y年m月d日H:i') . '生まれ',
                'result' => $res1
            ],
            'person2' => [
                'name' => $validated['person2']['name'],
                'birthday' => \Carbon\Carbon::parse($validated['person2']['birth_datetime'])->format('Y年m月d日H:i') . '生まれ',
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

        $person1Name = PdfFileNameSanitizer::part($validated['person1']['name'], 'person1');
        $person2Name = PdfFileNameSanitizer::part($validated['person2']['name'], 'person2');
        $fileName = '相性鑑定書_' . $person1Name . '_' . $person2Name . '.pdf';
        return $pdf->download($fileName);
    }
}
