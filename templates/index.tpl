{*
    S-01 初期・券種選択画面テンプレート（画面担当）

    画面契約（A担当 ticket.js と共有）：
      - 区分別枚数入力：<input class="js-qty" data-category="..." data-unit-price="...">
      - 合計金額表示  ：id="total_amount"
      - 合計枚数表示  ：id="total_count"
      - 購入ボタン    ：id="purchase_button"
      - リセットボタン：id="reset_button"

    PHP 側からは以下をアサインする：
      - $categories     ... getAgeCategories() の戻り値
      - $quantities     ... 区分コード → 入力済み枚数（戻る時の保持に使用）
      - $errors         ... バリデーションエラーメッセージ配列
      - $csrfToken      ... CSRFトークン
*}
{include file="header.tpl" pageTitle="券種選択"}

<section class="card">
    <h2 class="card__title">ご利用人数を入力してください</h2>

    {if !empty($errors)}
        <ul class="error-list">
            {foreach from=$errors item=msg}
                <li class="error-list__item">{$msg|escape}</li>
            {/foreach}
        </ul>
    {/if}

    <form action="price_conf.php" method="post" class="ticket-form" novalidate>
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape}">

        <table class="ticket-form__table">
            <thead>
                <tr>
                    <th>区分</th>
                    <th>対象年齢</th>
                    <th>単価</th>
                    <th>枚数</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$categories item=cat}
                    {assign var="qty" value=$quantities[$cat.code]|default:0}
                    <tr>
                        <td>{$cat.name|escape}</td>
                        <td>
                            {if $cat.max_age === null}
                                {$cat.min_age}歳以上
                            {else}
                                {$cat.min_age}〜{$cat.max_age}歳
                            {/if}
                        </td>
                        <td class="ticket-form__price">{$cat.price|number_format}円</td>
                        <td>
                            <input
                                type="number"
                                class="js-qty"
                                name="qty_{$cat.code|escape}"
                                id="qty_{$cat.code|escape}"
                                data-category="{$cat.code|escape}"
                                data-unit-price="{$cat.price}"
                                value="{$qty}"
                                min="0"
                                max="99"
                                step="1"
                                inputmode="numeric"
                            >
                            <span class="ticket-form__unit">枚</span>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <dl class="ticket-form__summary">
            <dt>合計枚数</dt>
            <dd><span id="total_count">0枚</span></dd>
            <dt>合計金額</dt>
            <dd><span id="total_amount">0円</span></dd>
        </dl>

        <div class="ticket-form__buttons">
            <button type="button" id="reset_button" class="btn btn--secondary">リセット</button>
            <button type="submit" id="purchase_button" class="btn btn--primary" disabled>購入する</button>
        </div>
    </form>
</section>

<script src="js/ticket.js"></script>

{include file="footer.tpl"}
