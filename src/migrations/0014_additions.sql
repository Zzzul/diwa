CREATE TABLE IF NOT EXISTS additions (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_additions_scraped_at ON additions (scraped_at DESC);
