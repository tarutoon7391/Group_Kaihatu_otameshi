/**
 * スペースワールド入園券売機システム
 * S-01（初期・券種選択画面）用 料金リアルタイム計算スクリプト
 *
 * 担当：A担当（計算・バリデーション）
 *
 * 機能：
 *   - 区分別枚数入力からリアルタイムに合計金額を表示する（F-002 金額自動計算）
 *   - 各区分の枚数を 0〜MAX_QUANTITY の範囲に強制する（バリデーション）
 *   - 合計枚数が1枚以上のときのみ購入ボタンを活性化する
 *   - リセットボタン押下で全枚数を 0 に戻す
 *
 * DOM 要素の規約（画面担当・B担当と共有）：
 *   - 区分別枚数入力 ... <input type="number" name="qty_infant|qty_child|qty_adult" class="js-qty">
 *       data-category 属性に区分コード（infant|child|adult）、
 *       data-unit-price 属性に単価（円）を持たせる
 *   - 合計金額表示 ... id="total_amount"（テキストノードを書き換える）
 *   - 合計枚数表示 ... id="total_count"（任意・存在しない場合はスキップ）
 *   - 購入ボタン ... id="purchase_button"
 *   - リセットボタン ... id="reset_button"
 *
 * コーディング規約：
 *   - 変数名・関数名：キャメルケース
 *   - 定数：大文字スネークケース
 *   - コメント：日本語
 */

