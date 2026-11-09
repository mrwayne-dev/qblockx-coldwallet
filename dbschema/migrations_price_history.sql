-- Run once on live: adds the price log (fix #8)
CREATE TABLE IF NOT EXISTS `price_history` (
  `id`             BIGINT AUTO_INCREMENT PRIMARY KEY,
  `currency_id`    INT           NOT NULL,
  `symbol`         VARCHAR(20)   NOT NULL,
  `price_usd`      DECIMAL(20,8) NOT NULL,
  `change_24h_pct` DECIMAL(10,4) NULL,
  `recorded_at`    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ph_symbol_time` (`symbol`, `recorded_at`),
  KEY `idx_ph_time` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
