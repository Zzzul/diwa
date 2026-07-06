import { getDb } from '../db/connection'
import type { Newsletter } from '../lib/distrowatch'

export function findLatest(limit: number): Newsletter[] {
  const db = getDb()
  const rows = db.query<Newsletter, [number]>(
    'SELECT * FROM newsletters ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
