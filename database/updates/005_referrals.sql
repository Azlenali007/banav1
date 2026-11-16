-- 005_referrals.sql
-- Refer & Earn: referral codes, attribution, reward ledger and settings.
-- Non-destructive & repeat-safe.

ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_code VARCHAR(30) NULL DEFAULT NULL AFTER preferred_currency;
ALTER TABLE users ADD COLUMN IF NOT EXISTS referred_by INT UNSIGNED NULL DEFAULT NULL AFTER referral_code;

SET @has_refcode_idx := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'uniq_users_referral_code'
);
SET @ddl_refcode := IF(@has_refcode_idx = 0,
    'ALTER TABLE users ADD UNIQUE KEY uniq_users_referral_code (referral_code)',
    'SELECT 1');
PREPARE stmt_refcode FROM @ddl_refcode;
EXECUTE stmt_refcode;
DEALLOCATE PREPARE stmt_refcode;

SET @has_referred_idx := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND index_name = 'idx_users_referred_by'
);
SET @ddl_referred := IF(@has_referred_idx = 0,
    'ALTER TABLE users ADD INDEX idx_users_referred_by (referred_by)',
    'SELECT 1');
PREPARE stmt_referred FROM @ddl_referred;
EXECUTE stmt_referred;
DEALLOCATE PREPARE stmt_referred;

CREATE TABLE IF NOT EXISTS referrals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    referrer_id INT UNSIGNED NOT NULL,
    referred_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,
    status ENUM('pending','approved','rejected','qualified') NOT NULL DEFAULT 'pending',
    qualifying_event VARCHAR(40) NULL DEFAULT NULL,
    reward_amount DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    admin_id INT UNSIGNED NULL DEFAULT NULL,
    note VARCHAR(255) NULL DEFAULT NULL,
    approved_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_referral_referred (referred_id),
    KEY idx_referral_referrer (referrer_id, status),
    KEY idx_referral_code (code),
    CONSTRAINT fk_referrals_referrer FOREIGN KEY (referrer_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_referrals_referred FOREIGN KEY (referred_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referral_rewards (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    referral_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reward_type VARCHAR(30) NOT NULL DEFAULT 'referral_bonus',
    amount DECIMAL(15,4) NOT NULL,
    status ENUM('pending','credited','reversed') NOT NULL DEFAULT 'credited',
    idempotency_key VARCHAR(128) NOT NULL,
    wallet_transaction_id BIGINT UNSIGNED NULL DEFAULT NULL,
    admin_id INT UNSIGNED NULL DEFAULT NULL,
    note VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_referral_reward_idem (idempotency_key),
    KEY idx_reward_referral (referral_id),
    KEY idx_reward_user (user_id),
    CONSTRAINT fk_reward_referral FOREIGN KEY (referral_id) REFERENCES referrals (id) ON DELETE CASCADE,
    CONSTRAINT fk_reward_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('referral', 'referral_enabled', '1', NOW()),
('referral', 'referral_reward_type', 'fixed', NOW()),
('referral', 'referral_reward_amount', '50.0000', NOW()),
('referral', 'referral_reward_percent', '5.0000', NOW()),
('referral', 'referral_qualifying_event', 'signup', NOW()),
('referral', 'referral_min_deposit', '0.0000', NOW()),
('referral', 'referral_reward_referee', '0.0000', NOW()),
('referral', 'referral_auto_approve', '1', NOW()),
('referral', 'referral_terms', 'Invite friends and earn a reward when they join and qualify.', NOW());

INSERT INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('system', 'schema_version', '005', NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW();
