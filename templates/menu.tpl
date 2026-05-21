{*
    管理者メニューテンプレート（C担当）

    PHP からアサインされる変数：
      - $admin   ... ログイン中の管理者情報
*}
{include file="header_admin.tpl" pageTitle="管理者メニュー"}

<section class="card">
    <h2 class="card__title">管理者メニュー</h2>
    <p class="menu-lead">利用したい機能を選択してください。</p>

    <ul class="menu-list">
        <li class="menu-list__item">
            <a class="menu-list__link" href="aggregate.php">
                <span class="menu-list__label">月次集計</span>
                <span class="menu-list__desc">対象年月を指定して入園実績を集計・PDF出力します。</span>
            </a>
        </li>
        <li class="menu-list__item">
            <a class="menu-list__link" href="logout.php">
                <span class="menu-list__label">ログアウト</span>
                <span class="menu-list__desc">管理画面からログアウトします。</span>
            </a>
        </li>
    </ul>
</section>

<script src="js/admin.js"></script>
{include file="footer.tpl"}
