{*
    管理者ログイン画面テンプレート（C担当）

    PHP からアサインされる変数：
      - $errors    ... エラーメッセージ配列
      - $loginId   ... 入力済みログインID（再表示用）
      - $csrfToken ... CSRFトークン
*}
{include file="header_admin.tpl" pageTitle="ログイン"}

<section class="card card--narrow">
    <h2 class="card__title">管理者ログイン</h2>

    {if !empty($errors)}
        <ul class="error-list">
            {foreach from=$errors item=msg}
                <li class="error-list__item">{$msg|escape}</li>
            {/foreach}
        </ul>
    {/if}

    <form action="login.php" method="post" class="login-form" novalidate>
        <input type="hidden" name="csrf_token" value="{$csrfToken|escape}">

        <div class="form-row">
            <label for="login_id">ログインID</label>
            <input type="text" id="login_id" name="login_id" value="{$loginId|default:''|escape}" maxlength="50" autocomplete="username" required>
        </div>
        <div class="form-row">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn btn--primary">ログイン</button>
        </div>
    </form>
</section>

<script src="js/admin.js"></script>
{include file="footer.tpl"}
