-- ============================================================
-- Quantum BlocX — Cold Wallet Database Schema
-- MySQL 8.x compatible
-- Updated by Wayne — June 2026
-- ============================================================
-- Clean creation script — safe to import on a fresh database.
-- Run with: mysql -u root -p < database.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


-- ============================================================
-- 1. USERS
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `username`          VARCHAR(50)          DEFAULT NULL,
  `email`             VARCHAR(255)         UNIQUE NOT NULL,
  `password`          VARCHAR(255)         NOT NULL,
  `full_name`         VARCHAR(255)         DEFAULT NULL,
  `avatar_url`        VARCHAR(500)         DEFAULT NULL,
  `current_ip`        VARCHAR(45)          DEFAULT NULL,
  `kyc_status`        ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
  `card_tier`         ENUM('none','VirtuElevate','VirtuElite') NOT NULL DEFAULT 'none',
  `two_fa_enabled`    TINYINT(1)           NOT NULL DEFAULT 0,
  `two_fa_secret`     VARCHAR(255)         DEFAULT NULL,
  `recovery_phrase`   TEXT                 DEFAULT NULL COMMENT 'AES-256 encrypted BIP-39 mnemonic',
  `is_verified`       TINYINT(1)           NOT NULL DEFAULT 0,
  `is_active`         TINYINT(1)           NOT NULL DEFAULT 1,
  `role`              ENUM('user','admin') DEFAULT 'user',
  `referred_by`       INT                  DEFAULT NULL,
  `referral_code`     VARCHAR(20)          DEFAULT NULL,
  `last_login_at`     TIMESTAMP            NULL DEFAULT NULL,
  `created_at`        TIMESTAMP            DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_referral` (`referral_code`),
  KEY `idx_users_kyc` (`kyc_status`),
  KEY `idx_users_card` (`card_tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. AUTHENTICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `email`       VARCHAR(255) NOT NULL,
  `token`       VARCHAR(255) NOT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `expires_at`  TIMESTAMP    NOT NULL,
  KEY `idx_pr_email` (`email`),
  KEY `idx_pr_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`        INT          NOT NULL,
  `session_token`  VARCHAR(255) UNIQUE NOT NULL,
  `ip_address`     VARCHAR(45)  DEFAULT NULL,
  `user_agent`     TEXT         DEFAULT NULL,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `expires_at`     TIMESTAMP    NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT        NOT NULL,
  `token`       VARCHAR(6) NOT NULL,
  `created_at`  TIMESTAMP  DEFAULT CURRENT_TIMESTAMP,
  `expires_at`  TIMESTAMP  NOT NULL,
  UNIQUE KEY `uq_ev_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. CURRENCIES (29 supported assets)
-- ============================================================

CREATE TABLE IF NOT EXISTS `currencies` (
  `id`                            INT AUTO_INCREMENT PRIMARY KEY,
  `symbol`                        VARCHAR(20)   NOT NULL,
  `name`                          VARCHAR(100)  NOT NULL,
  `network`                       VARCHAR(50)   NOT NULL,
  `contract_address`              VARCHAR(100)  DEFAULT NULL,
  `icon_url`                      VARCHAR(500)  DEFAULT NULL,
  `decimals`                      TINYINT UNSIGNED NOT NULL DEFAULT 8,
  `is_active`                     TINYINT(1)    NOT NULL DEFAULT 1,
  `is_new`                        TINYINT(1)    NOT NULL DEFAULT 0,
  `is_popular`                    TINYINT(1)    NOT NULL DEFAULT 0,
  `expected_arrival_confirmations` INT UNSIGNED  NOT NULL DEFAULT 3,
  `expected_unlock_confirmations`  INT UNSIGNED  NOT NULL DEFAULT 7,
  `current_price_usd`             DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `price_change_24h_pct`          DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
  `min_send_amount`               DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `send_fee`                      DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `sort_order`                    INT UNSIGNED  NOT NULL DEFAULT 0,
  `price_updated_at`              TIMESTAMP     NULL DEFAULT NULL,
  `created_at`                    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_currency_symbol_network` (`symbol`, `network`),
  KEY `idx_currencies_active` (`is_active`),
  KEY `idx_currencies_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. WALLETS (per user per asset)
-- ============================================================

CREATE TABLE IF NOT EXISTS `wallets` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT           NOT NULL,
  `currency_id`     INT           NOT NULL,
  `address`         VARCHAR(255)  NOT NULL,
  `balance`         DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `locked_balance`  DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `network`         VARCHAR(50)   NOT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_wallet_user_currency` (`user_id`, `currency_id`),
  KEY `idx_wallets_address` (`address`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. TRANSACTIONS (crypto operations)
-- ============================================================

CREATE TABLE IF NOT EXISTS `transactions` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`            INT           NOT NULL,
  `wallet_id`          INT           DEFAULT NULL,
  `type`               ENUM('send','receive','swap','mining_reward','investment_return',
                            'card_purchase','admin_credit','admin_debit') NOT NULL,
  `status`             ENUM('pending','confirming','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `amount`             DECIMAL(20,8) NOT NULL,
  `amount_usd`         DECIMAL(20,2) DEFAULT NULL,
  `currency_id`        INT           DEFAULT NULL,
  `currency_symbol`    VARCHAR(20)   DEFAULT NULL,
  `recipient_address`  VARCHAR(255)  DEFAULT NULL,
  `recipient_user_id`  INT           DEFAULT NULL,
  `sender_address`     VARCHAR(255)  DEFAULT NULL,
  `tx_hash`            VARCHAR(255)  DEFAULT NULL,
  `block_number`       BIGINT UNSIGNED DEFAULT NULL,
  `confirmations`      INT UNSIGNED  NOT NULL DEFAULT 0,
  `fee`                DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `notes`              TEXT          DEFAULT NULL,
  `ip_address`         VARCHAR(45)   DEFAULT NULL,
  `completed_at`       TIMESTAMP     NULL DEFAULT NULL,
  `created_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_tx_user` (`user_id`),
  KEY `idx_tx_type` (`type`),
  KEY `idx_tx_status` (`status`),
  KEY `idx_tx_hash` (`tx_hash`),
  KEY `idx_tx_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallets`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recipient_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. SWAPS
-- ============================================================

CREATE TABLE IF NOT EXISTS `swaps` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT           NOT NULL,
  `from_currency_id` INT           NOT NULL,
  `to_currency_id`   INT           NOT NULL,
  `from_amount`      DECIMAL(20,8) NOT NULL,
  `to_amount`        DECIMAL(20,8) NOT NULL,
  `exchange_rate`    DECIMAL(20,8) NOT NULL,
  `fee`              DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `fee_pct`          DECIMAL(5,4)  NOT NULL DEFAULT 0.0000,
  `status`           ENUM('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `completed_at`     TIMESTAMP     NULL DEFAULT NULL,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_swaps_user` (`user_id`),
  KEY `idx_swaps_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`to_currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. VIRTUAL CARDS (QFS Card)
-- ============================================================

CREATE TABLE IF NOT EXISTS `virtual_cards` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`           INT          NOT NULL,
  `card_tier`         ENUM('VirtuElevate','VirtuElite') NOT NULL,
  `card_number_masked` VARCHAR(25) NOT NULL DEFAULT '**** **** **** ****',
  `card_type`         VARCHAR(30)  NOT NULL DEFAULT 'Mastercard',
  `issuer`            VARCHAR(100) NOT NULL DEFAULT 'WebBank',
  `status`            ENUM('pending','active','suspended','cancelled','expired') NOT NULL DEFAULT 'pending',
  `price_paid_usd`    DECIMAL(12,2) NOT NULL,
  `cashback_pct`      DECIMAL(4,2) NOT NULL DEFAULT 4.00,
  `daily_limit_usd`   DECIMAL(12,2) DEFAULT NULL,
  `monthly_limit_usd` DECIMAL(12,2) DEFAULT NULL,
  `activated_at`      TIMESTAMP    NULL DEFAULT NULL,
  `expires_at`        TIMESTAMP    NULL DEFAULT NULL,
  `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_vc_user` (`user_id`),
  KEY `idx_vc_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. KYC APPLICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `kyc_applications` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT          NOT NULL,
  `first_name`       VARCHAR(100) NOT NULL,
  `last_name`        VARCHAR(100) NOT NULL,
  `email`            VARCHAR(255) NOT NULL,
  `phone_number`     VARCHAR(30)  NOT NULL,
  `date_of_birth`    DATE         NOT NULL,
  `social_handle`    VARCHAR(100) DEFAULT NULL,
  `address_line`     VARCHAR(255) NOT NULL,
  `city`             VARCHAR(100) NOT NULL,
  `state`            VARCHAR(100) NOT NULL,
  `nationality`      VARCHAR(100) NOT NULL,
  `document_type`    ENUM('drivers_license','passport','national_id') NOT NULL,
  `document_front_url` VARCHAR(500) NOT NULL,
  `document_back_url`  VARCHAR(500) DEFAULT NULL,
  `terms_accepted`   TINYINT(1)   NOT NULL DEFAULT 0,
  `status`           ENUM('pending','under_review','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT         DEFAULT NULL,
  `reviewed_by`      INT          DEFAULT NULL,
  `submitted_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at`      TIMESTAMP    NULL DEFAULT NULL,
  `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_kyc_user` (`user_id`),
  KEY `idx_kyc_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. MINING
-- ============================================================

CREATE TABLE IF NOT EXISTS `mining_sessions` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT           NOT NULL,
  `currency_id`  INT           NOT NULL,
  `status`       ENUM('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
  `hashrate`     DECIMAL(20,8) DEFAULT NULL,
  `total_earned` DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `started_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `ended_at`     TIMESTAMP     NULL DEFAULT NULL,
  `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_ms_user` (`user_id`),
  KEY `idx_ms_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mining_rewards` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `session_id`   INT           NOT NULL,
  `user_id`      INT           NOT NULL,
  `wallet_id`    INT           NOT NULL,
  `currency_id`  INT           NOT NULL,
  `amount`       DECIMAL(20,8) NOT NULL,
  `amount_usd`   DECIMAL(20,2) DEFAULT NULL,
  `status`       ENUM('pending','credited','failed') NOT NULL DEFAULT 'pending',
  `credited_at`  TIMESTAMP     NULL DEFAULT NULL,
  `created_at`   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_mr_session` (`session_id`),
  KEY `idx_mr_user` (`user_id`),
  FOREIGN KEY (`session_id`) REFERENCES `mining_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. INVESTMENTS (crypto, premium-gated)
-- ============================================================

CREATE TABLE IF NOT EXISTS `investment_plans` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `name`            VARCHAR(100)  NOT NULL,
  `description`     TEXT          DEFAULT NULL,
  `currency_id`     INT           DEFAULT NULL,
  `min_amount`      DECIMAL(20,8) NOT NULL,
  `max_amount`      DECIMAL(20,8) DEFAULT NULL,
  `apy_pct`         DECIMAL(6,3)  NOT NULL,
  `lock_period_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `investments` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT           NOT NULL,
  `plan_id`          INT           NOT NULL,
  `wallet_id`        INT           NOT NULL,
  `currency_id`      INT           NOT NULL,
  `principal_amount` DECIMAL(20,8) NOT NULL,
  `earned_amount`    DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
  `status`           ENUM('active','matured','withdrawn','cancelled') NOT NULL DEFAULT 'active',
  `started_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `matures_at`       TIMESTAMP     NULL DEFAULT NULL,
  `withdrawn_at`     TIMESTAMP     NULL DEFAULT NULL,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_inv_user` (`user_id`),
  KEY `idx_inv_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `investment_plans`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`wallet_id`) REFERENCES `wallets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. WALLET PROVIDERS & LINKED WALLETS
-- ============================================================

CREATE TABLE IF NOT EXISTS `wallet_providers` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(100) NOT NULL,
  `logo_url`    VARCHAR(500) DEFAULT NULL,
  `website`     VARCHAR(500) DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_wp_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `linked_wallets` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT          NOT NULL,
  `provider_id`     INT          DEFAULT NULL,
  `provider_name`   VARCHAR(100) NOT NULL,
  `address`         VARCHAR(255) NOT NULL,
  `chain_id`        INT UNSIGNED DEFAULT NULL,
  `session_topic`   VARCHAR(255) DEFAULT NULL,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `connected_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `disconnected_at` TIMESTAMP    NULL DEFAULT NULL,
  `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_lw_user` (`user_id`),
  KEY `idx_lw_address` (`address`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`provider_id`) REFERENCES `wallet_providers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. SUPPORT TICKETS
-- ============================================================

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT          NOT NULL,
  `ticket_ref`  VARCHAR(20)  NOT NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `body`        TEXT         NOT NULL,
  `priority`    ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `category`    VARCHAR(100) DEFAULT NULL,
  `status`      ENUM('open','in_progress','awaiting_reply','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to` INT          DEFAULT NULL,
  `closed_at`   TIMESTAMP    NULL DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_ticket_ref` (`ticket_ref`),
  KEY `idx_st_user` (`user_id`),
  KEY `idx_st_status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_ticket_replies` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `ticket_id`      INT      NOT NULL,
  `user_id`        INT      NOT NULL,
  `body`           TEXT     NOT NULL,
  `is_staff_reply` TINYINT(1) NOT NULL DEFAULT 0,
  `attachment_url`  VARCHAR(500) DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_str_ticket` (`ticket_id`),
  FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT          NOT NULL,
  `type`        VARCHAR(50)  NOT NULL DEFAULT 'system',
  `title`       VARCHAR(255) NOT NULL,
  `message`     TEXT         NOT NULL,
  `action_url`  VARCHAR(500) DEFAULT NULL,
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `read_at`     TIMESTAMP    NULL DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`user_id`, `is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. FEE SCHEDULE (per card tier)
-- ============================================================

CREATE TABLE IF NOT EXISTS `fee_schedule` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `card_tier`    ENUM('none','VirtuElevate','VirtuElite') NOT NULL,
  `fee_type`     ENUM('swap','send','receive','withdrawal') NOT NULL,
  `fee_pct`      DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
  `fee_flat_usd` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_active`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_fee_tier_type` (`card_tier`, `fee_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. EXCHANGE RATES CACHE
-- ============================================================

CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `from_currency_id` INT           NOT NULL,
  `to_currency_id`   INT           NOT NULL,
  `rate`             DECIMAL(20,8) NOT NULL,
  `source`           VARCHAR(50)   NOT NULL DEFAULT 'internal',
  `fetched_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `expires_at`       TIMESTAMP     NULL DEFAULT NULL,
  KEY `idx_er_pair` (`from_currency_id`, `to_currency_id`),
  FOREIGN KEY (`from_currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`to_currency_id`) REFERENCES `currencies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. ACTIVITY LOG (audit trail)
-- ============================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT          DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50)  DEFAULT NULL,
  `entity_id`   INT          DEFAULT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `user_agent`  VARCHAR(500) DEFAULT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_al_user` (`user_id`),
  KEY `idx_al_action` (`action`),
  KEY `idx_al_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. CONTACT MESSAGES (kept from original)
-- ============================================================

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(255) NOT NULL,
  `email`       VARCHAR(255) NOT NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `message`     TEXT         NOT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 18. CRON LOGS (kept from original)
-- ============================================================

CREATE TABLE IF NOT EXISTS `cron_logs` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `job_name`  VARCHAR(100)                       NOT NULL,
  `status`    ENUM('success','partial','failed') NOT NULL,
  `message`   TEXT      DEFAULT NULL,
  `ran_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 19. SYSTEM SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `key`        VARCHAR(100) NOT NULL UNIQUE,
  `value`      TEXT         NOT NULL,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `system_settings` (`key`, `value`) VALUES
  ('deposits_enabled',       '1'),
  ('withdrawals_enabled',    '1'),
  ('swaps_enabled',          '1'),
  ('mining_enabled',         '1'),
  ('investments_enabled',    '1'),
  ('maintenance_mode',       '0'),
  ('kyc_required_for_send',  '1'),
  ('card_required_for_send', '1'),
  ('default_swap_fee_pct',   '2.5'),
  ('default_send_fee_pct',   '1.5'),
  ('price_feed_source',      'coingecko'),
  ('price_feed_interval',    '300');


