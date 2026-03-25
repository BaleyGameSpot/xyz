-- ============================================================
-- Chinar Signals — MySQL Schema v1.0.0
-- Generated: 2025-03-25
-- Character set: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- ────────────────────────────────────────────────────────────
-- Drop tables (reverse FK order)
-- ────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `signal_user`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `signals`;
DROP TABLE IF EXISTS `trading_pairs`;
DROP TABLE IF EXISTS `packages`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `settings`;

-- ────────────────────────────────────────────────────────────
-- Table: admins
-- Stores administrator accounts for the web panel.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `admins` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(100)    NOT NULL COMMENT 'Display name',
    `email`            VARCHAR(191)    NOT NULL COMMENT 'Login email',
    `password`         VARCHAR(255)    NOT NULL COMMENT 'Bcrypt hash',
    `remember_token`   VARCHAR(100)    DEFAULT NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Administrator accounts — web panel access only';

-- ────────────────────────────────────────────────────────────
-- Table: users
-- Mobile app users (registered via Android app).
-- ────────────────────────────────────────────────────────────
CREATE TABLE `users` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(100)    NOT NULL COMMENT 'Full name',
    `email`            VARCHAR(191)    NOT NULL COMMENT 'Unique email for login',
    `password`         VARCHAR(255)    NOT NULL COMMENT 'Bcrypt hash',
    `phone`            VARCHAR(30)     DEFAULT NULL COMMENT 'Optional phone number',
    `fcm_token`        TEXT            DEFAULT NULL COMMENT 'Firebase Cloud Messaging device token',
    `is_blocked`       TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=blocked, 0=active',
    `email_verified_at` TIMESTAMP      DEFAULT NULL,
    `remember_token`   VARCHAR(100)    DEFAULT NULL,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_is_blocked_index`  (`is_blocked`),
    KEY `users_created_at_index`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mobile app users';

-- ────────────────────────────────────────────────────────────
-- Table: packages
-- Subscription plans available for purchase.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `packages` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                 VARCHAR(100)    NOT NULL COMMENT 'Plan name (Basic, Pro, Premium)',
    `description`          TEXT            DEFAULT NULL COMMENT 'Marketing description',
    `price`                DECIMAL(10,2)   NOT NULL COMMENT 'Monthly price in USD',
    `promo_price`          DECIMAL(10,2)   DEFAULT NULL COMMENT 'Optional discounted price',
    `pairs_limit`          SMALLINT        NOT NULL DEFAULT 0 COMMENT '0 = unlimited trading pairs',
    `daily_signals_limit`  SMALLINT        NOT NULL DEFAULT 0 COMMENT '0 = unlimited daily signals',
    `timeframes`           JSON            DEFAULT NULL COMMENT 'Allowed timeframes array: ["H1","H4","D1"]',
    `is_active`            TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=visible to users',
    `is_popular`           TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=highlighted in app',
    `sort_order`           SMALLINT        NOT NULL DEFAULT 0 COMMENT 'Display order (ascending)',
    `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `packages_is_active_index`  (`is_active`),
    KEY `packages_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Subscription plans / packages';

-- ────────────────────────────────────────────────────────────
-- Table: subscriptions
-- Links users to packages with a validity window.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `subscriptions` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `package_id`  BIGINT UNSIGNED NOT NULL,
    `payment_id`  BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL for manual assignments',
    `starts_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`  TIMESTAMP       DEFAULT NULL COMMENT 'NULL = lifetime',
    `is_manual`   TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=assigned by admin',
    `note`        TEXT            DEFAULT NULL COMMENT 'Admin note for manual assignments',
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `subscriptions_user_id_index`     (`user_id`),
    KEY `subscriptions_package_id_index`  (`package_id`),
    KEY `subscriptions_expires_at_index`  (`expires_at`),
    CONSTRAINT `fk_sub_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sub_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='User subscription records';

