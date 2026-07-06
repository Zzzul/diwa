CREATE TABLE IF NOT EXISTS weekly_issues (
  issue TEXT PRIMARY KEY,
  issue_number INTEGER,
  title TEXT,
  date TEXT,
  sections TEXT,
  scraped_at TEXT
);
