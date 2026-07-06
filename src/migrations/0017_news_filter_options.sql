CREATE TABLE IF NOT EXISTS news_filter_options (
  category TEXT NOT NULL,
  value TEXT NOT NULL,
  label TEXT NOT NULL,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_news_filter_options_category ON news_filter_options (category);