-- ────────────────────────────────────────────────────────────
-- Table: trading_pairs
-- Master list of supported currency / instrument pairs.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `trading_pairs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `symbol`      VARCHAR(20)     NOT NULL COMMENT 'e.g. XAUUSD, EURUSD, BTCUSDT',
    `name`        VARCHAR(100)    DEFAULT NULL COMMENT 'Human name: Gold / US Dollar',
    `category`    ENUM('forex','crypto','commodity','index') NOT NULL DEFAULT 'forex',
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `trading_pairs_symbol_unique` (`symbol`),
    KEY `trading_pairs_category_index`   (`category`),
    KEY `trading_pairs_is_active_index`  (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Supported trading instruments';

-- ────────────────────────────────────────────────────────────
-- Table: signals
-- Generated trading signals.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `signals` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pair`                 VARCHAR(20)     NOT NULL COMMENT 'Trading symbol e.g. XAUUSD',
    `timeframe`            VARCHAR(5)      NOT NULL COMMENT 'M1,M5,M15,M30,H1,H4,D1,W1',
    `type`                 ENUM('BUY','SELL') NOT NULL COMMENT 'Signal direction',
    `entry_price`          DECIMAL(18,6)   NOT NULL COMMENT 'Suggested entry price',
    `stop_loss`            DECIMAL(18,6)   NOT NULL COMMENT 'Stop loss level',
    `take_profits`         JSON            NOT NULL COMMENT 'Array of TP levels [tp1, tp2, tp3]',
    `confidence_score`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'AI confidence 0-100',
    `status`               ENUM('pending','win','loss') NOT NULL DEFAULT 'pending',
    `reason`               TEXT            DEFAULT NULL COMMENT 'Plain text analysis summary',
    `indicators`           JSON            DEFAULT NULL COMMENT 'Detailed indicator breakdown array',
    `notifications_sent`   INT             NOT NULL DEFAULT 0 COMMENT 'Number of push notifications sent',
    `closed_at`            TIMESTAMP       DEFAULT NULL COMMENT 'When status was set to win/loss',
    `created_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `signals_pair_index`       (`pair`),
    KEY `signals_timeframe_index`  (`timeframe`),
    KEY `signals_type_index`       (`type`),
    KEY `signals_status_index`     (`status`),
    KEY `signals_created_at_index` (`created_at`),
    KEY `signals_pair_tf_status`   (`pair`, `timeframe`, `status`) COMMENT 'Composite for common filters'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Trading signals generated by the AI engine';

-- ────────────────────────────────────────────────────────────
-- Table: signal_user
-- Pivot: tracks which signals a user received / opened.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `signal_user` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `signal_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `delivered_at` TIMESTAMP       DEFAULT NULL COMMENT 'When push notification was sent',
    `opened_at`    TIMESTAMP       DEFAULT NULL COMMENT 'When user tapped the notification',
    `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `signal_user_unique` (`signal_id`, `user_id`),
    KEY `signal_user_user_id_index`   (`user_id`),
    KEY `signal_user_signal_id_index` (`signal_id`),
    CONSTRAINT `fk_su_signal` FOREIGN KEY (`signal_id`) REFERENCES `signals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_su_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Signal delivery tracking per user';

