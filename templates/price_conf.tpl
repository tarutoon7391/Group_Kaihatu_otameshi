{*
    S-02 購入確認画面テンプレート（画面担当）

    PHP からアサインされる変数：
      - $lineItems   ... [['name'=>'幼児','quantity'=>2,'unit_price'=>100,'subtotal'=>200], ...]
                         （0枚の区分は事前に除外済み）
      - $totalAmount ... 合計金額
      - $totalCount  ... 合計枚数
      - $quantities  ... 戻るボタン用に S-01 で送られた区分別枚数
      - $errors      ... DB保存失敗時等のエラーメッセージ
      - $csrfToken   ... CSRFトークン
*}
{include file="header.tpl" pageTitle="購入内容のご確認"}

<section class="card">
    <h2 class="card__title">以下の内容で購入します</h2>

    {if !empty($errors)}
        <ul class="error-list">
            {foreach from=$errors item=msg}
                <li class="error-list__item">{$msg|escape}</li>
            {/foreach}
        </ul>
    {/if}

    <table class="confirm-table">
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
        <tfoot>
            <tr>
                <th colspan="2">合計</th>
                <td class="num">{$totalCount}枚</td>
                <td class="num">{$totalAmount|number_format}円</td>
            </tr>
        </tfoot>
    </table>

    <form action="ticketing.php" method="post" class="confirm-form">
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape}">
        {foreach from=$quantities key=code item=qty}
            <input type="hidden" name="qty_{$code|escape}" value="{$qty|intval}">
        {/foreach}

        <div class="confirm-form__buttons">
            {* 戻るボタンは index.php への単純な遷移リンク。
               入力値はサーバー側のセッションに保持済みのため復元できる *}
            <a href="index.php?keep=1" class="btn btn--secondary">戻る</a>
            <button type="submit" class="btn btn--primary">確定する</button>
        </div>
    </form>
</section>

{include file="footer.tpl"}
