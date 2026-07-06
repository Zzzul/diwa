import { getDb } from '../db/connection'
import type { WaitingListItem } from '../lib/distrowatch'

export function findLatest(limit: number): WaitingListItem[] {
  const db = getDb()
  const rows = db.query<WaitingListItem, [number]>(
    'SELECT * FROM waiting_list ORDER BY scraped_at DESC, position ASC LIMIT ?'
  ).all(limit)
  return rows
}
