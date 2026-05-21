-- =============================================================================
-- スペースワールド入園券売機システム DBスキーマ
-- 担当：B担当（購入処理）／C担当（管理者ログイン）共通
--
-- 設計書「DBテーブル設計」セクションに従い、T-01〜T-04 を定義する。
-- 文字コードは utf8mb4、ストレージエンジンは InnoDB を使用する。
-- =============================================================================

SET NAMES utf8mb4;

-- T-01 年齢区分マスタ -------------------------------------------------------
CREATE TABLE IF NOT EXISTS age_categories (
    category_id   INT          NOT NULL PRIMARY KEY,
    category_name VARCHAR(20)  NOT NULL,
    min_age       INT          NOT NULL,
    max_age       INT          NULL,
    price         INT          NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO age_categories (category_id, category_name, min_age, max_age, price) VALUES
    (1, '幼児',   0,    3, 100),
    (2, '小児',   4,    9, 300),
    (3, '一般',  10, NULL, 500)
ON DUPLICATE KEY UPDATE
    category_name = VALUES(category_name),
    min_age       = VALUES(min_age),
    max_age       = VALUES(max_age),
    price         = VALUES(price);

-- T-02 購入履歴 -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchases (
    purchase_id     INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    purchased_at    DATETIME NOT NULL,
    total_amount    INT      NOT NULL,
    total_count     INT      NOT NULL,
    paid_amount     INT      NULL,
    expiration_date DATE     NOT NULL,
    INDEX idx_purchases_purchased_at (purchased_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- T-03 購入明細 -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS purchase_details (
    detail_id   INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    category_id INT NOT NULL,
    quantity    INT NOT NULL,
    unit_price  INT NOT NULL,
    subtotal    INT NOT NULL,
    CONSTRAINT fk_purchase_details_purchase
        FOREIGN KEY (purchase_id) REFERENCES purchases (purchase_id)
        ON DELETE CASCADE,
    CONSTRAINT fk_purchase_details_category
        FOREIGN KEY (category_id) REFERENCES age_categories (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- T-04 管理者 ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_id VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初期管理者アカウント（パスワードは "admin123" を password_hash でハッシュ化）
-- 本番投入前に必ず再生成すること：
--   php -r "echo password_hash('your_password', PASSWORD_DEFAULT), PHP_EOL;"
INSERT INTO admins (login_id, password) VALUES
    ('admin', '$2y$10$E5O5e2NkM3M3Z3y6QnZ5l.7iX5q5wL7Q9aB4dQK1mT8FfYjXNQXq2')
ON DUPLICATE KEY UPDATE login_id = VALUES(login_id);
