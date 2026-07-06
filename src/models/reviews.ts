import { getDb } from '../db/connection'
import type { Review } from '../lib/distrowatch'

export function findLatest(limit: number): Review[] {
  const db = getDb()
  const rows = db.query<Review, [number]>(
    'SELECT * FROM reviews ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
