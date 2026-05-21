<?php
/**
 * S-03 購入完了画面（ticketing.php）
 *
 * 担当：画面担当（テンプレート連携）／B担当（DB保存・PDF生成）
 *
 * 処理フロー（購入確定時）：
 *   1. POST + CSRF 検証
 *   2. セッションに保持された枚数情報を再バリデーション
 *   3. T-02 purchases に INSERT
 *   4. T-03 purchase_details に INSERT（枚数1以上の区分のみ）
 *      - 失敗時はロールバックしてエラーメッセージ表示
 *   5. F-005 入園券 PDF を storage/ticket_{purchase_id}.pdf として生成
 *   6. ticketing.tpl で完了画面表示（PDFダウンロードリンク付き）
 *
 * GET ?download=1&purchase_id=NN&token=XX
 *   セッションに保持された購入トークンと一致する場合のみ PDF を送出する
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/pdf_renderer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 区分コードから category_id（T-01 age_categories の PK）を返す
 *
 * @param string $code
 * @return int|null
 */
function findCategoryIdByCode(string $code): ?int
{
    foreach (getAgeCategories() as $category) {
        if ($category['code'] === $code) {
            return (int) $category['category_id'];
        }
    }
    return null;
}

/**
 * 入園券PDFを生成し、生成ファイルパスを返す（F-005／F-006／F-007）
 *
 * @param int                                                       $purchaseId
 * @param DateTimeImmutable                                         $purchasedAt
 * @param DateTimeImmutable                                         $expirationDate
 * @param array<int, array{name:string,unit_price:int,quantity:int,subtotal:int}> $lineItems
 * @param int                                                       $totalAmount
 * @param int                                                       $totalCount
 * @return string ファイルパス
 */
function generateTicketPdf(
    int $purchaseId,
    DateTimeImmutable $purchasedAt,
    DateTimeImmutable $expirationDate,
    array $lineItems,
    int $totalAmount,
    int $totalCount
): string {
    $fileName = sprintf('ticket_%d.pdf', $purchaseId);
    return renderPdfFromTemplate(
        'ticket_label.tpl',
        [
            'purchaseId'     => $purchaseId,
            'purchasedAt'    => $purchasedAt->format('Y/m/d H:i'),
            'expirationDate' => $expirationDate->format('Y/m/d'),
            'lineItems'      => $lineItems,
            'totalAmount'    => $totalAmount,
            'totalCount'     => $totalCount,
        ],
        $fileName,
        'portrait'
    );
}

// -----------------------------------------------------------------------------
// PDF ダウンロード要求の処理（GET）
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['download'])) {
    $requestedId = isset($_GET['purchase_id']) ? (int) $_GET['purchase_id'] : 0;
    $requestedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';
    $sessionId    = isset($_SESSION['last_purchase_id']) ? (int) $_SESSION['last_purchase_id'] : 0;
    $sessionToken = isset($_SESSION['last_purchase_token']) ? (string) $_SESSION['last_purchase_token'] : '';

    if ($requestedId <= 0 || $requestedId !== $sessionId || $sessionToken === '' || !hash_equals($sessionToken, $requestedToken)) {
        http_response_code(403);
        echo 'このチケットをダウンロードする権限がありません。';
        exit;
    }

    // 既に保存済みであれば再生成しない（Dompdf 有無で拡張子が変わる点を吸収）
    $pdfPath  = PDF_STORAGE_DIR . '/' . sprintf('ticket_%d.pdf', $requestedId);
    $htmlPath = PDF_STORAGE_DIR . '/' . sprintf('ticket_%d.html', $requestedId);
    $existingPath = is_file($pdfPath) ? $pdfPath : (is_file($htmlPath) ? $htmlPath : '');

    if ($existingPath === '') {
        http_response_code(404);
        echo 'チケットファイルが見つかりません。';
        exit;
    }

    sendPdfDownload($existingPath, sprintf('ticket_%d.pdf', $requestedId));
}

// -----------------------------------------------------------------------------
// 購入確定処理（POST）
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF 検証
$postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $postedToken)) {
    $_SESSION['cart_errors'] = ['不正な操作が検出されました。最初からやり直してください。'];
    header('Location: index.php?reset=1');
    exit;
}

// 枚数を再取得（POST 優先、無ければセッション保持値）
$quantities = [];
foreach (getAgeCategories() as $category) {
    $code = $category['code'];
    $key  = 'qty_' . $code;
    if (isset($_POST[$key])) {
        $quantities[$code] = (string) $_POST[$key];
    } elseif (isset($_SESSION['cart_quantities'][$code])) {
        $quantities[$code] = (string) $_SESSION['cart_quantities'][$code];
    } else {
        $quantities[$code] = '0';
    }
}

