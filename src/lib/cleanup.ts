import { getDb } from '../db/connection'

const TABLES = ['rankings', 'news', 'random_distributions', 'latest_distributions']

export function cleanupOldData(): void {
  const db = getDb()
  const cutoff = new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString()
  for (const table of TABLES) {
    const { changes } = db.query(`DELETE FROM ${table} WHERE scraped_at < ?`).run(cutoff)
    if (changes > 0) console.log(`[cleanup] ${table}: ${changes} rows deleted`)
  }
}
