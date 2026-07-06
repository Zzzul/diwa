CREATE TABLE IF NOT EXISTS headlines (
  id TEXT PRIMARY KEY,
  story_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  url TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_headlines_scraped_at ON headlines (scraped_at DESC);
