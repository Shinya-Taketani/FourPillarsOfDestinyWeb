<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppraisalPdfRequest;
use App\Services\DestinyCalculationService;
use App\Support\PdfFileNameSanitizer;
use Barryvdh\DomPDF\Facade\Pdf;

class AppraisalController extends Controller
{
    public function __construct(
        protected DestinyCalculationService $calculationService
    ) {}

    public function downloadPdf(AppraisalPdfRequest $request)
    {
        $validated = $request->validatedForAnalysis();
        $name = $validated['name'] !== '' ? $validated['name'] : '鑑定者';

        $result = $this->calculationService->analyze(
            $validated['birth_datetime'],
            $validated['longitude'],
            $validated['gender'],
            $validated['target_datetime'],
        );
        
        $currentMonthIdx = (int)date('n') - 1; 
        $currentMonthData = $result['getsuun'][$currentMonthIdx] ?? $result['getsuun'][0];

        $data = [
            'user' => [
                'name' => $name,
                'birthday' => $validated['birth_datetime'],
                'gender' => $validated['gender'] === 'male' ? '男性' : '女性',
            ],
            'result' => $result,
            'appraisal' => $result['appraisal'],
            'currentMonth' => $currentMonthData,
        ];

        // 3. DomPDFオプションの設定
        Pdf::setOption([
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'defaultFont' => 'NotoSansJP',
            'isHtml5ParserEnabled' => true,

            // ローカルフォントを使うだけなら remote は不要
            'isRemoteEnabled' => false,

            // storage/fonts が base_path 配下なので base_path で問題なし
            'chroot' => base_path(),

            // 日本語フォントのPDFサイズ肥大化を抑えたい場合
            'isFontSubsettingEnabled' => true,
        ]);

        $pdf = Pdf::loadView('pdf.appraisal', $data)
            ->setPaper('a4', 'portrait');

        $safeName = PdfFileNameSanitizer::part($name, '鑑定者');

        return $pdf->download('運命鑑定書_' . $safeName . '.pdf');
    }
}
