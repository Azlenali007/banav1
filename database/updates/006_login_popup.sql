-- 006_login_popup.sql
-- Post-login WhatsApp / Telegram announcement popup configuration and views.
-- Non-destructive & repeat-safe.

CREATE TABLE IF NOT EXISTS login_popup_views (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    shown_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_popup_view_user (user_id, shown_at),
    CONSTRAINT fk_popup_view_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('popup', 'login_popup_enabled', '0', NOW()),
('popup', 'login_popup_title', 'Join our community', NOW()),
('popup', 'login_popup_message', 'Get order updates, exclusive offers and instant support on WhatsApp and Telegram.', NOW()),
('popup', 'login_popup_image_url', '', NOW()),
('popup', 'login_popup_whatsapp_enabled', '1', NOW()),
('popup', 'login_popup_whatsapp_url', '', NOW()),
('popup', 'login_popup_whatsapp_label', 'Join WhatsApp Channel', NOW()),
('popup', 'login_popup_telegram_enabled', '1', NOW()),
('popup', 'login_popup_telegram_url', '', NOW()),
('popup', 'login_popup_telegram_label', 'Join Telegram Channel', NOW()),
('popup', 'login_popup_frequency', 'once_per_session', NOW()),
('popup', 'login_popup_frequency_days', '7', NOW()),
('popup', 'login_popup_delay_seconds', '0', NOW());

INSERT INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('system', 'schema_version', '006', NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW();
