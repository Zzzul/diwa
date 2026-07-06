import { getDb } from '../db/connection'
import type { LatestDist } from '../lib/distrowatch'

export type LatestDistribution = LatestDist

export function insertMany(items: LatestDist[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO latest_distributions (id, date, slug, name, description, version, download_url, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: LatestDist[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.slug, r.name, r.description, r.version, r.download_url, r.scraped_at)
    }
  })
  tx(items)
}

export function findLatest(limit = 50): LatestDistribution[] {
  const db = getDb()
  return db.query<any, [number]>(
    'SELECT * FROM latest_distributions ORDER BY scraped_at DESC, date DESC LIMIT ?'
  ).all(limit)
}