(function () {
    'use strict';

    // 枚数バリデーションの境界値（constants.php と一致させる）
    var MIN_QUANTITY       = 0;
    var MAX_QUANTITY       = 99;
    var MIN_TOTAL_QUANTITY = 1;

    // DOM 要素のセレクタ
    var SELECTOR_QTY            = '.js-qty';
    var SELECTOR_TOTAL_AMOUNT   = '#total_amount';
    var SELECTOR_TOTAL_COUNT    = '#total_count';
    var SELECTOR_PURCHASE_BTN   = '#purchase_button';
    var SELECTOR_RESET_BTN      = '#reset_button';

    /**
     * 数値文字列を整数化し、許容範囲に収めて返す
     * 不正値（空文字・非数値・小数）は 0 として扱う
     *
     * @param {string|number} rawValue 入力欄の値
     * @returns {number} 0 以上 MAX_QUANTITY 以下の整数
     */
    function normalizeQuantity(rawValue) {
        // 数値型で渡された場合は文字列化（小数は切り捨て）。
        // 負数は後段の正規表現（非負整数のみ許可）で MIN_QUANTITY に正規化される
        if (typeof rawValue === 'number' && isFinite(rawValue)) {
            rawValue = String(Math.trunc(rawValue));
        }
        if (typeof rawValue !== 'string' || !/^\d+$/.test(rawValue)) {
            return MIN_QUANTITY;
        }
        var value = parseInt(rawValue, 10);
        if (isNaN(value) || value < MIN_QUANTITY) {
            return MIN_QUANTITY;
        }
        if (value > MAX_QUANTITY) {
            return MAX_QUANTITY;
        }
        return value;
    }

    /**
     * 全枚数入力欄から「区分コード→枚数」のオブジェクトを生成する
     *
     * @param {NodeList} qtyInputs data-category 属性付きの input 要素群
     * @returns {{ quantities: Object, unitPrices: Object }}
     */
    function collectQuantities(qtyInputs) {
        var quantities = {};
        var unitPrices = {};
        for (var i = 0; i < qtyInputs.length; i++) {
            var input    = qtyInputs[i];
            var category = input.getAttribute('data-category');
            var price    = parseInt(input.getAttribute('data-unit-price'), 10);
            if (!category || isNaN(price)) {
                continue;
            }
            quantities[category] = normalizeQuantity(input.value);
            unitPrices[category] = price;
        }
        return { quantities: quantities, unitPrices: unitPrices };
    }

    /**
     * 区分別枚数と単価から合計金額を算出する（F-002）
     *
     * @param {Object} quantities { infant: 1, child: 0, adult: 2 } など
     * @param {Object} unitPrices { infant: 100, child: 300, adult: 500 } など
     * @returns {number} 合計金額（円）
     */
    function calcTotal(quantities, unitPrices) {
        var total = 0;
        for (var code in quantities) {
            if (!Object.prototype.hasOwnProperty.call(quantities, code)) {
                continue;
            }
            var price = unitPrices[code] || 0;
            total += price * quantities[code];
        }
        return total;
    }

    /**
     * 区分別枚数から合計枚数を算出する
     *
     * @param {Object} quantities
     * @returns {number} 合計枚数
     */
    function calcCount(quantities) {
        var count = 0;
        for (var code in quantities) {
            if (Object.prototype.hasOwnProperty.call(quantities, code)) {
                count += quantities[code];
            }
        }
        return count;
    }

    /**
     * 金額を「1,234円」形式の文字列に整形する
     *
     * @param {number} amount 金額（円）
     * @returns {string}
     */
    function formatAmount(amount) {
        return amount.toLocaleString('ja-JP') + '円';
    }

    /**
     * 表示の更新と購入ボタンの活性制御をまとめて行う
     *
     * @param {NodeList} qtyInputs
     * @param {Element|null} totalAmountEl
     * @param {Element|null} totalCountEl
     * @param {Element|null} purchaseBtn
     */
    function updateDisplay(qtyInputs, totalAmountEl, totalCountEl, purchaseBtn) {
        var collected = collectQuantities(qtyInputs);
        var quantities = collected.quantities;
        var unitPrices = collected.unitPrices;

        var totalAmount = calcTotal(quantities, unitPrices);
        var totalCount  = calcCount(quantities);

        if (totalAmountEl) {
            totalAmountEl.textContent = formatAmount(totalAmount);
        }
        if (totalCountEl) {
            totalCountEl.textContent = totalCount + '枚';
        }
        if (purchaseBtn) {
            purchaseBtn.disabled = (totalCount < MIN_TOTAL_QUANTITY);
        }
    }

    /**
     * 入力値を正規化してから input 要素に書き戻す
     * 範囲外の値や非数値の場合に表示を補正する役割を持つ
     *
     * @param {HTMLInputElement} input
     */
    function normalizeInputValue(input) {
        var normalized = normalizeQuantity(input.value);
        if (input.value !== String(normalized)) {
            input.value = String(normalized);
        }
    }

    /**
     * リセットボタン押下時の処理（全枚数を 0 に戻す）
     *
     * @param {NodeList} qtyInputs
     */
    function resetQuantities(qtyInputs) {
        for (var i = 0; i < qtyInputs.length; i++) {
            qtyInputs[i].value = String(MIN_QUANTITY);
        }
    }

    /**
     * 画面初期化処理
     * DOMContentLoaded 後に呼び出され、各種イベントリスナーを設定する
     */
    function init() {
        var qtyInputs     = document.querySelectorAll(SELECTOR_QTY);
        var totalAmountEl = document.querySelector(SELECTOR_TOTAL_AMOUNT);
        var totalCountEl  = document.querySelector(SELECTOR_TOTAL_COUNT);
        var purchaseBtn   = document.querySelector(SELECTOR_PURCHASE_BTN);
        var resetBtn      = document.querySelector(SELECTOR_RESET_BTN);

        if (qtyInputs.length === 0) {
            // S-01 以外の画面では何もしない
            return;
        }

        // 枚数入力の min/max を強制設定（HTML 側の設定漏れ対策）
        for (var i = 0; i < qtyInputs.length; i++) {
            var input = qtyInputs[i];
            input.min  = String(MIN_QUANTITY);
            input.max  = String(MAX_QUANTITY);
            input.step = '1';

            input.addEventListener('input', function (event) {
                normalizeInputValue(event.target);
                updateDisplay(qtyInputs, totalAmountEl, totalCountEl, purchaseBtn);
            });
            input.addEventListener('change', function (event) {
                normalizeInputValue(event.target);
                updateDisplay(qtyInputs, totalAmountEl, totalCountEl, purchaseBtn);
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function (event) {
                event.preventDefault();
                resetQuantities(qtyInputs);
                updateDisplay(qtyInputs, totalAmountEl, totalCountEl, purchaseBtn);
            });
        }

        // 初期表示
        updateDisplay(qtyInputs, totalAmountEl, totalCountEl, purchaseBtn);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
