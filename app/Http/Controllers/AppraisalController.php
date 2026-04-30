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

        $safeName = preg_replace('/[\\\\\/:*?"<>|\r\n]+/u', '_', trim($name));
        $safeName = $safeName !== '' ? $safeName : '鑑定者';

        return $pdf->download('運命鑑定書_' . $safeName . '.pdf');
    }
}
