<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @font-face {
            font-family: 'NotoSansJP';
            font-style: normal;
            font-weight: normal;
            src: url("{{ storage_path('fonts/Noto_Sans_JP/static/NotoSansJP-Regular.ttf') }}") format('truetype');
        }

        /* 文字化け防止：すべてのHTML要素にフォントを適用 */
        html, body, table, tr, th, td, div, span, h1, h2, strong, small {
            font-family: 'NotoSansJP', sans-serif !important;
        }

        body { 
            color: #1e1b4b; 
            line-height: 1.4; 
            margin: 0; 
            padding: 0;
            font-size: 11px;
        }

        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 5px; margin-bottom: 15px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .section-title { background: #eef2ff; border-left: 6px solid #4f46e5; padding: 4px 8px; font-weight: bold; margin: 15px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        th, td { border: 1px solid #e2e8f0; padding: 5px; text-align: center; word-wrap: break-word; }
        th { background: #f1f5f9; color: #475569; font-size: 9px; }
        .kanji { font-size: 16px; font-weight: bold; }
        .ten-god-label { background: #4f46e5; color: white; padding: 1px 4px; border-radius: 2px; font-size: 9px; display: inline-block; margin-top: 2px; }
        .comment-box { border: 1px solid #e2e8f0; padding: 8px; font-size: 10px; background: #fafafa; }
        .nichiun-cell { border: 1px solid #cbd5e1; padding: 3px; font-size: 8px; vertical-align: top; height: 35px; }
    </style>
</head>
<body>
    <div class="header"><h1>運命鑑定書</h1></div>
    
    <div style="margin-bottom: 10px;">
        鑑定者：{{ $user['name'] }} 様（{{ $user['gender'] }}）<br>
        生年月日：{{ $user['birthday'] }}
    </div>

    <div class="section-title">基本命式 (泰山流)</div>
    <table>
        <tr><th>時柱</th><th>日柱</th><th>月柱</th><th>年柱</th></tr>
        <tr>
            @foreach(['hour', 'day', 'month', 'year'] as $p)
            <td>
                <div class="kanji">{{ $result['pillars'][$p]['kanji'] }}</div>
                <div class="ten-god-label">{{ $result['pillars'][$p]['ten_god']['name'] }}</div><br>
                <small>蔵干：{{ $result['pillars'][$p]['zokan']['ten_god_name'] }}</small><br>
                <small style="color: #ea580c;">{{ $result['pillars'][$p]['twelve_life_stage']['name'] }}</small>
            </td>
            @endforeach
        </tr>
    </table>

    <div class="section-title">2026年 歳運：{{ $result['saiun']['kanji'] }}（{{ $result['saiun']['ten_god'] }}）</div>
    <div class="comment-box">{{ $appraisal['saiun_comment'] }}</div>

    <div class="section-title">一生の歩み（大運） - 立運：{{ $result['dayun']['start_age_full'] }}</div>
    @foreach($result['dayun']['cycles'] as $idx => $cycle)
        <div style="font-size: 9px; margin-bottom: 3px; border-bottom: 1px solid #f1f5f9; padding-bottom: 2px;">
            <strong>{{ $cycle['age'] }}歳〜：{{ $cycle['kanji'] }}（{{ $cycle['ten_god'] }}）</strong>
            <span style="color: #666;">- {{ $appraisal['dayun_comments'][$idx]['comment'] ?? '' }}</span>
        </div>
    @endforeach

    <div class="section-title">2026年 月運一覧</div>
    <table>
        <thead>
            <tr><th width="10%">月</th><th width="12%">干支/星</th><th>運勢解説</th></tr>
        </thead>
        <tbody>
            @foreach($result['getsuun'] as $idx => $m)
            <tr>
                <td>{{ $m['month_name'] }}</td>
                <td>{{ $m['kanji'] }}<br><small>{{ $m['ten_god'] }}</small></td>
                <td style="text-align:left;">{{ $appraisal['getsuun_comments'][$idx]['comment'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">今月（{{ $currentMonth['month_name'] }}）の日運詳細</div>
    <table>
        @foreach(array_chunk($currentMonth['days'], 7) as $week)
        <tr>
            @foreach($week as $day)
            <td class="nichiun-cell">
                <div style="font-weight: bold; color: #4f46e5; border-bottom: 1px solid #e2e8f0; margin-bottom: 2px;">{{ $day['day'] }}日</div>
                {{ $day['kanji'] }}<br>
                {{ $day['ten_god'] }}
            </td>
            @endforeach
            @if(count($week) < 7)
                @for($i=0; $i < 7 - count($week); $i++) <td class="nichiun-cell"></td> @endfor
            @endif
        </tr>
        @endforeach
    </table>

    <div style="margin-top:20px; font-size:8px; text-align:center; color: #64748b;">
        ※本鑑定書は四柱推命学（泰山流）に基づき算出されたものです。
    </div>
</body>
</html>
