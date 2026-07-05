import { getDb } from '../db/connection'
import type { Distribution } from '../lib/distrowatch'

export function insert(data: Distribution): void {
  const db = getDb()
  db.query(
    `INSERT INTO random_distributions (
      slug, name, logo, screenshot, last_update, os_type, based_on, origin,
      architecture, desktop, category, status, popularity, description,
      rating, reviews_count, home_page, user_forums, documentation,
      screenshots, download_mirrors, bug_tracker, reviews,
      where_to_donate, related_websites, reader_reviews,
      recent_releases, recent_headlines, scraped_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
  ).run(
    data.slug, data.name, data.logo, data.screenshot, data.last_update,
    data.os_type, JSON.stringify(data.based_on), data.origin,
    JSON.stringify(data.architecture), JSON.stringify(data.desktop),
    JSON.stringify(data.category), data.status, JSON.stringify(data.popularity),
    data.description, data.rating, data.reviews_count, data.home_page,
    data.user_forums, data.documentation, data.screenshots,
    data.download_mirrors, data.bug_tracker, JSON.stringify(data.reviews),
    JSON.stringify(data.where_to_donate), JSON.stringify(data.related_websites),
    JSON.stringify(data.reader_reviews), JSON.stringify(data.recent_releases),
    JSON.stringify(data.recent_headlines), data.scraped_at
  )
}

export function findLatest(limit = 10): Distribution[] {
  const db = getDb()
  const rows = db.query<any[], [number]>(
    'SELECT * FROM random_distributions ORDER BY scraped_at DESC LIMIT ?'
  ).all(limit)
  return rows.map(hydrate)
}

function hydrate(row: any): Distribution {
  return {
    ...row,
    based_on: JSON.parse(row.based_on || '[]'),
    architecture: JSON.parse(row.architecture || '[]'),
    desktop: JSON.parse(row.desktop || '[]'),
    category: JSON.parse(row.category || '[]'),
    popularity: JSON.parse(row.popularity || '{}'),
    reviews: JSON.parse(row.reviews || '[]'),
    where_to_donate: JSON.parse(row.where_to_donate || '[]'),
    related_websites: JSON.parse(row.related_websites || '[]'),
    reader_reviews: JSON.parse(row.reader_reviews || '[]'),
    recent_releases: JSON.parse(row.recent_releases || '[]'),
    recent_headlines: JSON.parse(row.recent_headlines || '[]'),
  }
}