// 再バリデーション
$errors = validateQuantities($quantities);
if (!empty($errors)) {
    $_SESSION['cart_quantities'] = array_map('intval', $quantities);
    $_SESSION['cart_errors']     = array_values($errors);
    header('Location: index.php');
    exit;
}

$normalizedQuantities = [];
foreach (getAgeCategories() as $category) {
    $normalizedQuantities[$category['code']] = (int) $quantities[$category['code']];
}

$totalAmount = calcTotalAmount($normalizedQuantities);
$totalCount  = calcTotalCount($normalizedQuantities);

// 明細の組み立て
$lineItems = [];
foreach (getAgeCategories() as $category) {
    $qty = (int) $normalizedQuantities[$category['code']];
    if ($qty <= 0) {
        continue;
    }
    $unitPrice = (int) $category['price'];
    $lineItems[] = [
        'category_id' => (int) $category['category_id'],
        'code'        => $category['code'],
        'name'        => $category['name'],
        'unit_price'  => $unitPrice,
        'quantity'    => $qty,
        'subtotal'    => $unitPrice * $qty,
    ];
}

// 購入日時・有効期限
$purchasedAt    = new DateTimeImmutable('now');
$expirationDate = $purchasedAt->modify('+' . (TICKET_VALID_DAYS - 1) . ' day');

// DB 保存（T-02 + T-03 をトランザクションで）
$pdo = getDbConnection();
$saveErrors = [];
$purchaseId = 0;
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO purchases (purchased_at, total_amount, total_count, paid_amount, expiration_date)
         VALUES (:purchased_at, :total_amount, :total_count, :paid_amount, :expiration_date)'
    );
    $stmt->execute([
        ':purchased_at'    => $purchasedAt->format('Y-m-d H:i:s'),
        ':total_amount'    => $totalAmount,
        ':total_count'     => $totalCount,
        ':paid_amount'     => null, // No.10 未確定のため NULL 固定
        ':expiration_date' => $expirationDate->format('Y-m-d'),
    ]);
    $purchaseId = (int) $pdo->lastInsertId();

    $detailStmt = $pdo->prepare(
        'INSERT INTO purchase_details (purchase_id, category_id, quantity, unit_price, subtotal)
         VALUES (:purchase_id, :category_id, :quantity, :unit_price, :subtotal)'
    );
    foreach ($lineItems as $item) {
        $detailStmt->execute([
            ':purchase_id' => $purchaseId,
            ':category_id' => $item['category_id'],
            ':quantity'    => $item['quantity'],
            ':unit_price'  => $item['unit_price'],
            ':subtotal'    => $item['subtotal'],
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[購入DB保存エラー] ' . $e->getMessage());
    $saveErrors[] = '購入情報の保存に失敗しました。時間を置いて再度お試しください。';
}

if (!empty($saveErrors)) {
    // 再表示は確認画面のテンプレートで（戻り操作させたいため）
    $smarty = getSmarty();
    $smarty->assign('lineItems',   $lineItems);
    $smarty->assign('totalAmount', $totalAmount);
    $smarty->assign('totalCount',  $totalCount);
    $smarty->assign('quantities',  $normalizedQuantities);
    $smarty->assign('errors',      $saveErrors);
    $smarty->assign('csrfToken',   $_SESSION['csrf_token']);
    $smarty->display('price_conf.tpl');
    exit;
}

// PDF 生成（失敗してもチケット情報は表示できるよう例外を握る）
try {
    generateTicketPdf($purchaseId, $purchasedAt, $expirationDate, $lineItems, $totalAmount, $totalCount);
} catch (Throwable $e) {
    error_log('[PDF生成エラー] ' . $e->getMessage());
}

// ダウンロード用ワンタイムトークンを発行
$downloadToken = bin2hex(random_bytes(16));
$_SESSION['last_purchase_id']    = $purchaseId;
$_SESSION['last_purchase_token'] = $downloadToken;

// 購入完了表示（カート情報はリセット）
unset($_SESSION['cart_quantities']);

$pdfDownloadUrl = sprintf(
    'ticketing.php?download=1&purchase_id=%d&token=%s',
    $purchaseId,
    $downloadToken
);

$smarty = getSmarty();
$smarty->assign('purchaseId',     $purchaseId);
$smarty->assign('totalAmount',    $totalAmount);
$smarty->assign('totalCount',     $totalCount);
$smarty->assign('pdfDownloadUrl', $pdfDownloadUrl);
$smarty->display('ticketing.tpl');
