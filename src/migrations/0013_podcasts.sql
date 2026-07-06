CREATE TABLE IF NOT EXISTS podcasts (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  title TEXT NOT NULL,
  url TEXT NOT NULL,
  episode TEXT,
  episode_url TEXT,
  mp3_url TEXT,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_podcasts_scraped_at ON podcasts (scraped_at DESC);
