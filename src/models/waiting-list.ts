import { getDb } from '../db/connection'
import type { WaitingListItem } from '../lib/distrowatch'

export function findLatest(): WaitingListItem[] {
  const db = getDb()
  const rows = db.query<WaitingListItem, []>(
    'SELECT * FROM waiting_list ORDER BY scraped_at DESC, position ASC'
  ).all()
  return rows
}
