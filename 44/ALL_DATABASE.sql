-- Database schema voor volledige installatie
-- 1. Gebruikers en devices
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `wg_public_key` VARCHAR(255) NOT NULL,
  `wg_ip` VARCHAR(50) NOT NULL,
  `status` ENUM('active', 'inactive', 'pending', 'blocked') DEFAULT 'active',
  `auto_created` TINYINT(1) DEFAULT 0,
  `permanent_blocked` TINYINT(1) DEFAULT 0,
  `admin_created` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_auto_created` (`auto_created`),
  INDEX `idx_permanent_blocked` (`permanent_blocked`),
  INDEX `idx_admin_created` (`admin_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Whitelist
CREATE TABLE IF NOT EXISTS `whitelist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` INT UNSIGNED NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `comment` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,
  INDEX `idx_device_id` (`device_id`),
  INDEX `idx_domain` (`domain`),
  UNIQUE KEY `unique_device_domain` (`device_id`, `domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Blocklists
CREATE TABLE IF NOT EXISTS `blocklist_global` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain` VARCHAR(255) NOT NULL UNIQUE,
  `category` VARCHAR(50) DEFAULT 'pornography',
  `source` VARCHAR(100) DEFAULT 'manual',
  `enabled` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_domain` (`domain`),
  INDEX `idx_category` (`category`),
  INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blocklist_device` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` INT UNSIGNED NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'pornography',
  `enabled` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE,
  INDEX `idx_device_id` (`device_id`),
  INDEX `idx_domain` (`domain`),
  UNIQUE KEY `unique_device_domain` (`device_id`, `domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blocklist_permanent` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain` VARCHAR(255) NOT NULL UNIQUE,
  `category` VARCHAR(50) DEFAULT 'pornography',
  `source` VARCHAR(100) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_domain` (`domain`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blocklist_subscription` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `domain` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'pornography',
  `source` VARCHAR(100) DEFAULT 'subscription',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_domain` (`domain`),
  INDEX `idx_category` (`category`),
  UNIQUE KEY `unique_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Password reset
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Subscriptions
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `max_devices` INT UNSIGNED NOT NULL,
  `price_monthly` DECIMAL(10,2) DEFAULT 0.00,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subscription_plans` (`name`, `max_devices`, `price_monthly`, `description`) VALUES
('basic', 2, 9.99, 'Basis plan - 2 devices'),
('family', 5, 19.99, 'Family plan - 5 devices'),
('premium', 10, 29.99, 'Premium plan - 10 devices')
ON DUPLICATE KEY UPDATE `name`=`name`;

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `plan` VARCHAR(50) DEFAULT 'basic',
  `status` ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'pending',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `stripe_subscription_id` VARCHAR(255) DEFAULT NULL,
  `stripe_customer_id` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_end_date` (`end_date`),
  INDEX `idx_stripe_subscription_id` (`stripe_subscription_id`),
  INDEX `idx_stripe_customer_id` (`stripe_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Activity logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `device_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'blocked, allowed, login, logout, device_added, etc.',
  `domain` VARCHAR(255) DEFAULT NULL,
  `url` TEXT DEFAULT NULL,
  `reason` VARCHAR(100) DEFAULT NULL COMMENT 'permanent_blocklist, global_blocklist, keyword_detection, etc.',
  `category` VARCHAR(50) DEFAULT NULL COMMENT 'pornography, gambling, etc.',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE SET NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_device_id` (`device_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_domain` (`domain`(100)),
  INDEX `idx_reason` (`reason`),
  INDEX `idx_category` (`category`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_created_at_date` ON `activity_logs` (`created_at`, `action`);
