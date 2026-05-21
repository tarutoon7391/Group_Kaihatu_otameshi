<?php
/**
 * 管理者メニュー画面（C担当）
 *
 * F-017 権限制御：ログイン中の管理者のみ表示可能。
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireAdminLogin();

$smarty = getSmarty();
$smarty->assign('admin', getLoginAdmin());
$smarty->display('menu.tpl');
