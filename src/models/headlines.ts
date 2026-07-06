import { getDb } from '../db/connection'
import type { Headline } from '../lib/distrowatch'

export function findLatest(limit: number): Headline[] {
  const db = getDb()
  const rows = db.query<Headline, [number]>(
    'SELECT * FROM headlines ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
