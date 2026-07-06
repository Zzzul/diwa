import { getDb } from '../db/connection'

export type SearchFilterRow = {
  category_name: string
  category_label: string
  value: string
  label: string
}

export function findSearchFilters(): SearchFilterRow[] {
  const db = getDb()
  return db.query<SearchFilterRow, []>(
    'SELECT category_name, category_label, value, label FROM search_filters ORDER BY rowid'
  ).all()
}

export function insertSearchFilters(items: (SearchFilterRow & { scraped_at: string })[]): void {
  const db = getDb()
  db.run('DELETE FROM search_filters')
  const stmt = db.prepare(
    'INSERT INTO search_filters (category_name, category_label, value, label, scraped_at) VALUES (?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: (SearchFilterRow & { scraped_at: string })[]) => {
    for (const r of rows) stmt.run(r.category_name, r.category_label, r.value, r.label, r.scraped_at)
  })
  tx(items)
}
