<?php
/**
 * スペースワールド入園券売機システム DB接続・Smarty初期化
 *
 * 担当：B担当（購入処理）
 *
 * - PDO による MySQL 接続を生成し、シングルトン的に共有する
 * - Smarty テンプレートエンジンを初期化して返却する
 * - すべての画面・処理ファイルから require_once で読み込む
 *
 * Smarty 読み込み方針（優先順）：
 *   1. Composer の vendor/autoload.php があれば優先する
 *   2. サーバー共有ライブラリ /usr/local/lib/smarty4/libs/Smarty.class.php
 *   3. プロジェクト内 libs/Smarty/Smarty.class.php（手動配置用）
 *
 * コーディング規約：
 *   - 関数：キャメルケース／定数：大文字スネークケース／コメント：日本語
 */

require_once __DIR__ . '/constants.php';

// =============================================================================
// DB接続設定
// =============================================================================
/** DBホスト */
const DB_HOST    = 'localhost';
/** DB名 */
const DB_NAME    = 'se2_2025';
/** DBユーザー */
const DB_USER    = 'se2_2025';
/** DBパスワード */
const DB_PASS    = 'IshidaT';
/** DB文字コード */
const DB_CHARSET = 'utf8mb4';

// =============================================================================
// ライブラリ自動読み込み
// =============================================================================
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// サーバー共有 Smarty（inc_smarty.php と同じパス）
if (!class_exists('Smarty')) {
    $smartyServerPath = '/usr/local/lib/smarty4/libs/Smarty.class.php';
    if (is_file($smartyServerPath)) {
        require_once $smartyServerPath;
    }
}

// プロジェクト内手動配置のフォールバック
if (!class_exists('Smarty')) {
    $smartyManualPath = __DIR__ . '/libs/Smarty/Smarty.class.php';
    if (is_file($smartyManualPath)) {
        require_once $smartyManualPath;
    }
}

/**
 * PDO による DB 接続を取得する（シングルトン）
 *
 * 例外発生時はエラーメッセージを画面に表示し、処理を終了する。
 *
 * @return PDO
 */
function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // ログに残しつつ、利用者にはサニタイズしたメッセージのみ提示する
        error_log('[DB接続エラー] ' . $e->getMessage());
        http_response_code(500);
        echo 'データベースに接続できませんでした。管理者にお問い合わせください。';
        exit;
    }

    return $pdo;
}

/**
 * Smarty インスタンスを初期化して取得する
 *
 * templates_c/ は Smarty のコンパイル出力先（自動生成）。
 *
 * @return Smarty
 */
function getSmarty(): Smarty
{
    static $smarty = null;
    if ($smarty instanceof Smarty) {
        return $smarty;
    }

    if (!class_exists('Smarty')) {
        http_response_code(500);
        echo 'Smarty ライブラリが見つかりません。/usr/local/lib/smarty4/libs/ の存在を確認してください。';
        exit;
    }

    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . '/templates/');
    $smarty->setCompileDir(__DIR__ . '/templates_c/');
    $smarty->setCacheDir(__DIR__ . '/templates_c/cache/');
    $smarty->setConfigDir(__DIR__ . '/templates_c/configs/');
    // デバッグ用：開発中は強制再コンパイルしたい場合に true にする
    $smarty->force_compile = false;
    $smarty->caching       = 0;

    return $smarty;
}

/**
 * XSS 対策用の HTML エスケープを行う
 *
 * @param mixed $value
 * @return string
 */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
