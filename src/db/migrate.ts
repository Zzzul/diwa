import { readdirSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { getDb } from './connection'

const MIGRATIONS_DIR = resolve(import.meta.dir, '..', 'migrations')
const NAME_RE = /^(\d+)_([a-z0-9_]+)\.sql$/

function ensureMigrationsTable() {
  const db = getDb()
  db.exec(
    'CREATE TABLE IF NOT EXISTS _migrations (version INTEGER PRIMARY KEY, name TEXT NOT NULL, applied_at TEXT NOT NULL)'
  )
  return db
}

function appliedVersions(db: ReturnType<typeof getDb>): Set<number> {
  const rows = db.query<{ version: number }, []>('SELECT version FROM _migrations').all()
  return new Set(rows.map((r) => r.version))
}

function run() {
  const db = ensureMigrationsTable()
  const applied = appliedVersions(db)

  const files = readdirSync(MIGRATIONS_DIR)
    .filter((f) => f.endsWith('.sql'))
    .sort()

  let count = 0
  for (const file of files) {
    const match = NAME_RE.exec(file)
    if (!match) {
      console.error(`[migrate] skip (bad name): ${file}`)
      continue
    }
    const version = Number(match[1])
    const name = match[2]
    if (applied.has(version)) {
      console.log(`[migrate] skip (applied): ${file}`)
      continue
    }

    const sql = readFileSync(resolve(MIGRATIONS_DIR, file), 'utf8')
    db.exec('BEGIN')
    try {
      db.exec(sql)
      db.query(
        'INSERT INTO _migrations (version, name, applied_at) VALUES (?, ?, ?)'
      ).run(version, name, new Date().toISOString())
      db.exec('COMMIT')
      console.log(`[migrate] applied: ${file}`)
      count++
    } catch (err) {
      db.exec('ROLLBACK')
      console.error(`[migrate] failed: ${file}`)
      throw err
    }
  }

  console.log(`[migrate] done (${count} applied)`)
}

run()
