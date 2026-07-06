import { getDb } from '../db/connection'

export function findNewsCache(cacheKey: string): string | null {
  const db = getDb()
  const row = db.query<{ results: string }, [string]>(
    'SELECT results FROM news_cache WHERE cache_key = ?'
  ).get(cacheKey)
  return row ? row.results : null
}

export function insertNewsCache(cacheKey: string, params: string, results: string): void {
  const db = getDb()
  db.run(
    'INSERT OR REPLACE INTO news_cache (cache_key, params, results, scraped_at) VALUES (?, ?, ?, ?)',
    [cacheKey, params, results, new Date().toISOString()]
  )
}
