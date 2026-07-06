import { getDb } from '../db/connection'

export type RelatedNewsItem = {
  news_id: string
  headline: string
  url: string
}

export type NewsDetail = {
  newsid: string
  date: string | null
  headline: string | null
  headline_url: string | null
  type: string | null
  logo: string | null
  screenshot: string | null
  rating: number | null
  text: string | null
  text_html: string | null
  distribution_slug: string | null
  distribution_summary: Record<string, string> | null
  related_news: RelatedNewsItem[] | null
  about: string | null
  scraped_at: string | null
}

type Row = Omit<NewsDetail, 'related_news' | 'distribution_summary'> & { related_news: string | null; distribution_summary: string | null }

function hydrate(row: Row): NewsDetail {
  let related: RelatedNewsItem[] | null = null
  if (row.related_news) {
    try {
      const v = JSON.parse(row.related_news)
      related = Array.isArray(v) ? v : null
    } catch {
      related = null
    }
  }
  let summary: Record<string, string> | null = null
  if (row.distribution_summary) {
    try {
      const v = JSON.parse(row.distribution_summary)
      summary = typeof v === 'object' && v !== null ? v : null
    } catch {
      summary = null
    }
  }
  const { distribution_name: _, ...r } = row as any
  return { ...r, related_news: related, distribution_summary: summary }
}

export function findNewsDetail(newsid: string): NewsDetail | null {
  const db = getDb()
  const row = db.query<Row, [string]>(
    'SELECT * FROM news_detail WHERE newsid = ?'
  ).get(newsid)
  return row ? hydrate(row) : null
}

export function insertNewsDetail(item: NewsDetail): void {
  const db = getDb()
  const stmt = db.prepare(
    `INSERT OR REPLACE INTO news_detail (newsid, date, headline, headline_url, type, logo, screenshot, rating, text, text_html, distribution_slug, distribution_summary, related_news, about, scraped_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
  )
  stmt.run(item.newsid, item.date, item.headline, item.headline_url, item.type,
    item.logo, item.screenshot, item.rating, item.text, item.text_html,
    item.distribution_slug,
    item.distribution_summary ? JSON.stringify(item.distribution_summary) : null,
    item.related_news ? JSON.stringify(item.related_news) : null,
    item.about, item.scraped_at)
}
