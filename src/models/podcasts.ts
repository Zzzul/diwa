import { getDb } from '../db/connection'
import type { Podcast } from '../lib/distrowatch'

export function findLatest(): Podcast[] {
  const db = getDb()
  const rows = db.query<Podcast, []>(
    'SELECT * FROM podcasts ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
