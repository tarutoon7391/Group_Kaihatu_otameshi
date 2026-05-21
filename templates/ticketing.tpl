{*
    S-03 購入完了画面テンプレート（画面担当）

    PHP からアサインされる変数：
      - $purchaseId    ... 採番された購入ID（チケット番号）
      - $totalAmount   ... 支払合計金額
      - $totalCount    ... 合計枚数
      - $pdfDownloadUrl ... PDFダウンロード用URL（例：ticketing.php?download=1&purchase_id=...）
*}
{include file="header.tpl" pageTitle="購入完了"}

<section class="card">
    <h2 class="card__title">ご購入ありがとうございました</h2>

    <dl class="complete-info">
        <dt>チケット番号</dt>
        <dd>#{$purchaseId|escape}</dd>
        <dt>合計枚数</dt>
        <dd>{$totalCount}枚</dd>
        <dt>合計金額</dt>
        <dd>{$totalAmount|number_format}円</dd>
    </dl>

    <p class="complete-message">
        下記より入園券PDFをダウンロードし、当日入園口でご提示ください。
    </p>

    <div class="complete-buttons">
        <a href="{$pdfDownloadUrl|escape}" class="btn btn--primary">入園券PDFをダウンロード</a>
        <a href="index.php?reset=1" class="btn btn--secondary">最初に戻る</a>
    </div>
</section>

{include file="footer.tpl"}
