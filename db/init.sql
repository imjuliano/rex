CREATE DATABASE IF NOT EXISTS rex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rex;

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Clean and recreate all demo tables so first boot always starts
-- with a consistent demo dataset.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS refresh_tokens;
DROP TABLE IF EXISTS wallet_entries;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS campaigns;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','seller') NOT NULL,
  deleted_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, password_hash, role) VALUES
('Admin Seed', 'admin@rex.test', '$2y$10$clOftVmnBE5Z8qm0cDTWdehyE1H9nN6HcfzWcxw7hEkjHLSfiT6dO', 'admin'),
('Seller One', 'seller1@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller Two', 'seller2@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 03', 'seller03@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 04', 'seller04@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 05', 'seller05@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 06', 'seller06@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 07', 'seller07@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 08', 'seller08@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 09', 'seller09@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 10', 'seller10@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 11', 'seller11@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 12', 'seller12@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 13', 'seller13@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller'),
('Seller 14', 'seller14@rex.test', '$2y$10$7cmYN3MaZNQSZ0coW/3g4eoJDToDrzDbSOpfP8FsGV077IIwyJx4K', 'seller');

-- Refresh tokens are stored as SHA-256 hashes: a database dump alone is not
-- enough to impersonate a session. `family_id` groups every token descended
-- from one login, so a single reuse can revoke the whole chain at once.
CREATE TABLE refresh_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  family_id CHAR(32) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  revoked_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_refresh_tokens_family (family_id),
  INDEX idx_refresh_tokens_user (user_id),
  INDEX idx_refresh_tokens_expires (expires_at)
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  sku VARCHAR(100) NOT NULL UNIQUE,
  points_per_unit INT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  deleted_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_points_per_unit CHECK (points_per_unit >= 0)
);

INSERT INTO products (name, sku, points_per_unit, active) VALUES
('Produto 001', 'PROD-001', 15, 1),
('Produto 002', 'PROD-002', 20, 1),
('Produto 003', 'PROD-003', 25, 1),
('Produto 004', 'PROD-004', 30, 1),
('Produto 005', 'PROD-005', 35, 1),
('Produto 006', 'PROD-006', 40, 1),
('Produto 007', 'PROD-007', 45, 1),
('Produto 008', 'PROD-008', 50, 1),
('Produto 009', 'PROD-009', 55, 1),
('Produto 010', 'PROD-010', 60, 1),
('Produto 011', 'PROD-011', 65, 1),
('Produto 012', 'PROD-012', 70, 1),
('Produto 013', 'PROD-013', 75, 1),
('Produto 014', 'PROD-014', 80, 1),
('Produto 015', 'PROD-015', 85, 1);

CREATE TABLE campaigns (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  budget_total INT NOT NULL,
  budget_used INT NOT NULL DEFAULT 0,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_budget_total CHECK (budget_total >= 0),
  CONSTRAINT chk_budget_used CHECK (budget_used >= 0)
);

INSERT INTO campaigns (name, budget_total, budget_used, starts_at, ends_at, status) VALUES
('Campanha 01', 1500, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 02', 2000, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 03', 2500, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 04', 3000, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 05', 3500, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 06', 4000, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 07', 4500, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 08', 5000, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 09', 5500, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 10', 6000, 0, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 11', 6500, 0, '2026-06-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 12', 7000, 0, '2026-06-01 00:00:00', '2026-12-31 23:59:59', 'active'),
('Campanha 13', 7500, 0, '2026-06-01 00:00:00', '2026-12-31 23:59:59', 'closed'),
('Campanha 14', 8000, 0, '2026-06-01 00:00:00', '2026-12-31 23:59:59', 'closed'),
('Campanha 15', 8500, 0, '2026-06-01 00:00:00', '2026-12-31 23:59:59', 'closed');

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  external_id VARCHAR(255) NOT NULL UNIQUE,
  campaign_id INT NOT NULL,
  seller_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_value DECIMAL(10,2) NOT NULL,
  status ENUM('approved','canceled') NOT NULL DEFAULT 'approved',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_quantity CHECK (quantity > 0),
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  FOREIGN KEY (seller_id) REFERENCES users(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

INSERT INTO sales (external_id, campaign_id, seller_id, product_id, quantity, unit_value, status) VALUES
('VENDA-2026-001', 1, 3, 1, 1, 75.00, 'approved'),
('VENDA-2026-002', 2, 2, 2, 2, 100.00, 'approved'),
('VENDA-2026-003', 3, 3, 3, 3, 125.00, 'approved'),
('VENDA-2026-004', 4, 2, 4, 4, 150.00, 'approved'),
('VENDA-2026-005', 5, 3, 5, 5, 175.00, 'approved'),
('VENDA-2026-006', 6, 2, 6, 6, 200.00, 'approved'),
('VENDA-2026-007', 7, 3, 7, 7, 225.00, 'approved'),
('VENDA-2026-008', 8, 2, 8, 8, 250.00, 'approved'),
('VENDA-2026-009', 9, 3, 9, 9, 275.00, 'approved'),
('VENDA-2026-010', 10, 2, 10, 10, 300.00, 'approved'),
('VENDA-2026-011', 11, 3, 11, 1, 325.00, 'approved'),
('VENDA-2026-012', 12, 2, 12, 2, 350.00, 'approved'),
('VENDA-2026-013', 13, 3, 13, 3, 375.00, 'approved'),
('VENDA-2026-014', 14, 2, 14, 4, 400.00, 'approved'),
('VENDA-2026-015', 15, 3, 15, 5, 425.00, 'approved');

CREATE TABLE wallet_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  campaign_id INT NOT NULL,
  sale_id INT NOT NULL,
  type ENUM('credit','debit') NOT NULL,
  points INT NOT NULL,
  description VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES users(id),
  FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  FOREIGN KEY (sale_id) REFERENCES sales(id)
);

INSERT INTO wallet_entries (seller_id, campaign_id, sale_id, type, points, description)
SELECT
  s.seller_id,
  s.campaign_id,
  s.id,
  'credit',
  (s.quantity * p.points_per_unit),
  CONCAT('Sale ', s.external_id)
FROM sales s
JOIN products p ON p.id = s.product_id
WHERE s.status = 'approved';

UPDATE campaigns c
SET c.budget_used = (
  SELECT COALESCE(SUM(w.points), 0)
  FROM wallet_entries w
  WHERE w.campaign_id = c.id AND w.type = 'credit'
);

