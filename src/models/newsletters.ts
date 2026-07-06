import { getDb } from '../db/connection'
import type { Newsletter } from '../lib/distrowatch'

export function findLatest(): Newsletter[] {
  const db = getDb()
  const rows = db.query<Newsletter, []>(
    'SELECT * FROM newsletters ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
