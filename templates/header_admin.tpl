{*
    管理者画面共通ヘッダー（C担当）

    PHP からアサインされる変数：
      - $pageTitle  ... 画面タイトル（任意）
      - $admin      ... ログイン中の管理者情報（['login_id'=>'...']）。未ログイン画面では未定義
*}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{if !empty($pageTitle)}{$pageTitle|escape}｜{/if}スペースワールド 管理画面</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="page-admin">
<header class="admin-header">
    <h1 class="admin-header__title">スペースワールド 管理画面</h1>
    <nav class="admin-header__nav">
        {if !empty($admin)}
            <span class="admin-header__user">{$admin.login_id|escape} さん</span>
            <a class="admin-header__link" href="menu.php">メニュー</a>
            <a class="admin-header__link" href="aggregate.php">月次集計</a>
            <a class="admin-header__link admin-header__link--logout" href="logout.php">ログアウト</a>
        {/if}
    </nav>
</header>
<main class="admin-main">
