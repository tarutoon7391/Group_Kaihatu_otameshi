<?php
/**
 * S-04 月次集計画面（aggregate.php）
 *
 * 担当：C担当
 *
 * 処理：
 *   - GET ：対象年月プルダウン付きの集計画面を表示する（デフォルト：前月）
 *   - POST：対象年月のバリデーション → DBから集計 → A4縦PDFを生成しダウンロード送出
 *
 * 月次集計処理フロー（設計書準拠）：
 *   1. 画面から対象年月を受け取る
 *   2. バリデーション（YYYY-MM形式・未来月でない）
 *   3. 対象月の purchased_at で DB から取得
 *   4. 区分ごとに合計枚数・合計金額を集計
 *      0件 → 「対象月のデータがありません」を表示
 *   5. A4縦PDFを出力してダウンロード
 *
 * F-017 権限制御：管理者ログイン必須。
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/pdf_renderer.php';

requireAdminLogin();

/**
 * 月選択肢を生成する（過去 24 ヶ月＋前月をデフォルトに）
 *
 * @return array<int, array{value:string,label:string}>
 */
function buildMonthOptions(): array
{
    $options = [];
    $base    = new DateTimeImmutable('first day of last month');
    // 過去 24 ヶ月分を選択肢として用意する
    for ($i = 0; $i < 24; $i++) {
        $month = $base->modify('-' . $i . ' month');
        $options[] = [
            'value' => $month->format('Y-m'),
            'label' => $month->format('Y年') . (int) $month->format('m') . '月',
        ];
    }
    return $options;
}

/**
 * 'YYYY-MM' 文字列を検証する。妥当なら DateTimeImmutable（その月の1日）を返す。
 *
 * @param string $targetMonth
 * @return DateTimeImmutable|null  不正な場合は null
 */
function parseTargetMonth(string $targetMonth): ?DateTimeImmutable
{
    if (!preg_match('/\A(\d{4})-(\d{2})\z/', $targetMonth, $m)) {
        return null;
    }
    $year  = (int) $m[1];
    $month = (int) $m[2];
    if ($month < 1 || $month > 12) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-01', $year, $month));
    if (!$date instanceof DateTimeImmutable) {
        return null;
    }
    // 未来月は不可（当月以降は不可とする＝過去月のみ）
    $thisMonth = new DateTimeImmutable('first day of this month 00:00:00');
    if ($date >= $thisMonth) {
        return null;
    }
    return $date;
}

/**
 * 指定月の購入実績を年齢区分別に集計する
 *
 * @param PDO               $pdo
 * @param DateTimeImmutable $monthStart 対象月の1日 00:00:00
 * @return array{rows:array<int,array<string,mixed>>,totalCount:int,totalAmount:int}
 */
function aggregateMonthly(PDO $pdo, DateTimeImmutable $monthStart): array
{
    $start = $monthStart->format('Y-m-d 00:00:00');
    $end   = $monthStart->modify('first day of next month')->format('Y-m-d 00:00:00');

    $sql = '
        SELECT
            ac.category_id,
            ac.category_name,
            ac.min_age,
            ac.max_age,
            ac.price            AS unit_price,
            COALESCE(SUM(pd.quantity), 0) AS total_count,
            COALESCE(SUM(pd.subtotal), 0) AS total_subtotal
        FROM age_categories ac
        LEFT JOIN purchase_details pd
               ON pd.category_id = ac.category_id
        LEFT JOIN purchases p
               ON p.purchase_id  = pd.purchase_id
              AND p.purchased_at >= :start
              AND p.purchased_at <  :end
        GROUP BY ac.category_id, ac.category_name, ac.min_age, ac.max_age, ac.price
        ORDER BY ac.category_id
    ';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $raw = $stmt->fetchAll();

    $rows        = [];
    $totalCount  = 0;
    $totalAmount = 0;
    foreach ($raw as $r) {
        $count    = (int) $r['total_count'];
        $subtotal = (int) $r['total_subtotal'];
        $ageRange = ($r['max_age'] === null)
            ? sprintf('%d歳以上', (int) $r['min_age'])
            : sprintf('%d〜%d歳', (int) $r['min_age'], (int) $r['max_age']);
        $rows[] = [
            'category_id' => (int) $r['category_id'],
            'name'        => (string) $r['category_name'],
            'age_range'   => $ageRange,
            'unit_price'  => (int) $r['unit_price'],
            'count'       => $count,
            'subtotal'    => $subtotal,
        ];
        $totalCount  += $count;
        $totalAmount += $subtotal;
    }

    return [
        'rows'        => $rows,
        'totalCount'  => $totalCount,
        'totalAmount' => $totalAmount,
    ];
}

// -----------------------------------------------------------------------------
// メイン処理
// -----------------------------------------------------------------------------
$errors        = [];
$info          = '';
$monthOptions  = buildMonthOptions();
$selectedMonth = !empty($monthOptions) ? $monthOptions[0]['value'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($postedToken)) {
        $errors[] = '不正な操作が検出されました。再度お試しください。';
    } else {
        $selectedMonth = isset($_POST['target_month']) ? (string) $_POST['target_month'] : '';
        $monthDate     = parseTargetMonth($selectedMonth);
        if ($monthDate === null) {
            $errors[] = MSG_ERROR_TARGET_MONTH;
        } else {
            try {
                $pdo       = getDbConnection();
                $aggregate = aggregateMonthly($pdo, $monthDate);

                if ($aggregate['totalCount'] === 0) {
                    $info = '対象月のデータがありません。';
                } else {
                    // PDF 生成
                    $fileName = sprintf('monthly_report_%s.pdf', $monthDate->format('Ym'));
                    $filePath = renderPdfFromTemplate(
                        'report_pdf.tpl',
                        [
                            'facilityName' => 'スペースワールド',
                            'reportTitle'  => '月次入園集計レポート',
                            'targetYear'   => (int) $monthDate->format('Y'),
                            'targetMonth'  => (int) $monthDate->format('m'),
                            'generatedAt'  => (new DateTimeImmutable('now'))->format('Y/m/d H:i'),
                            'rows'         => $aggregate['rows'],
                            'totalCount'   => $aggregate['totalCount'],
                            'totalAmount'  => $aggregate['totalAmount'],
                        ],
                        $fileName,
                        'portrait'
                    );

                    sendPdfDownload($filePath, $fileName);
                    // sendPdfDownload 内で exit
                }
            } catch (PDOException $e) {
                error_log('[月次集計DBエラー] ' . $e->getMessage());
                $errors[] = '集計処理中にエラーが発生しました。';
            } catch (Throwable $e) {
                error_log('[月次集計PDFエラー] ' . $e->getMessage());
                $errors[] = 'PDF出力中にエラーが発生しました。';
            }
        }
    }
}

$smarty = getSmarty();
$smarty->assign('admin',         getLoginAdmin());
$smarty->assign('monthOptions',  $monthOptions);
$smarty->assign('selectedMonth', $selectedMonth);
$smarty->assign('errors',        $errors);
$smarty->assign('info',          $info);
$smarty->assign('csrfToken',     issueCsrfToken());
$smarty->display('aggregate.tpl');
