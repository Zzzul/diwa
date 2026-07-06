import { getDb } from '../db/connection'

export type NewsLink = {
  url: string | null
  text: string
  href: string | null
}

export type News = {
  id: string
  date: string | null
  is_new: boolean | null
  type: string | null
  headline: string | null
  headline_slug: string | null
  headline_url: string | null
  logo: string | null
  screenshot: string | null
  rating: number | null
  text: string | null
  text_html: string | null
  links: NewsLink[]
  scraped_at: string
}

type Row = Omit<News, 'links' | 'is_new'> & { links: string; is_new: number | null }

function hydrate(row: Row): News {
  let links: NewsLink[]
  try {
    const v = JSON.parse(row.links)
    links = Array.isArray(v) ? v : []
  } catch {
    links = []
  }
  return { ...row, links, is_new: row.is_new ? true : false }
}

export function findLatest(opts: {
  type?: string
  date?: string
}): News[] {
  const db = getDb()
  const where: string[] = []
  const params: string[] = []
  if (opts.type) {
    where.push('type = ?')
    params.push(opts.type)
  }
  if (opts.date) {
    where.push('date = ?')
    params.push(opts.date)
  }
  const whereSql = where.length ? `WHERE ${where.join(' AND ')}` : ''
  const sql = `SELECT * FROM news ${whereSql} ORDER BY scraped_at DESC, id ASC`
  const rows = db.query<Row, [...string[]]>(sql).all(...params)
  return rows.map(hydrate)
}

export function findById(id: string): News | null {
  const db = getDb()
  const row = db.query<Row, [string]>('SELECT * FROM news WHERE id = ?').get(id)
  return row ? hydrate(row) : null
}