-- ============================================================
-- SEED: 29 Supported Currencies
-- ============================================================

DELETE FROM `currencies` WHERE `id` > 0;

INSERT INTO `currencies`
  (`id`, `symbol`, `name`, `network`, `is_active`, `is_new`, `is_popular`,
   `expected_arrival_confirmations`, `expected_unlock_confirmations`, `sort_order`) VALUES
( 1, 'XAUT',  'Tether Gold',      'ERC-20',       1, 1, 0,  12, 30,  1),
( 2, 'PAXG',  'PAX Gold',         'ERC-20',       1, 1, 0,  12, 30,  2),
( 3, 'KAG',   'Kinesis Silver',   'Kinesis',      1, 1, 0,   6, 12,  3),
( 4, 'BTC',   'Bitcoin',          'Bitcoin',      1, 0, 0,   3,  7,  4),
( 5, 'ETH',   'Ethereum',         'ERC-20',       1, 0, 0,  12, 30,  5),
( 6, 'SUI',   'Sui',              'Sui',          1, 0, 0,   3,  7,  6),
( 7, 'LTC',   'Litecoin',         'Litecoin',     1, 0, 0,   6, 12,  7),
( 8, 'LINK',  'Chainlink',        'ERC-20',       1, 0, 0,  12, 30,  8),
( 9, 'BNB',   'BNB',              'BEP-20',       1, 0, 0,  15, 30,  9),
(10, 'AAVE',  'Aave',             'ERC-20',       1, 0, 0,  12, 30, 10),
(11, 'USDT',  'Tether USD',       'ERC-20',       1, 0, 0,  12, 30, 11),
(12, 'USDT',  'Tether USD',       'TRC-20',       1, 0, 0,  20, 30, 12),
(13, 'USDT',  'Tether USD',       'BEP-20',       1, 0, 0,  15, 30, 13),
(14, 'USDT',  'Tether USD',       'SOL',          1, 0, 0,   3,  7, 14),
(15, 'USDC',  'USD Coin',         'ERC-20',       1, 0, 0,  12, 30, 15),
(16, 'USDC',  'USD Coin',         'TRC-20',       1, 0, 0,  20, 30, 16),
(17, 'USDC',  'USD Coin',         'BEP-20',       1, 0, 0,  15, 30, 17),
(18, 'USDC',  'USD Coin',         'SOL',          1, 0, 0,   3,  7, 18),
(19, 'BCH',   'Bitcoin Cash',     'Bitcoin Cash', 1, 0, 0,   6, 12, 19),
(20, 'XRP',   'Ripple',           'XRP Ledger',   1, 0, 1,   1,  3, 20),
(21, 'XLM',   'Stellar',          'Stellar',      1, 0, 0,   1,  3, 21),
(22, 'ADA',   'Cardano',          'Cardano',      1, 0, 0,  15, 30, 22),
(23, 'TRX',   'TRON',             'TRC-20',       1, 0, 0,  20, 30, 23),
(24, 'SOL',   'Solana',           'SOL',          1, 0, 0,   3,  7, 24),
(25, 'DOGE',  'Dogecoin',         'Dogecoin',     1, 0, 0,  20, 40, 25),
(26, 'QNT',   'Quant',            'ERC-20',       1, 0, 0,  12, 30, 26),
(27, 'ALGO',  'Algorand',         'Algorand',     1, 0, 0,   1,  3, 27),
(28, 'TRUMP', 'Official Trump',   'SOL',          1, 0, 0,   3,  7, 28),
(29, 'RLUSD', 'Ripple USD',       'ERC-20',       1, 0, 0,  12, 30, 29),
(30, 'SFP',   'SafePal',          'BEP-20',       1, 0, 0,  15, 30, 30);


-- ============================================================
-- SEED: Fee Schedule
-- ============================================================

DELETE FROM `fee_schedule` WHERE `id` > 0;

INSERT INTO `fee_schedule` (`card_tier`, `fee_type`, `fee_pct`, `fee_flat_usd`) VALUES
('none',           'swap',       2.5000, 0.00),
('none',           'send',       1.5000, 0.00),
('none',           'receive',    0.0000, 0.00),
('none',           'withdrawal', 2.0000, 0.00),
('VirtuElevate',   'swap',       1.0000, 0.00),
('VirtuElevate',   'send',       0.5000, 0.00),
('VirtuElevate',   'receive',    0.0000, 0.00),
('VirtuElevate',   'withdrawal', 1.0000, 0.00),
('VirtuElite',     'swap',       0.0000, 0.00),
('VirtuElite',     'send',       0.0000, 0.00),
('VirtuElite',     'receive',    0.0000, 0.00),
('VirtuElite',     'withdrawal', 0.0000, 0.00);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA
-- ============================================================
