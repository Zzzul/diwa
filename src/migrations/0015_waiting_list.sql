CREATE TABLE IF NOT EXISTS waiting_list (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  name TEXT NOT NULL,
  url TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_waiting_list_scraped_at ON waiting_list (scraped_at DESC);
