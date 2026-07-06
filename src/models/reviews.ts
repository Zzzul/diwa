import { getDb } from '../db/connection'
import type { Review } from '../lib/distrowatch'

export function findLatest(): Review[] {
  const db = getDb()
  const rows = db.query<Review, []>(
    'SELECT * FROM reviews ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
