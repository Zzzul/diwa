import { getDb } from './connection'

function run() {
  const db = getDb()

  const tables = db
    .query<{ name: string }, []>(
      "SELECT name FROM sqlite_master WHERE type='table' AND name NOT IN ('_migrations', 'sqlite_sequence')"
    )
    .all()

  if (tables.length > 0) {
    for (const t of tables) {
      db.exec(`DROP TABLE IF EXISTS "${t.name}"`)
    }
    console.log(`[fresh] dropped: ${tables.map((t) => t.name).join(', ')}`)
  } else {
    console.log('[fresh] no tables to drop')
  }

  db.exec('DROP TABLE IF EXISTS _migrations')
  console.log('[fresh] _migrations dropped')
}

run()
