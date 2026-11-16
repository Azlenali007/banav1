-- 004_bulk_operations.sql
-- Support for safe bulk service operations. Deleting a service that is
-- referenced by orders archives it instead (soft delete) so historical order
-- records stay intact. Non-destructive & repeat-safe.

ALTER TABLE services ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL DEFAULT NULL AFTER status;

SET @has_idx := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'services'
      AND index_name = 'idx_services_bulk'
);
SET @ddl := IF(@has_idx = 0,
    'ALTER TABLE services ADD INDEX idx_services_bulk (status, category_id, archived_at)',
    'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Helpful index for bulk duplicate detection during provider imports.
SET @has_idx2 := (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'provider_service_mapping'
      AND index_name = 'idx_psm_provider_service'
);
SET @ddl2 := IF(@has_idx2 = 0,
    'ALTER TABLE provider_service_mapping ADD INDEX idx_psm_provider_service (provider_id, provider_service_id)',
    'SELECT 1');
PREPARE stmt2 FROM @ddl2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

INSERT INTO settings (setting_group, setting_key, setting_value, updated_at) VALUES
('system', 'schema_version', '004', NOW())
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW();
