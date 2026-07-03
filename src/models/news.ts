import { getDb } from '../db/connection'

export type News = {
  id: number
  date: string | null
  is_new: number | null
  type: string | null
  headline: string | null
  headline_slug: string | null
  headline_url: string | null
  logo: string | null
  screenshot: string | null
  rating: number | null
  text: string | null
  text_html: string | null
  links: string[]
  scraped_at: string
}

type Row = Omit<News, 'links'> & { links: string }

function hydrate(row: Row): News {
  let links: string[]
  try {
    const v = JSON.parse(row.links)
    links = Array.isArray(v) ? v : []
  } catch {
    links = []
  }
  return { ...row, links }
}

export function findLatest(opts: {
  limit: number
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
  const sql = `SELECT * FROM news ${whereSql} ORDER BY scraped_at DESC, id DESC LIMIT ?`
  const rows = db.query<Row, [...string[], number]>(sql).all(...params, opts.limit)
  return rows.map(hydrate)
}

export function findById(id: number): News | null {
  const db = getDb()
  const row = db.query<Row, [number]>('SELECT * FROM news WHERE id = ?').get(id)
  return row ? hydrate(row) : null
}
