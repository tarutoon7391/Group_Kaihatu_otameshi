{*
    月次集計 PDF テンプレート（PDF担当・A4縦・F-011）

    PHP からアサインされる変数：
      - $facilityName    ... 施設名（"スペースワールド"）
      - $reportTitle     ... レポートタイトル（"月次入園集計レポート"）
      - $targetYear      ... 対象年（数値）
      - $targetMonth     ... 対象月（数値・1〜12）
      - $generatedAt     ... 出力日時（文字列）
      - $rows            ... [['name'=>'幼児','age_range'=>'0〜3歳','unit_price'=>100,'count'=>10,'subtotal'=>1000], ...]
      - $totalCount      ... 全区分合計人数
      - $totalAmount     ... 全区分合計金額
*}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>{$reportTitle|escape} {$targetYear}年{$targetMonth}月</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 16mm 22mm 16mm; }
        body { font-family: "IPAGothic", "MS Gothic", sans-serif; color: #222; font-size: 10.5pt; }

        .report-header { border-bottom: 2px solid #003366; padding-bottom: 6px; margin-bottom: 14px; }
        .report-header__facility { margin: 0; font-size: 11pt; color: #555; }
        .report-header__title { margin: 4px 0; font-size: 18pt; color: #003366; }
        .report-header__meta { margin: 0; font-size: 9pt; color: #555; text-align: right; }

        .report-target { font-size: 14pt; margin: 8px 0 12px; }

        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td {
            border: 1px solid #888; padding: 6px 8px; font-size: 10.5pt;
        }
        table.report th { background: #eef2f7; }
        table.report .num { text-align: right; }
        table.report tfoot th, table.report tfoot td {
            background: #fff8e1; font-weight: bold;
        }

        .report-signature {
            margin-top: 24px; font-size: 10pt;
        }
        .report-signature__line {
            display: inline-block;
            border-bottom: 1px solid #333;
            min-width: 160px;
            margin-left: 8px;
        }

        .page-footer {
            position: fixed;
            bottom: -10mm; left: 0; right: 0;
            text-align: center;
            font-size: 9pt; color: #666;
        }
        /* Dompdf 用のページ番号表示 */
        .page-number:before { content: counter(page); }
    </style>
</head>
<body>

<div class="report-header">
    <p class="report-header__facility">{$facilityName|escape}</p>
    <h1 class="report-header__title">{$reportTitle|escape}</h1>
    <p class="report-header__meta">出力日時：{$generatedAt|escape}</p>
</div>

<p class="report-target">対象月：<strong>{$targetYear}年{$targetMonth}月</strong></p>

<table class="report">
    <thead>
        <tr>
            <th>年齢区分</th>
            <th>対象年齢</th>
            <th>単価</th>
            <th>人数</th>
            <th>合計金額</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$rows item=row}
            <tr>
                <td>{$row.name|escape}</td>
                <td>{$row.age_range|escape}</td>
                <td class="num">{$row.unit_price|number_format}円</td>
                <td class="num">{$row.count|number_format}人</td>
                <td class="num">{$row.subtotal|number_format}円</td>
            </tr>
        {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3">全区分合計</th>
            <td class="num">{$totalCount|number_format}人</td>
            <td class="num">{$totalAmount|number_format}円</td>
        </tr>
    </tfoot>
</table>

<div class="report-signature">
    担当者署名：<span class="report-signature__line">&nbsp;</span>
</div>

<div class="page-footer">
    - <span class="page-number"></span> -
</div>

</body>
</html>
