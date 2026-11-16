-- 003_multi_currency.sql
-- Real multi-currency support: currency registry, per-currency exchange rates,
-- rate history, and user display-currency preference. Non-destructive & repeat-safe.

CREATE TABLE IF NOT EXISTS currencies (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(12) NOT NULL DEFAULT '',
    decimals TINYINT UNSIGNED NOT NULL DEFAULT 2,
    symbol_position ENUM('before','after') NOT NULL DEFAULT 'before',
    exchange_rate DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    rate_source VARCHAR(60) NULL DEFAULT NULL,
    rate_updated_at DATETIME NULL DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_currency_code (code),
    KEY idx_currency_enabled (is_enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS currency_rate_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    currency_id INT UNSIGNED NOT NULL,
    rate DECIMAL(18,8) NOT NULL,
    source VARCHAR(60) NOT NULL DEFAULT 'manual',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rate_history_currency (currency_id, created_at),
    CONSTRAINT fk_rate_history_currency FOREIGN KEY (currency_id) REFERENCES currencies (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the base currency plus common display currencies.
-- exchange_rate = units of this currency per 1 unit of the base currency.
INSERT IGNORE INTO currencies (code, name, symbol, decimals, symbol_position, exchange_rate, is_enabled, is_default, rate_source, rate_updated_at, sort_order) VALUES
('INR', 'Indian Rupee', '₹', 2, 'before', 1.00000000, 1, 1, 'base', NOW(), 1),
('USD', 'US Dollar', '$', 2, 'before', 0.01200000, 1, 0, 'manual', NOW(), 2),
('EUR', 'Euro', '€', 2, 'before', 0.01100000, 1, 0, 'manual', NOW(), 3),
('GBP', 'British Pound', '£', 2, 'before', 0.00940000, 1, 0, 'manual', NOW(), 4),
('AED', 'UAE Dirham', 'د.إ', 2, 'after', 0.04400000, 1, 0, 'manual', NOW(), 5),
('SAR', 'Saudi Riyal', '﷼', 2, 'after', 0.04500000, 1, 0, 'manual', NOW(), 6);

ALTER TABLE users ADD COLUMN IF NOT EXISTS preferred_currency VARCHAR(10) NULL DEFAULT NULL AFTER custom_rate_multiplier;

-- Keep the legacy general settings in sync with the base currency row.
UPDATE settings SET setting_value = 'INR' WHERE setting_key = 'currency_code' AND (setting_value IS NULL OR setting_value = '');
UPDATE settings SET setting_value = '₹' WHERE setting_key = 'currency_symbol' AND (setting_value IS NULL OR setting_value = '' OR setting_value = '?');

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('currency', 'currency_auto_update', '1', NOW()),
('currency', 'currency_rate_provider', 'open.er-api.com', NOW()),
('currency', 'currency_rate_api_url', 'https://open.er-api.com/v6/latest/{base}', NOW()),
('currency', 'currency_rate_api_key_encrypted', '', NOW()),
('currency', 'currency_last_sync_at', '', NOW()),
('currency', 'currency_last_sync_status', '', NOW());

INSERT INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('system', 'schema_version', '003', NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW();
