import { getDb } from '../db/connection'
import type { Headline } from '../lib/distrowatch'

export function findLatest(): Headline[] {
  const db = getDb()
  const rows = db.query<Headline, []>(
    'SELECT * FROM headlines ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
