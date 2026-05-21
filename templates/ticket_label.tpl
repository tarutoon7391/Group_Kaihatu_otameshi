{*
    入園券ラベル PDF テンプレート（PDF担当）

    PHP からアサインされる変数：
      - $purchaseId      ... チケット番号（purchase_id）
      - $purchasedAt     ... 購入日時（DateTime互換の文字列）
      - $expirationDate  ... 有効期限（Y/m/d）
      - $lineItems       ... [['name'=>'幼児','quantity'=>2,'unit_price'=>100,'subtotal'=>200], ...]
      - $totalAmount     ... 合計金額
      - $totalCount      ... 合計枚数
*}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スペースワールド 入園券 #{$purchaseId|escape}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: "IPAGothic", "MS Gothic", sans-serif; color: #222; }
        .ticket {
            border: 3px solid #003366;
            padding: 16px 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .ticket__title { font-size: 22pt; margin: 0 0 8px; color: #003366; }
        .ticket__subtitle { font-size: 10pt; margin: 0 0 16px; color: #666; }
        .ticket__no { font-size: 16pt; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #999; padding: 6px 8px; font-size: 10pt; }
        th { background: #eef2f7; }
        .num { text-align: right; }
        .ticket__total { margin-top: 12px; font-size: 14pt; text-align: right; }
        .ticket__meta { margin-top: 16px; font-size: 10pt; }
        .ticket__meta dt { float: left; clear: left; width: 80px; font-weight: bold; }
        .ticket__meta dd { margin-left: 90px; margin-bottom: 4px; }
        .ticket__notice { margin-top: 18px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
<div class="ticket">
    <h1 class="ticket__title">スペースワールド 入園券</h1>
    <p class="ticket__subtitle">SpaceWorld Admission Ticket</p>

    <p class="ticket__no">チケット番号：<strong>#{$purchaseId|escape}</strong></p>

    <table>
        <thead>
            <tr>
                <th>区分</th>
                <th>単価</th>
                <th>枚数</th>
                <th>小計</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$lineItems item=item}
                <tr>
                    <td>{$item.name|escape}</td>
                    <td class="num">{$item.unit_price|number_format}円</td>
                    <td class="num">{$item.quantity}枚</td>
                    <td class="num">{$item.subtotal|number_format}円</td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    <p class="ticket__total">
        合計 {$totalCount}枚 / <strong>{$totalAmount|number_format}円</strong>
    </p>

    <dl class="ticket__meta">
        <dt>購入日時</dt><dd>{$purchasedAt|escape}</dd>
        <dt>有効期限</dt><dd>{$expirationDate|escape}</dd>
    </dl>

    <p class="ticket__notice">
        ※本券は有効期限内に1回限り有効です。紛失・盗難の際の再発行はいたしかねます。
    </p>
</div>
</body>
</html>
