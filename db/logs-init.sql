CREATE DATABASE IF NOT EXISTS rex_logs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rex_logs;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS product_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
  action TINYINT UNSIGNED NOT NULL,
  actor_id INT,
  actor_role VARCHAR(20),
  actor_email_encrypted VARBINARY(255),
  entity_id INT NOT NULL,
  payload JSON,
  diff JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  correlation_id VARCHAR(64),
  INDEX idx_occurred (occurred_at),
  INDEX idx_entity (entity_id),
  INDEX idx_actor (actor_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaign_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
  action TINYINT UNSIGNED NOT NULL,
  actor_id INT,
  actor_role VARCHAR(20),
  actor_email_encrypted VARBINARY(255),
  entity_id INT NOT NULL,
  payload JSON,
  diff JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  correlation_id VARCHAR(64),
  INDEX idx_occurred (occurred_at),
  INDEX idx_entity (entity_id),
  INDEX idx_actor (actor_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
  action TINYINT UNSIGNED NOT NULL,
  actor_id INT,
  actor_role VARCHAR(20),
  actor_email_encrypted VARBINARY(255),
  entity_id VARCHAR(64) NOT NULL,
  payload JSON,
  diff JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  correlation_id VARCHAR(64),
  INDEX idx_occurred (occurred_at),
  INDEX idx_entity (entity_id),
  INDEX idx_actor (actor_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
  action TINYINT UNSIGNED NOT NULL,
  actor_id INT,
  actor_role VARCHAR(20),
  actor_email_encrypted VARBINARY(255),
  entity_id INT NOT NULL,
  payload JSON,
  diff JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  correlation_id VARCHAR(64),
  INDEX idx_occurred (occurred_at),
  INDEX idx_entity (entity_id),
  INDEX idx_actor (actor_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  occurred_at DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
  action TINYINT UNSIGNED NOT NULL,
  actor_id INT,
  actor_role VARCHAR(20),
  actor_email_encrypted VARBINARY(255),
  entity_id VARCHAR(255),
  payload JSON,
  diff JSON,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  correlation_id VARCHAR(64),
  INDEX idx_occurred (occurred_at),
  INDEX idx_actor (actor_id),
  INDEX idx_action (action)
) ENGINE=InnoDB;

-- Grant application user access to the logs database.
GRANT ALL PRIVILEGES ON rex_logs.* TO 'rex'@'%';
FLUSH PRIVILEGES;
