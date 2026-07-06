import { getDb } from '../db/connection'

export function findSearchResults(cacheKey: string): string | null {
  const db = getDb()
  const row = db.query<{ results: string }, [string]>(
    'SELECT results FROM search_results WHERE cache_key = ?'
  ).get(cacheKey)
  return row ? row.results : null
}

export function insertSearchResults(cacheKey: string, params: string, results: string): void {
  const db = getDb()
  db.run(
    'INSERT OR REPLACE INTO search_results (cache_key, params, results, scraped_at) VALUES (?, ?, ?, ?)',
    [cacheKey, params, results, new Date().toISOString()]
  )
}
