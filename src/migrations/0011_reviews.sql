CREATE TABLE IF NOT EXISTS reviews (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  title TEXT NOT NULL,
  url TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_reviews_scraped_at ON reviews (scraped_at DESC);
