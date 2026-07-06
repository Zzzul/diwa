CREATE TABLE IF NOT EXISTS news_cache (
  cache_key TEXT PRIMARY KEY,
  params TEXT,
  results TEXT,
  scraped_at TEXT
);
