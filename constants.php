<?php
/**
 * スペースワールド入園券売機システム 共通定数定義
 *
 * 担当：A担当（計算・バリデーション）
 *
 * - 料金・年齢区分・枚数上限などのマジックナンバーを一元管理する
 * - 年齢区分判定（F-001）／合計金額計算（F-002）／枚数バリデーションの
 *   サーバー側ユーティリティ関数を提供する
 * - 画面（index.tpl）・JS（ticket.js）・購入処理（B担当）から require して使用する
 *
 * コーディング規約：
 *   - 定数：大文字スネークケース
 *   - 関数：キャメルケース
 *   - コメント：日本語
 */

// =============================================================================
// 年齢区分コード
//   DB（T-01 age_categories）の category_id と一致させる
//   画面要素の name 属性のサフィックスにも使用する（qty_infant など）
// =============================================================================
/** 区分コード：幼児（0〜3歳） */
const CATEGORY_INFANT = 'infant';
/** 区分コード：小児（4〜9歳） */
const CATEGORY_CHILD  = 'child';
/** 区分コード：一般（10歳以上） */
const CATEGORY_ADULT  = 'adult';

// =============================================================================
// 入園料金マスタ（税込・円）
//   T-01 age_categories の price 初期値と一致させる
// =============================================================================
/** 幼児単価（円） */
const PRICE_INFANT = 100;
/** 小児単価（円） */
const PRICE_CHILD  = 300;
/** 一般単価（円） */
const PRICE_ADULT  = 500;

// =============================================================================
// 年齢区分判定の境界値（満年齢・歳）
// =============================================================================
/** 幼児区分の下限年齢 */
const AGE_MIN_INFANT = 0;
/** 幼児区分の上限年齢 */
const AGE_MAX_INFANT = 3;
/** 小児区分の下限年齢 */
const AGE_MIN_CHILD  = 4;
/** 小児区分の上限年齢 */
const AGE_MAX_CHILD  = 9;
/** 一般区分の下限年齢（上限なし） */
const AGE_MIN_ADULT  = 10;

// =============================================================================
// 枚数バリデーション
// =============================================================================
/** 各区分あたりの最小枚数 */
const MIN_QUANTITY = 0;
/** 各区分あたりの最大枚数 */
const MAX_QUANTITY = 99;
/** 購入に必要な合計枚数の最小値 */
const MIN_TOTAL_QUANTITY = 1;

// =============================================================================
// チケット有効期限
//   F-006 券面情報表示・購入処理フロー（T-02 expiration_date）で使用
// =============================================================================
/** 入園券の有効日数（購入日を含む） */
const TICKET_VALID_DAYS = 1;

// =============================================================================
// エラーメッセージ
//   バリデーション一覧の文言と一致させる
// =============================================================================
/** 区分ごとの枚数が範囲外の場合のメッセージ */
const MSG_ERROR_QUANTITY_RANGE = '枚数は0〜99で入力してください';
/** 合計枚数が0の場合のメッセージ */
const MSG_ERROR_TOTAL_QUANTITY = '1枚以上選択してください';
/** 集計年月が不正な場合のメッセージ */
const MSG_ERROR_TARGET_MONTH   = '正しい年月を選択してください';

// =============================================================================
// 区分マスタ（コード・表示名・単価・年齢範囲をひとまとめにした配列）
//   画面・PDF・集計処理から参照することで、定義の重複を避ける
// =============================================================================
/**
 * 全年齢区分の定義を取得する
 *
 * 返却配列の各要素：
 *   code        … 区分コード（CATEGORY_*）
 *   category_id … DB 上の category_id
 *   name        … 表示名（例：幼児）
 *   price       … 単価（円）
 *   min_age     … 下限年齢
 *   max_age     … 上限年齢（null は上限なし）
 *
 * @return array<int, array<string, mixed>>
 */
function getAgeCategories(): array
{
    return [
        [
            'code'        => CATEGORY_INFANT,
            'category_id' => 1,
            'name'        => '幼児',
            'price'       => PRICE_INFANT,
            'min_age'     => AGE_MIN_INFANT,
            'max_age'     => AGE_MAX_INFANT,
        ],
        [
            'code'        => CATEGORY_CHILD,
            'category_id' => 2,
            'name'        => '小児',
            'price'       => PRICE_CHILD,
            'min_age'     => AGE_MIN_CHILD,
            'max_age'     => AGE_MAX_CHILD,
        ],
        [
            'code'        => CATEGORY_ADULT,
            'category_id' => 3,
            'name'        => '一般',
            'price'       => PRICE_ADULT,
            'min_age'     => AGE_MIN_ADULT,
            'max_age'     => null,
        ],
    ];
}

