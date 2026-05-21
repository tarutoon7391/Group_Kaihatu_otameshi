<?php
/**
 * S-01 初期・券種選択画面（index.php）
 *
 * 担当：画面担当（テンプレート連携）／B担当（セッションでの入力値保持）
 *
 * 役割：
 *   - 区分別枚数入力フォームを表示する
 *   - GET ?reset=1 でフォーム内容を初期化する（S-03「最初に戻る」用）
 *   - GET ?keep=1 でセッションに保持された前回入力値を表示する（S-02「戻る」用）
 *   - 実際の購入処理は price_conf.php / ticketing.php が担当する
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF トークンを発行（auth.php とは独立にトークンを管理）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// reset 指定時はセッション上の入力値を全クリアする
if (isset($_GET['reset'])) {
    unset($_SESSION['cart_quantities'], $_SESSION['cart_errors']);
}

// 戻る (keep) 時または通常表示時に保持枚数を取り出す
$quantities = [];
foreach (getAgeCategories() as $category) {
    $quantities[$category['code']] = 0;
}
if (isset($_SESSION['cart_quantities']) && is_array($_SESSION['cart_quantities'])) {
    foreach ($_SESSION['cart_quantities'] as $code => $value) {
        if (array_key_exists($code, $quantities)) {
            $quantities[$code] = (int) $value;
        }
    }
}

// バリデーションエラー（price_conf.php からのリダイレクト時に表示）
$errors = [];
if (!empty($_SESSION['cart_errors']) && is_array($_SESSION['cart_errors'])) {
    $errors = $_SESSION['cart_errors'];
    unset($_SESSION['cart_errors']);
}

$smarty = getSmarty();
$smarty->assign('categories', getAgeCategories());
$smarty->assign('quantities', $quantities);
$smarty->assign('errors', $errors);
$smarty->assign('csrfToken', $_SESSION['csrf_token']);
$smarty->display('index.tpl');
