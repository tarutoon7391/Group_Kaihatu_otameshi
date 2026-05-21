{*
    月次集計画面テンプレート（C担当・S-04）

    PHP からアサインされる変数：
      - $admin           ... ログイン中の管理者情報
      - $monthOptions    ... [['value'=>'2025-04','label'=>'2025年4月'], ...]
      - $selectedMonth   ... 'YYYY-MM' 形式の選択中値（デフォルト：前月）
      - $errors          ... バリデーションエラー
      - $info            ... 「対象月のデータがありません」等の情報メッセージ
      - $csrfToken       ... CSRFトークン
*}
{include file="header_admin.tpl" pageTitle="月次集計"}

<section class="card">
    <h2 class="card__title">月次集計（PDF出力）</h2>

    {if !empty($errors)}
        <ul class="error-list">
            {foreach from=$errors item=msg}
                <li class="error-list__item">{$msg|escape}</li>
            {/foreach}
        </ul>
    {/if}

    {if !empty($info)}
        <p class="info-message">{$info|escape}</p>
    {/if}

    <form action="aggregate.php" method="post" class="aggregate-form" novalidate>
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape}">

        <div class="form-row">
            <label for="target_month">対象年月</label>
            <select id="target_month" name="target_month" required>
                {foreach from=$monthOptions item=opt}
                    <option value="{$opt.value|escape}" {if $opt.value === $selectedMonth}selected{/if}>
                        {$opt.label|escape}
                    </option>
                {/foreach}
            </select>
        </div>

        <div class="form-buttons">
            <button type="submit" name="action" value="download" class="btn btn--primary">集計PDFを出力</button>
        </div>
    </form>
</section>

<script src="js/admin.js"></script>
{include file="footer.tpl"}
