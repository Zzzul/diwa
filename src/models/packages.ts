import { getDb } from '../db/connection'
import type { Package } from '../lib/distrowatch'

export function findLatest(): Package[] {
  const db = getDb()
  const rows = db.query<Package, []>(
    'SELECT * FROM packages ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
