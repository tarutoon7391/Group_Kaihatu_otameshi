{*
    共通ヘッダーテンプレート（画面担当）
    - 一般利用者向け画面（S-01〜S-03）の先頭で {include} される
    - $pageTitle がアサインされていればタイトルに利用
*}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{if !empty($pageTitle)}{$pageTitle|escape}｜{/if}スペースワールド 入園券売機</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-public">
<header class="site-header">
    <h1 class="site-header__title">スペースワールド 入園券売機</h1>
    {if !empty($pageTitle)}
        <p class="site-header__subtitle">{$pageTitle|escape}</p>
    {/if}
</header>
<main class="site-main">
