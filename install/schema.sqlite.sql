CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL DEFAULT '',
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS settings (
  name TEXT NOT NULL PRIMARY KEY,
  value TEXT NULL
);

CREATE TABLE IF NOT EXISTS niches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  intro TEXT NULL,
  color TEXT NOT NULL DEFAULT '#2546D8',
  sort_order INT NOT NULL DEFAULT 0,
  is_demo INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  niche_id INTEGER NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  brand TEXT NOT NULL DEFAULT '',
  summary TEXT NULL,
  image_path TEXT NULL,
  asin TEXT NULL,
  temu_url TEXT NULL,
  shein_url TEXT NULL,
  other_name TEXT NULL,
  other_url TEXT NULL,
  amazon_title TEXT NULL,
  amazon_url TEXT NULL,
  amazon_image_url TEXT NULL,
  amazon_price REAL NULL,
  amazon_list_price REAL NULL,
  amazon_price_at TEXT NULL,
  amazon_checked_at TEXT NULL,
  amazon_error TEXT NULL,
  best_price REAL NULL,
  best_store TEXT NULL,
  avg90 REAL NULL,
  min180 REAL NULL,
  change30 REAL NULL,
  at_low INTEGER NOT NULL DEFAULT 0,
  low_store TEXT NULL,
  stats_store TEXT NULL,
  track INTEGER NOT NULL DEFAULT 1,
  status TEXT NOT NULL DEFAULT 'draft',
  is_demo INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_products_niche ON products (niche_id);

CREATE INDEX IF NOT EXISTS idx_products_status ON products (status);

CREATE INDEX IF NOT EXISTS idx_products_asin ON products (asin);

CREATE TABLE IF NOT EXISTS prices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  store TEXT NOT NULL,
  price REAL NOT NULL,
  currency TEXT NOT NULL DEFAULT 'EUR',
  source TEXT NOT NULL DEFAULT 'api',
  captured_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_prices_lookup ON prices (product_id, store, captured_at);

CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL DEFAULT 'blog',
  niche_id INTEGER NULL,
  product_id INTEGER NULL,
  title TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  excerpt TEXT NULL,
  body TEXT NULL,
  cover_path TEXT NULL,
  rating REAL NULL,
  pros TEXT NULL,
  cons TEXT NULL,
  verdict TEXT NULL,
  author TEXT NOT NULL DEFAULT '',
  status TEXT NOT NULL DEFAULT 'draft',
  is_demo INTEGER NOT NULL DEFAULT 0,
  published_at TEXT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_posts_status ON posts (status, published_at);

CREATE INDEX IF NOT EXISTS idx_posts_niche ON posts (niche_id);

CREATE TABLE IF NOT EXISTS clicks (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  store TEXT NOT NULL,
  page TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_clicks_product ON clicks (product_id, created_at);

CREATE TABLE IF NOT EXISTS telegram_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL,
  price REAL NOT NULL,
  sent_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_tg_product ON telegram_log (product_id, sent_at);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ip_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_login_ip ON login_attempts (ip_hash, created_at);
