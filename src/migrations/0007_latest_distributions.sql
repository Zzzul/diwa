CREATE TABLE IF NOT EXISTS latest_distributions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,
  slug TEXT NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  version TEXT,
  download_url TEXT,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_latest_distributions_scraped_at ON latest_distributions (scraped_at DESC);
CREATE INDEX IF NOT EXISTS idx_latest_distributions_date ON latest_distributions (date DESC);
