import { getDb } from '../db/connection'
import type { DistroItem } from '../lib/distrowatch'

export function findAll(): DistroItem[] {
  const db = getDb()
  return db.query<DistroItem, []>('SELECT id, slug, name FROM distributions_list ORDER BY name ASC').all()
}

export function insertMany(items: DistroItem[]): void {
  const db = getDb()
  db.exec('DELETE FROM distributions_list')
  const stmt = db.prepare('INSERT INTO distributions_list (id, slug, name, scraped_at) VALUES (?, ?, ?, ?)')
  const now = new Date().toISOString()
  const tx = db.transaction((rows: DistroItem[]) => {
    for (const r of rows) {
      stmt.run(r.id!, r.slug, r.name, now)
    }
  })
  tx(items)
}
