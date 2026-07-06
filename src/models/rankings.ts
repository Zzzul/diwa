import { getDb } from '../db/connection'

export type Ranking = {
  id: string
  rank: number
  name: string
  slug: string
  based_on: string[]
  hpd: number | null
  yesterday: number | null
  trend: string | null
  scraped_at: string
  dataspan: string
}

type Row = Omit<Ranking, 'based_on'> & { based_on: string }

function hydrate(row: Row): Ranking {
  let based_on: string[]
  try {
    const v = JSON.parse(row.based_on)
    based_on = Array.isArray(v) ? v : []
  } catch {
    based_on = []
  }
  return { ...row, based_on }
}

export function findLatest(opts: { slug?: string; dataspan?: string }): Ranking[] {
  const db = getDb()
  const ds = opts.dataspan || '26'
  if (opts.slug) {
    const rows = db
      .query<Row, [string, string]>(
        'SELECT * FROM rankings WHERE slug = ? AND dataspan = ? ORDER BY scraped_at DESC, rank ASC'
      )
      .all(opts.slug, ds)
    return rows.map(hydrate)
  }
  const rows = db
    .query<Row, [string]>(
      'SELECT * FROM rankings WHERE dataspan = ? ORDER BY scraped_at DESC, rank ASC'
    )
    .all(ds)
  return rows.map(hydrate)
}

export function findBySlug(slug: string): Ranking[] {
  const db = getDb()
  const rows = db
    .query<Row, [string]>(
      'SELECT * FROM rankings WHERE slug = ? ORDER BY scraped_at DESC, rank ASC'
    )
    .all(slug)
  return rows.map(hydrate)
}
