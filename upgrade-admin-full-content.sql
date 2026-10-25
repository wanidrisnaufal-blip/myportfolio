USE `myportfolio_db`;

-- Existing dump already has site_content, but keep this safe for older installs too.
CREATE TABLE IF NOT EXISTS `site_content` (
  `content_key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `content_value` TEXT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add the logo fields needed by the Admin dashboard only when they are missing.
SET @admin_upgrade_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'timeline' AND COLUMN_NAME = 'logo_url') = 0,
  'ALTER TABLE `timeline` ADD COLUMN `logo_url` VARCHAR(500) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE admin_upgrade_stmt FROM @admin_upgrade_sql;
EXECUTE admin_upgrade_stmt;
DEALLOCATE PREPARE admin_upgrade_stmt;

SET @admin_upgrade_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'skill_categories' AND COLUMN_NAME = 'logo_1_url') = 0,
  'ALTER TABLE `skill_categories` ADD COLUMN `logo_1_url` VARCHAR(500) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE admin_upgrade_stmt FROM @admin_upgrade_sql;
EXECUTE admin_upgrade_stmt;
DEALLOCATE PREPARE admin_upgrade_stmt;

SET @admin_upgrade_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'skill_categories' AND COLUMN_NAME = 'logo_2_url') = 0,
  'ALTER TABLE `skill_categories` ADD COLUMN `logo_2_url` VARCHAR(500) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE admin_upgrade_stmt FROM @admin_upgrade_sql;
EXECUTE admin_upgrade_stmt;
DEALLOCATE PREPARE admin_upgrade_stmt;

SET @admin_upgrade_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'skill_categories' AND COLUMN_NAME = 'logo_3_url') = 0,
  'ALTER TABLE `skill_categories` ADD COLUMN `logo_3_url` VARCHAR(500) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE admin_upgrade_stmt FROM @admin_upgrade_sql;
EXECUTE admin_upgrade_stmt;
DEALLOCATE PREPARE admin_upgrade_stmt;
