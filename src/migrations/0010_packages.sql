CREATE TABLE IF NOT EXISTS packages (
  id TEXT PRIMARY KEY,
  date TEXT NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  package_url TEXT NOT NULL,
  version TEXT NOT NULL,
  download_url TEXT NOT NULL,
  position INTEGER NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_packages_scraped_at ON packages (scraped_at DESC);
