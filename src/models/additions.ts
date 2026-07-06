import { getDb } from '../db/connection'
import type { Addition } from '../lib/distrowatch'

export function findLatest(): Addition[] {
  const db = getDb()
  const rows = db.query<Addition, []>(
    'SELECT * FROM additions ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
