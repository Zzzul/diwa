import { getDb } from '../db/connection'
import type { Distribution } from '../lib/distrowatch'

const JSON_FIELDS: (keyof Distribution)[] = [
  'based_on', 'architecture', 'desktop', 'category', 'popularity',
  'reviews', 'where_to_donate', 'related_websites', 'reader_reviews',
  'recent_releases', 'recent_headlines',
]

function hydrate(row: any): Distribution {
  const out: any = { ...row }
  for (const field of JSON_FIELDS) {
    if (typeof out[field] === 'string') {
      try { out[field] = JSON.parse(out[field]) } catch { out[field] = field === 'popularity' ? { rank: null, hpd: null } : [] }
    }
  }
  return out as Distribution
}

export function findBySlug(slug: string): Distribution | null {
  const db = getDb()
  const row = db.query<any, [string]>('SELECT * FROM distributions WHERE slug = ?').get(slug)
  return row ? hydrate(row) : null
}

export function insert(data: Distribution): void {
  const db = getDb()
  const stmt = db.prepare(`
    INSERT INTO distributions (
      id, slug, name, logo, screenshot, last_update, os_type, based_on, origin,
      architecture, desktop, category, status, popularity, description,
      rating, reviews_count, home_page, user_forums, documentation,
      screenshots, download_mirrors, bug_tracker, reviews, where_to_donate,
      related_websites, reader_reviews, recent_releases, recent_headlines, scraped_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `)
  stmt.run(
    data.id!, data.slug, data.name, data.logo, data.screenshot, data.last_update,
    data.os_type, JSON.stringify(data.based_on), data.origin,
    JSON.stringify(data.architecture), JSON.stringify(data.desktop),
    JSON.stringify(data.category), data.status, JSON.stringify(data.popularity),
    data.description, data.rating, data.reviews_count,
    data.home_page, data.user_forums, data.documentation,
    data.screenshots, data.download_mirrors, data.bug_tracker,
    JSON.stringify(data.reviews), JSON.stringify(data.where_to_donate),
    JSON.stringify(data.related_websites), JSON.stringify(data.reader_reviews),
    JSON.stringify(data.recent_releases), JSON.stringify(data.recent_headlines), data.scraped_at,
  )
}