/**
 * 年齢から年齢区分コードを判定する（F-001 年齢区分判定）
 *
 * 入園日時点の満年齢を渡すこと。
 * 0歳未満や数値以外が渡された場合は null を返す。
 *
 * @param int $age 満年齢
 * @return string|null CATEGORY_INFANT / CATEGORY_CHILD / CATEGORY_ADULT、判定不可は null
 */
function judgeAgeCategory(int $age): ?string
{
    if ($age < AGE_MIN_INFANT) {
        return null;
    }
    foreach (getAgeCategories() as $category) {
        $min = $category['min_age'];
        $max = $category['max_age'];
        if ($age >= $min && ($max === null || $age <= $max)) {
            return $category['code'];
        }
    }
    return null;
}

/**
 * 年齢から該当区分の単価を取得する（F-001 → F-002 への橋渡し）
 *
 * @param int $age 満年齢
 * @return int|null 単価（円）、判定不可は null
 */
function getPriceByAge(int $age): ?int
{
    $code = judgeAgeCategory($age);
    if ($code === null) {
        return null;
    }
    foreach (getAgeCategories() as $category) {
        if ($category['code'] === $code) {
            return $category['price'];
        }
    }
    return null;
}

/**
 * 区分コードから単価を取得する
 *
 * @param string $code 区分コード（CATEGORY_*）
 * @return int|null 単価（円）、未定義コードは null
 */
function getPriceByCategory(string $code): ?int
{
    foreach (getAgeCategories() as $category) {
        if ($category['code'] === $code) {
            return $category['price'];
        }
    }
    return null;
}

/**
 * 区分別枚数から合計金額を計算する（F-002 金額自動計算）
 *
 * 引数の連想配列キーは区分コード（CATEGORY_*）。未指定キーは0枚として扱う。
 *
 * @param array<string, int> $quantities ['infant' => 1, 'child' => 0, 'adult' => 2] など
 * @return int 合計金額（円）
 */
function calcTotalAmount(array $quantities): int
{
    $total = 0;
    foreach (getAgeCategories() as $category) {
        $code     = $category['code'];
        $quantity = isset($quantities[$code]) ? (int) $quantities[$code] : 0;
        $total   += $category['price'] * $quantity;
    }
    return $total;
}

/**
 * 区分別枚数から合計枚数を計算する
 *
 * @param array<string, int> $quantities
 * @return int 合計枚数
 */
function calcTotalCount(array $quantities): int
{
    $total = 0;
    foreach ($quantities as $quantity) {
        $total += (int) $quantity;
    }
    return $total;
}

/**
 * 1区分の枚数が許容範囲内かを判定する
 *
 * 整数かつ MIN_QUANTITY 以上 MAX_QUANTITY 以下のとき true。
 * 数値文字列も許容する（フォーム入力からの受け取りを想定）。
 *
 * @param mixed $quantity 枚数（intまたは数値文字列）
 * @return bool 範囲内なら true
 */
function isValidQuantity($quantity): bool
{
    if (!is_numeric($quantity)) {
        return false;
    }
    // 小数・全角数字・符号付き数値を弾くため、非負整数文字列のみを許可する
    if (!preg_match('/\A\d+\z/', (string) $quantity)) {
        return false;
    }
    $value = (int) $quantity;
    return $value >= MIN_QUANTITY && $value <= MAX_QUANTITY;
}

/**
 * 区分別枚数全体のバリデーションを行う
 *
 * 各区分が isValidQuantity を満たし、かつ合計枚数が MIN_TOTAL_QUANTITY 以上の場合に
 * 空配列を返す。エラーがある場合は ['quantity' => MSG_..., 'total' => MSG_...] を返す。
 *
 * @param array<string, mixed> $quantities
 * @return array<string, string> エラーメッセージの配列（空配列ならバリデーションOK）
 */
function validateQuantities(array $quantities): array
{
    $errors = [];

    foreach (getAgeCategories() as $category) {
        $code  = $category['code'];
        $value = $quantities[$code] ?? 0;
        if (!isValidQuantity($value)) {
            $errors['quantity'] = MSG_ERROR_QUANTITY_RANGE;
            break;
        }
    }

    if (!isset($errors['quantity'])) {
        $totalCount = 0;
        foreach (getAgeCategories() as $category) {
            $totalCount += (int) ($quantities[$category['code']] ?? 0);
        }
        if ($totalCount < MIN_TOTAL_QUANTITY) {
            $errors['total'] = MSG_ERROR_TOTAL_QUANTITY;
        }
    }

    return $errors;
}
