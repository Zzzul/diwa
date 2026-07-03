CREATE TABLE IF NOT EXISTS rankings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  rank INTEGER NOT NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL,
  based_on TEXT NOT NULL DEFAULT '[]',
  hpd INTEGER,
  yesterday INTEGER,
  trend TEXT,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_rankings_scraped_at ON rankings (scraped_at DESC);

CREATE TABLE IF NOT EXISTS news (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT,
  is_new INTEGER DEFAULT 0,
  type TEXT,
  headline TEXT,
  headline_slug TEXT,
  headline_url TEXT,
  logo TEXT,
  screenshot TEXT,
  rating INTEGER,
  text TEXT,
  text_html TEXT,
  links TEXT DEFAULT '[]',
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_news_scraped_at ON news (scraped_at DESC);
