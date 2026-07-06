import { getDb } from '../db/connection'

export type WeeklySection = {
  id: string | null
  title: string
  content_html: string
  content_text: string
}

export type WeeklyIssue = {
  issue: string
  issue_number: number | null
  title: string | null
  date: string | null
  sections: WeeklySection[]
  scraped_at: string | null
}

type Row = Omit<WeeklyIssue, 'sections'> & { sections: string }

function hydrate(row: Row): WeeklyIssue {
  let sections: WeeklySection[] = []
  try {
    const v = JSON.parse(row.sections)
    sections = Array.isArray(v) ? v : []
  } catch {
    sections = []
  }
  return { ...row, sections }
}

export function findWeeklyIssue(issue: string): WeeklyIssue | null {
  const db = getDb()
  const row = db.query<Row, [string]>(
    'SELECT * FROM weekly_issues WHERE issue = ?'
  ).get(issue)
  return row ? hydrate(row) : null
}

export function insertWeeklyIssue(item: WeeklyIssue): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT OR REPLACE INTO weekly_issues (issue, issue_number, title, date, sections, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  stmt.run(item.issue, item.issue_number, item.title, item.date,
    JSON.stringify(item.sections), item.scraped_at)
}
