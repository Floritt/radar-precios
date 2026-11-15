CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  name VARCHAR(64) NOT NULL PRIMARY KEY,
  value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS niches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  intro TEXT NULL,
  color VARCHAR(7) NOT NULL DEFAULT '#2546D8',
  sort_order INT NOT NULL DEFAULT 0,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  niche_id INT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  brand VARCHAR(120) NOT NULL DEFAULT '',
  summary TEXT NULL,
  image_path VARCHAR(255) NULL,
  asin VARCHAR(10) NULL,
  temu_url VARCHAR(500) NULL,
  shein_url VARCHAR(500) NULL,
  other_name VARCHAR(80) NULL,
  other_url VARCHAR(500) NULL,
  amazon_title VARCHAR(400) NULL,
  amazon_url VARCHAR(600) NULL,
  amazon_image_url VARCHAR(600) NULL,
  amazon_price DECIMAL(10,2) NULL,
  amazon_list_price DECIMAL(10,2) NULL,
  amazon_price_at DATETIME NULL,
  amazon_checked_at DATETIME NULL,
  amazon_error VARCHAR(255) NULL,
  best_price DECIMAL(10,2) NULL,
  best_store VARCHAR(16) NULL,
  avg90 DECIMAL(10,2) NULL,
  min180 DECIMAL(10,2) NULL,
  change30 DECIMAL(8,4) NULL,
  at_low TINYINT(1) NOT NULL DEFAULT 0,
  low_store VARCHAR(16) NULL,
  stats_store VARCHAR(16) NULL,
  track TINYINT(1) NOT NULL DEFAULT 1,
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_products_niche (niche_id),
  INDEX idx_products_status (status),
  INDEX idx_products_asin (asin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  store VARCHAR(16) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  source VARCHAR(12) NOT NULL DEFAULT 'api',
  captured_at DATETIME NOT NULL,
  INDEX idx_prices_lookup (product_id, store, captured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(12) NOT NULL DEFAULT 'blog',
  niche_id INT UNSIGNED NULL,
  product_id INT UNSIGNED NULL,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(240) NOT NULL UNIQUE,
  excerpt TEXT NULL,
  body MEDIUMTEXT NULL,
  cover_path VARCHAR(255) NULL,
  rating DECIMAL(3,1) NULL,
  pros TEXT NULL,
  cons TEXT NULL,
  verdict TEXT NULL,
  author VARCHAR(120) NOT NULL DEFAULT '',
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_posts_status (status, published_at),
  INDEX idx_posts_niche (niche_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clicks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  store VARCHAR(16) NOT NULL,
  page VARCHAR(16) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  INDEX idx_clicks_product (product_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  sent_at DATETIME NOT NULL,
  INDEX idx_tg_product (product_id, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_login_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
