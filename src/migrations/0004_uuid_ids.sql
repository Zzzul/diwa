DROP TABLE IF EXISTS rankings;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS distributions;
DROP TABLE IF EXISTS distributions_list;

CREATE TABLE IF NOT EXISTS rankings (
  id TEXT PRIMARY KEY,
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
  id TEXT PRIMARY KEY,
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

CREATE TABLE IF NOT EXISTS distributions (
  id TEXT PRIMARY KEY,
  slug TEXT NOT NULL UNIQUE,
  name TEXT,
  logo TEXT,
  screenshot TEXT,
  last_update TEXT,
  os_type TEXT,
  based_on TEXT DEFAULT '[]',
  origin TEXT,
  architecture TEXT DEFAULT '[]',
  desktop TEXT DEFAULT '[]',
  category TEXT DEFAULT '[]',
  status TEXT,
  popularity TEXT DEFAULT '{}',
  description TEXT,
  rating REAL,
  reviews_count INTEGER,
  home_page TEXT,
  user_forums TEXT,
  documentation TEXT,
  screenshots TEXT,
  download_mirrors TEXT,
  bug_tracker TEXT,
  reviews TEXT DEFAULT '[]',
  where_to_donate TEXT DEFAULT '[]',
  related_websites TEXT DEFAULT '[]',
  reader_reviews TEXT DEFAULT '[]',
  recent_releases TEXT DEFAULT '[]',
  recent_headlines TEXT DEFAULT '[]',
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_distributions_slug ON distributions (slug);

CREATE TABLE IF NOT EXISTS distributions_list (
  id TEXT PRIMARY KEY,
  slug TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_distributions_list_slug ON distributions_list (slug);
