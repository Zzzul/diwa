import { getDb } from '../db/connection'

export type FilterOption = {
  category: string
  value: string
  label: string
}

export function findNewsFilterOptions(): FilterOption[] {
  const db = getDb()
  return db.query<FilterOption, []>(
    'SELECT category, value, label FROM news_filter_options ORDER BY rowid'
  ).all()
}