-- ────────────────────────────────────────────────────────────
-- Table: payments
-- Crypto payment submissions from users.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `payments` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `package_id`     BIGINT UNSIGNED NOT NULL,
    `amount`         DECIMAL(10,2)   NOT NULL COMMENT 'USD equivalent',
    `crypto_type`    VARCHAR(20)     NOT NULL COMMENT 'USDT_TRC20, USDT_ERC20, BTC, ETH, BNB',
    `tx_hash`        VARCHAR(255)    DEFAULT NULL COMMENT 'Blockchain transaction hash',
    `status`         ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    `reject_reason`  VARCHAR(100)    DEFAULT NULL COMMENT 'Reason code for rejection',
    `reject_note`    TEXT            DEFAULT NULL COMMENT 'Admin note to user on rejection',
    `verified_by`    BIGINT UNSIGNED DEFAULT NULL COMMENT 'Admin ID who verified',
    `verified_at`    TIMESTAMP       DEFAULT NULL,
    `created_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_tx_hash_unique`       (`tx_hash`),
    KEY `payments_user_id_index`               (`user_id`),
    KEY `payments_package_id_index`            (`package_id`),
    KEY `payments_status_index`                (`status`),
    KEY `payments_created_at_index`            (`created_at`),
    CONSTRAINT `fk_pay_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`    (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pay_admin`   FOREIGN KEY (`verified_by`) REFERENCES `admins`  (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Crypto payment submissions and verification status';

-- ────────────────────────────────────────────────────────────
-- Table: settings
-- Key-value store for application configuration.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `settings` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(100)    NOT NULL COMMENT 'Setting key e.g. wallet_usdt_trc20',
    `value`       TEXT            DEFAULT NULL COMMENT 'Setting value (JSON-encoded if complex)',
    `group`       VARCHAR(50)     NOT NULL DEFAULT 'general' COMMENT 'Logical group: general, wallets, signals, notifications',
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `settings_key_unique` (`key`),
    KEY `settings_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Application settings / configuration key-value store';

-- ============================================================
-- INITIAL DATA
-- ============================================================

-- ── Default admin account ──────────────────────────────────
-- Password: Admin@12345 (bcrypt)
-- IMPORTANT: Change immediately after first login!
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Super Admin', 'admin@chinarsignals.com', '$2y$12$K8YkqJQ8GjBGi5C6VfS1nuDh.LqzfL.o/XsWm6z1kfP3Mzs0LXLKe');

-- ── Default packages ───────────────────────────────────────
INSERT INTO `packages`
    (`name`, `description`, `price`, `promo_price`, `pairs_limit`, `daily_signals_limit`, `timeframes`, `is_active`, `is_popular`, `sort_order`)
VALUES
(
    'Basic',
    'Perfect for beginners. Access to the most popular Forex pairs with daily signals.',
    9.99, NULL, 5, 3,
    '["H1","H4","D1"]',
    1, 0, 1
),
(
    'Pro',
    'Full access for serious traders. Unlimited signals across all pairs and timeframes.',
    19.99, 14.99, 0, 0,
    '["M5","M15","M30","H1","H4","D1","W1"]',
    1, 1, 2
),
(
    'Premium',
    'Ultimate trading suite. All Pro features plus priority alerts and dedicated support.',
    39.99, NULL, 0, 0,
    '["M1","M5","M15","M30","H1","H4","D1","W1"]',
    1, 0, 3
);

-- ── Trading pairs ───────────────────────────────────────────
INSERT INTO `trading_pairs` (`symbol`, `name`, `category`) VALUES
-- Forex Majors
('XAUUSD', 'Gold / US Dollar',        'commodity'),
('EURUSD', 'Euro / US Dollar',         'forex'),
('GBPUSD', 'British Pound / US Dollar','forex'),
('USDJPY', 'US Dollar / Japanese Yen', 'forex'),
('USDCHF', 'US Dollar / Swiss Franc',  'forex'),
('AUDUSD', 'Australian Dollar / USD',  'forex'),
('USDCAD', 'US Dollar / Canadian Dollar','forex'),
('NZDUSD', 'New Zealand Dollar / USD', 'forex'),
-- Forex Crosses
('EURJPY', 'Euro / Japanese Yen',      'forex'),
('EURGBP', 'Euro / British Pound',     'forex'),
('GBPJPY', 'British Pound / Yen',      'forex'),
-- Commodities
('XAGUSD', 'Silver / US Dollar',       'commodity'),
('USOIL',  'Crude Oil (WTI)',           'commodity'),
-- Crypto
('BTCUSDT','Bitcoin / USDT',           'crypto'),
('ETHUSDT','Ethereum / USDT',          'crypto'),
('BNBUSDT','Binance Coin / USDT',      'crypto');

-- ── Default settings ────────────────────────────────────────
INSERT INTO `settings` (`key`, `value`, `group`) VALUES
('app_name',             'Chinar Signals',    'general'),
('support_email',        '',                  'general'),
('telegram_channel',     '',                  'general'),
('maintenance_mode',     '0',                 'general'),
('min_confidence',       '65',                'signals'),
('max_daily_signals',    '20',                'signals'),
('signals_enabled',      '1',                 'signals'),
('auto_close_signals',   '0',                 'signals'),
('active_pairs',         'XAUUSD\nEURUSD\nGBPUSD\nUSDJPY\nBTCUSDT\nETHUSDT', 'signals'),
('wallet_usdt_trc20',    '',                  'wallets'),
('wallet_usdt_erc20',    '',                  'wallets'),
('wallet_btc',           '',                  'wallets'),
('wallet_eth',           '',                  'wallets'),
('wallet_bnb',           '',                  'wallets'),
('firebase_project_id',  '',                  'notifications'),
('firebase_server_key',  '',                  'notifications'),
('notify_new_signal',    '1',                 'notifications'),
('notify_signal_result', '1',                 'notifications'),
('notify_payment_verified','1',              'notifications');

-- ============================================================
SET foreign_key_checks = 1;
-- ============================================================
-- End of schema.sql
-- ============================================================
