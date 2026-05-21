<?php
/**
 * S-02 購入確認画面（price_conf.php）
 *
 * 担当：画面担当（テンプレート連携）／B担当（バリデーション・確認データ生成）
 *
 * 処理フロー：
 *   1. POST で送られた qty_infant/qty_child/qty_adult を受け取る
 *   2. CSRF トークン検証
 *   3. validateQuantities() でバリデーション
 *      → NG なら $_SESSION['cart_errors'] に積んで index.php へリダイレクト
 *   4. 区分別小計・合計金額・合計枚数を計算
 *   5. セッションに保持（戻るボタンと ticketing.php からの再利用のため）
 *   6. price_conf.tpl を表示
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF トークンが無ければ発行（直接アクセス時の保護）
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// POST 以外でのアクセスは S-01 に戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF 検証
$postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
    $_SESSION['cart_errors'] = ['不正な操作が検出されました。最初からやり直してください。'];
    header('Location: index.php?reset=1');
    exit;
}

// 区分別枚数を取り出す
$quantities = [];
foreach (getAgeCategories() as $category) {
    $code = $category['code'];
    $key  = 'qty_' . $code;
    $quantities[$code] = isset($_POST[$key]) ? (string) $_POST[$key] : '0';
}

// バリデーション（A担当 constants.php の関数を利用）
$errors = validateQuantities($quantities);
if (!empty($errors)) {
    // 入力値はそのまま保持する（戻り表示のため）
    $_SESSION['cart_quantities'] = array_map('intval', $quantities);
    $_SESSION['cart_errors']     = array_values($errors);
    header('Location: index.php');
    exit;
}

// 確定値に変換してセッションに保持
$normalizedQuantities = [];
foreach (getAgeCategories() as $category) {
    $normalizedQuantities[$category['code']] = (int) $quantities[$category['code']];
}
$_SESSION['cart_quantities'] = $normalizedQuantities;

// 表示用の明細を組み立てる（0枚の区分は除外）
$lineItems = [];
foreach (getAgeCategories() as $category) {
    $qty = (int) $normalizedQuantities[$category['code']];
    if ($qty <= 0) {
        continue;
    }
    $unitPrice = (int) $category['price'];
    $lineItems[] = [
        'code'       => $category['code'],
        'name'       => $category['name'],
        'unit_price' => $unitPrice,
        'quantity'   => $qty,
        'subtotal'   => $unitPrice * $qty,
    ];
}

$totalAmount = calcTotalAmount($normalizedQuantities);
$totalCount  = calcTotalCount($normalizedQuantities);

$smarty = getSmarty();
$smarty->assign('lineItems',   $lineItems);
$smarty->assign('totalAmount', $totalAmount);
$smarty->assign('totalCount',  $totalCount);
$smarty->assign('quantities',  $normalizedQuantities);
$smarty->assign('errors',      []);
$smarty->assign('csrfToken',   $_SESSION['csrf_token']);
$smarty->display('price_conf.tpl');
