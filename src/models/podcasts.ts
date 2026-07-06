import { getDb } from '../db/connection'
import type { Podcast } from '../lib/distrowatch'

export function findLatest(limit: number): Podcast[] {
  const db = getDb()
  const rows = db.query<Podcast, [number]>(
    'SELECT * FROM podcasts ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
