<?php
/**
 * 管理者ログイン処理（C担当）
 *
 * 処理：
 *   - GET ：login.tpl を表示
 *   - POST：login_id / password を検証し、成功時は menu.php へリダイレクト
 *
 * 認証仕様：
 *   - admins テーブル（T-04）の password は password_hash でハッシュ化済み
 *   - 認証成功時に session_regenerate_id() でセッション固定化対策を行う
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 既ログインならメニューへ
if (isAdminLoggedIn()) {
    header('Location: menu.php');
    exit;
}

$errors  = [];
$loginId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($postedToken)) {
        $errors[] = '不正な操作が検出されました。再度お試しください。';
    } else {
        $loginId  = isset($_POST['login_id']) ? trim((string) $_POST['login_id']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        if ($loginId === '' || $password === '') {
            $errors[] = 'ログインIDとパスワードを入力してください。';
        } elseif (mb_strlen($loginId) > 50) {
            $errors[] = 'ログインIDは50文字以内で入力してください。';
        } else {
            try {
                $pdo  = getDbConnection();
                $stmt = $pdo->prepare('SELECT admin_id, login_id, password FROM admins WHERE login_id = :login_id LIMIT 1');
                $stmt->execute([':login_id' => $loginId]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    // セッション固定化対策
                    session_regenerate_id(true);
                    $_SESSION['admin_id']       = (int) $admin['admin_id'];
                    $_SESSION['admin_login_id'] = (string) $admin['login_id'];

                    header('Location: menu.php');
                    exit;
                }

                $errors[] = 'ログインIDまたはパスワードが正しくありません。';
            } catch (PDOException $e) {
                error_log('[ログインDBエラー] ' . $e->getMessage());
                $errors[] = '認証処理中にエラーが発生しました。時間を置いて再度お試しください。';
            }
        }
    }
}

$smarty = getSmarty();
$smarty->assign('errors',    $errors);
$smarty->assign('loginId',   $loginId);
$smarty->assign('csrfToken', issueCsrfToken());
$smarty->display('login.tpl');
