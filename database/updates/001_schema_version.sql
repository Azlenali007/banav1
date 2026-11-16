-- Migration 001: Ensure schema version setting exists
INSERT INTO `settings` (`setting_group`, `setting_key`, `setting_value`)
VALUES ('system', 'schema_version', '1.0.0')
ON DUPLICATE KEY UPDATE `setting_value` = '1.0.0';
