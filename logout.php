<?php
/**
 * 管理者ログアウト処理（C担当）
 *
 * セッションを破棄して login.php へリダイレクトする（F-015）。
 */

require_once __DIR__ . '/auth.php';

// セッション変数を全消去
$_SESSION = [];

// セッションクッキーも削除
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: login.php');
exit;
