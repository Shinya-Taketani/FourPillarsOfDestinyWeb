<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    @php
        $fontRegular = str_replace('\\', '/', storage_path('fonts/Noto_Sans_JP/static/NotoSansJP-Regular.ttf'));
        $fontBold = str_replace('\\', '/', storage_path('fonts/Noto_Sans_JP/static/NotoSansJP-Bold.ttf'));
    @endphp
    <style>
        @font-face {
            font-family: 'NotoSansJP';
            font-style: normal;
            font-weight: 400;
            src: url("file://{{ $fontRegular }}") format('truetype');
        }

        @font-face {
            font-family: 'NotoSansJP';
            font-style: normal;
            font-weight: 700;
            src: url("file://{{ $fontBold }}") format('truetype');
        }

        html, body, table, tr, th, td, div, span, h1, h2, strong, small {
            font-family: 'NotoSansJP', sans-serif !important;
        }

        body { color: #1e1b4b; line-height: 1.4; font-size: 10px; margin: 0; padding: 0; }
        .header { border-bottom: 3px solid #4f46e5; text-align: center; padding: 10px; margin-bottom: 20px; }
        .total-score { text-align: center; background: #f8fafc; padding: 20px; border-radius: 20px; margin-bottom: 20px; }
        .score-val { font-size: 48px; color: #4f46e5; font-weight: bold; }
        .section-title { background: #eef2ff; border-left: 6px solid #4f46e5; padding: 4px 8px; font-weight: bold; margin: 15px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        th, td { border: 1px solid #e2e8f0; padding: 5px; text-align: center; }
        th { background: #f1f5f9; font-size: 8px; }
        .kanji { font-size: 14px; font-weight: bold; }
        .compatibility-card { border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 10px; background: #fff; }
        .advice { color: #4f46e5; font-style: bold; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header"><h1>精密相性鑑定書</h1></div>

    <div class="total-score">
        <div>総合相性スコア</div>
        <div class="score-val">{{ $compatibility['total_score'] }}点</div>
        <div style="font-size: 16px; font-weight: bold;">“ {{ $compatibility['summary'] }} ”</div>
    </div>

    <div class="section-title">基本命式の比較</div>
    <table>
        <tr>
            <th>区分</th><th>年柱</th><th>月柱</th><th>日柱</th><th>時柱</th>
        </tr>
        <tr>
            <td style="background: #eef2ff;">{{ $person1['name'] }}</td>
            @foreach(['year', 'month', 'day', 'hour'] as $p)
                <td><div class="kanji">{{ $person1['result']['pillars'][$p]['kanji'] }}</div><small>{{ $person1['result']['pillars'][$p]['ten_god']['name'] }}</small></td>
            @endforeach
        </tr>
        <tr>
            <td style="background: #fff1f2;">{{ $person2['name'] }}</td>
            @foreach(['year', 'month', 'day', 'hour'] as $p)
                <td><div class="kanji">{{ $person2['result']['pillars'][$p]['kanji'] }}</div><small>{{ $person2['result']['pillars'][$p]['ten_god']['name'] }}</small></td>
            @endforeach
        </tr>
    </table>

    <div class="section-title">相性の詳細分析</div>
    @foreach($compatibility['details'] as $key => $rel)
    <div class="compatibility-card">
        <strong>【{{ $key === 'year_relation' ? 'ルーツ' : ($key === 'stem_relation' ? '精神' : '活力') }}の相性】 {{ $rel['name'] }}</strong>
        <span style="float: right; font-weight: bold; color: {{ $rel['score'] >= 0 ? '#10b981' : '#f97316' }};">
            {{ $rel['score'] >= 0 ? '+' : '' }}{{ $rel['score'] }}
        </span>
        <p style="margin: 5px 0;">{{ $rel['conclusion'] }}</p>
        <p class="advice">助言：{{ $rel['advice'] }}</p>
    </div>
    @endforeach

    <div style="margin-top: 30px; font-size: 8px; text-align: center; color: #94a3b8;">
        ※本鑑定書は四柱推命学に基づき、二人の生年月日時のエネルギー干渉を算出したものです。
    </div>
</body>
</html>
