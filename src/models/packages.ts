import { getDb } from '../db/connection'
import type { Package } from '../lib/distrowatch'

export function findLatest(limit: number): Package[] {
  const db = getDb()
  const rows = db.query<Package, [number]>(
    'SELECT * FROM packages ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
