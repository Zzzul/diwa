ALTER TABLE news ADD COLUMN distribution TEXT;
ALTER TABLE news ADD COLUMN release_type TEXT;
ALTER TABLE news ADD COLUMN month TEXT;
ALTER TABLE news ADD COLUMN year TEXT;

CREATE INDEX IF NOT EXISTS idx_news_filters ON news (distribution, release_type, month, year);
