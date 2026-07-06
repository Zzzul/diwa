CREATE TABLE IF NOT EXISTS search_results (
  cache_key TEXT PRIMARY KEY,
  params TEXT,
  results TEXT,
  scraped_at TEXT
);
