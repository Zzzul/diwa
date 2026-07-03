import { Database } from 'bun:sqlite'
import { mkdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'

export const DB_PATH = resolve(process.cwd(), 'data', 'app.db')

let instance: Database | null = null

export function getDb(): Database {
  if (instance) return instance
  mkdirSync(dirname(DB_PATH), { recursive: true })
  const db = new Database(DB_PATH, { create: true })
  db.exec('PRAGMA journal_mode = WAL')
  db.exec('PRAGMA synchronous = NORMAL')
  instance = db
  return db
}
