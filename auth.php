<?php
/**
 * スペースワールド入園券売機システム セッション認証チェック
 *
 * 担当：C担当（月次集計・管理者ログイン）
 *
 * 役割：
 *   - 管理者専用画面（menu.php / aggregate.php / logout.php 等）の先頭で
 *     require_once され、未ログインなら login.php へ強制リダイレクトする（F-017）。
 *   - セッション開始・CSRF トークン発行などの共通処理も担う。
 *
 * 確定済み事項（No.12〜15）：
 *   - 管理者ログイン：必要
 *   - 管理者アカウント数：複数対応（admins テーブル）
 *   - パスワード変更機能：不要
 *   - 自動ログアウト：なし（手動ログアウトのみ）→ セッションタイムアウトは行わない
 */

// 全管理者画面で同じセッション設定にする
if (session_status() === PHP_SESSION_NONE) {
    // セッションクッキーをサーバー側のみ参照可能にする
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/**
 * 管理者ログイン済みかを判定する
 *
 * @return bool ログイン済みなら true
 */
function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_login_id']);
}

/**
 * 認証必須画面の入口で呼ぶアクセス制御処理
 *
 * 未ログインなら login.php へリダイレクトして処理を終了する。
 *
 * @return void
 */
function requireAdminLogin(): void
{
    if (isAdminLoggedIn()) {
        return;
    }
    header('Location: login.php');
    exit;
}

/**
 * CSRF トークンを発行（無ければ生成）して返す
 *
 * @return string 64文字のトークン文字列
 */
function issueCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 受け取った CSRF トークンがセッションのものと一致するか検証する
 *
 * @param string|null $token POST 等で受け取ったトークン
 * @return bool 一致すれば true
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ログイン中の管理者情報をセッションから取得する
 *
 * @return array<string, mixed>|null 未ログインなら null
 */
function getLoginAdmin(): ?array
{
    if (!isAdminLoggedIn()) {
        return null;
    }
    return [
        'admin_id' => (int) $_SESSION['admin_id'],
        'login_id' => (string) $_SESSION['admin_login_id'],
    ];
}
