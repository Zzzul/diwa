CREATE TABLE IF NOT EXISTS newsletters (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  title TEXT NOT NULL,
  url TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_newsletters_scraped_at ON newsletters (scraped_at DESC);
