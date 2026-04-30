<?php

namespace App\Http\Controllers;

use App\Services\DestinyCalculationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;

class AppraisalController extends Controller
{
    public function __construct(
        protected DestinyCalculationService $calculationService
    ) {}

    public function downloadPdf(Request $request)
    {
        // 1. 入力値の取得
        $name = $request->input('name', '鑑定者');
        $birthday = $request->input('birthday');
        $longitude = (float) $request->input('longitude');
        $gender = $request->input('gender', 'male');

        // 2. 鑑定データの算出
        $result = $this->calculationService->analyze($birthday, $longitude, $gender);
        
        $currentMonthIdx = (int)date('n') - 1; 
        $currentMonthData = $result['getsuun'][$currentMonthIdx] ?? $result['getsuun'][0];

        $data = [
            'user' => [
                'name' => $name,
                'birthday' => $birthday,
                'gender' => $gender === 'male' ? '男性' : '女性',
            ],
            'result' => $result,
            'appraisal' => $result['appraisal'],
            'currentMonth' => $currentMonthData,
        ];

        // 3. DomPDFオプションの設定 (TypeError回避)
        $options = new Options();
        $options->set('fontDir', storage_path('fonts/'));
        $options->set('fontCache', storage_path('fonts/'));
        $options->set('defaultFont', 'NotoSansJP');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', base_path());

        // 4. 文字化け（リテラル問題）の解決策
        // Viewをレンダリングし、UTF-8リテラルをHTMLエンティティに変換
        $html = view('pdf.appraisal', $data)->render();
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        // 5. PDF生成
        $pdf = Pdf::loadHTML($html);
        $pdf->getDomPDF()->setOptions($options);

        return $pdf->setPaper('a4', 'portrait')
                   ->download('運命鑑定書_' . $name . '.pdf');
    }
}
