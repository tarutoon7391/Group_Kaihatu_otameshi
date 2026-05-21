/**
 * 管理者画面用 JavaScript
 *
 * 担当：C担当（一部 画面担当）
 *
 * 機能：
 *   - ログアウトリンク押下時の確認ダイアログ表示
 *   - 月次集計画面で対象年月未選択のまま送信されるのを防ぐ簡易チェック
 *
 * コーディング規約：
 *   - 変数名・関数名：キャメルケース
 *   - 定数：大文字スネークケース
 *   - コメント：日本語
 */
(function () {
    'use strict';

    var SELECTOR_LOGOUT_LINK   = '.admin-header__link--logout';
    var SELECTOR_AGGREGATE_FORM = '.aggregate-form';
    var SELECTOR_TARGET_MONTH  = '#target_month';

    var CONFIRM_LOGOUT_MESSAGE       = 'ログアウトしますか？';
    var ALERT_TARGET_MONTH_REQUIRED  = '対象年月を選択してください。';

    /**
     * ログアウト確認ダイアログを設定する
     */
    function bindLogoutConfirm() {
        var links = document.querySelectorAll(SELECTOR_LOGOUT_LINK);
        for (var i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function (event) {
                if (!window.confirm(CONFIRM_LOGOUT_MESSAGE)) {
                    event.preventDefault();
                }
            });
        }
    }

    /**
     * 月次集計フォーム送信時の簡易バリデーション
     */
    function bindAggregateFormValidation() {
        var form = document.querySelector(SELECTOR_AGGREGATE_FORM);
        if (!form) {
            return;
        }
        form.addEventListener('submit', function (event) {
            var select = form.querySelector(SELECTOR_TARGET_MONTH);
            if (!select || !select.value) {
                event.preventDefault();
                window.alert(ALERT_TARGET_MONTH_REQUIRED);
            }
        });
    }

    /**
     * 画面初期化処理
     */
    function init() {
        bindLogoutConfirm();
        bindAggregateFormValidation();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
