import { getDb } from '../db/connection'
import type { Addition } from '../lib/distrowatch'

export function findLatest(limit: number): Addition[] {
  const db = getDb()
  const rows = db.query<Addition, [number]>(
    'SELECT * FROM additions ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
