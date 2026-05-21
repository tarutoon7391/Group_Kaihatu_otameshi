<?php
/**
 * スペースワールド入園券売機システム PDF出力ヘルパー
 *
 * 担当：PDF担当
 *
 * 役割：
 *   - Smarty で組み立てた HTML を PDF に変換して storage/ 配下に保存する
 *   - 発券PDF（F-005）／月次集計PDF（F-011）の両方で共有する
 *
 * PDFライブラリの方針：
 *   - 第一選択：Dompdf（composer require dompdf/dompdf）
 *   - Dompdf が無い環境では .html ファイルとして保存し、印刷時にPDF化可能な
 *     ブラウザ表示用HTMLとして提供する（学校演習環境向けフォールバック）
 */

require_once __DIR__ . '/config.php';

/** PDF（または HTML フォールバック）の保存先ディレクトリ */
const PDF_STORAGE_DIR = __DIR__ . '/storage';

/**
 * 保存先ディレクトリを必要に応じて作成する
 *
 * @return void
 */
function ensurePdfStorageDir(): void
{
    if (!is_dir(PDF_STORAGE_DIR)) {
        mkdir(PDF_STORAGE_DIR, 0755, true);
    }
}

/**
 * Smartyテンプレートを描画して PDF ファイルとして保存する
 *
 * Dompdf が利用可能なら PDF、そうでなければ印刷向けスタイル付き HTML を保存する。
 * 戻り値は実際に保存したファイルのフルパス。
 *
 * @param string               $templateName Smartyテンプレート名（例：ticket_label.tpl）
 * @param array<string, mixed> $assignments  テンプレートに割り当てる変数
 * @param string               $fileName     保存ファイル名（拡張子は .pdf を想定）
 * @param string               $orientation  'portrait' または 'landscape'
 * @return string 保存したファイルのフルパス
 */
function renderPdfFromTemplate(
    string $templateName,
    array $assignments,
    string $fileName,
    string $orientation = 'portrait'
): string {
    ensurePdfStorageDir();

    $smarty = getSmarty();
    foreach ($assignments as $key => $value) {
        $smarty->assign($key, $value);
    }
    $html = $smarty->fetch($templateName);

    $outputPath = PDF_STORAGE_DIR . '/' . $fileName;

    if (class_exists('Dompdf\\Dompdf')) {
        $dompdfClass = 'Dompdf\\Dompdf';
        $dompdf      = new $dompdfClass([
            'isRemoteEnabled' => false,
            'defaultFont'     => 'sans-serif',
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        file_put_contents($outputPath, $dompdf->output());
    } else {
        // フォールバック：HTML として保存。拡張子は .html に差し替える。
        $htmlPath = preg_replace('/\.pdf$/i', '.html', $outputPath);
        if (!is_string($htmlPath) || $htmlPath === '') {
            $htmlPath = $outputPath . '.html';
        }
        file_put_contents($htmlPath, $html);
        $outputPath = $htmlPath;
    }

    return $outputPath;
}

/**
 * 保存済み PDF/HTML をブラウザにダウンロード送出する
 *
 * @param string $filePath        サーバー上のファイルパス
 * @param string $downloadName    ダウンロードファイル名
 * @return void  本関数は exit する
 */
function sendPdfDownload(string $filePath, string $downloadName): void
{
    if (!is_file($filePath)) {
        http_response_code(404);
        echo 'ファイルが見つかりません。';
        exit;
    }

    $isPdf = (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf');
    if (!$isPdf) {
        // Dompdf 未導入時は .html として保存されているため、拡張子も合わせる
        $downloadName = preg_replace('/\.pdf$/i', '.html', $downloadName) ?: $downloadName;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html; charset=UTF-8'));
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string) filesize($filePath));
    readfile($filePath);
    exit;
}
