# Diwa MVC Scaffold — Design

**Date:** 2026-07-03
**Status:** Approved (pending written-spec review)
**Project:** `/home/zzzul/experiment/js/hono/diwa`

## Purpose

Scaffold a Hono + bun:sqlite API for the `diwa` rankings/news scraper. Establish
MVC folder layout, a raw-SQL migration runner, typed model fns, per-resource
routing, and a JSON-only API surface. No scraping logic, no write endpoints, no
auth in this spec.

## Stack

- Runtime: bun
- HTTP: hono 4
- DB: bun:sqlite (driver: `bun:sqlite`)
- Language: typescript (strict)
- JSX: hono/jsx available (unused — JSON API only)
- No ORM, no query builder

## Folder Layout

```
src/
  app.ts              # build Hono app, mount sub-apps, error handler
  index.ts            # bun entry, re-exports default app
  db/
    connection.ts     # open ./data/app.db, set PRAGMA WAL+NORMAL
    migrate.ts        # migration runner CLI
  migrations/
    0001_initial.sql  # rankings + news + indexes
  models/
    rankings.ts       # findLatest, findBySlug, findTop
    news.ts           # findLatest, findById, findByDate, findByType
  controllers/
    rankings.ts       # Hono handlers
    news.ts
  routes/
    rankings.ts       # Hono sub-app
    news.ts
data/
  app.db              # created on first migrate (gitignored)
docs/
  superpowers/
    specs/            # this file
```

Existing `src/index.ts` becomes the bun entry that re-exports `app` from
`src/app.ts`.

## Database

File: `./data/app.db`. Parent dir created on migrate.

Pragmas (applied per open in `connection.ts`):

```sql
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
```

### Tables (`migrations/0001_initial.sql`)

```sql
CREATE TABLE IF NOT EXISTS rankings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  rank INTEGER NOT NULL,
  name TEXT NOT NULL,
  slug TEXT NOT NULL,
  based_on TEXT NOT NULL DEFAULT '[]',
  hpd INTEGER,
  yesterday INTEGER,
  trend TEXT,
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_rankings_scraped_at ON rankings (scraped_at DESC);

CREATE TABLE IF NOT EXISTS news (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT,
  is_new INTEGER DEFAULT 0,
  type TEXT,
  headline TEXT,
  headline_slug TEXT,
  headline_url TEXT,
  logo TEXT,
  screenshot TEXT,
  rating INTEGER,
  text TEXT,
  text_html TEXT,
  links TEXT DEFAULT '[]',
  scraped_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_news_scraped_at ON news (scraped_at DESC);
```

### Migration Tracking

Table created lazily by runner (not in SQL file — runner owns it):

```sql
CREATE TABLE IF NOT EXISTS _migrations (
  version    INTEGER PRIMARY KEY,
  name       TEXT NOT NULL,
  applied_at TEXT NOT NULL
);
```

## Migration Runner

**File:** `src/db/migrate.ts` (CLI, not auto-run on app boot).

**Algorithm:**

1. Resolve `./data/` dir; `mkdir -p`.
2. Open `app.db` via `connection.ts`.
3. Ensure `_migrations` table exists.
4. Read `src/migrations/*.sql`, sort by filename.
5. For each file, parse leading `NNNN_` as integer version.
6. Skip if `version` already in `_migrations`.
7. Execute file contents in a transaction using `BEGIN` / `COMMIT` /
   `ROLLBACK` via `db.exec()` (bun:sqlite has no multi-statement transaction
   helper — manual BEGIN/COMMIT is the supported pattern).
8. Insert `(version, name, applied_at=ISO_NOW)` row.
9. Log `[migrate] applied 0001_initial` per applied file.
10. Exit 0; non-zero on any error (tx rolls back).

**Idempotency:** all SQL uses `IF NOT EXISTS`. Runner skips applied versions.
Running twice in a row is a no-op after first.

## Models

Thin typed fns over `db.query<Row>()`. JSON text columns (`based_on`, `links`)
parsed with `JSON.parse` on read; raw string on write (no write endpoints in
this spec).

**`src/models/rankings.ts`**

- `Ranking` type
- `findLatest(opts: { limit?: number; slug?: string }): Ranking[]`
  - ORDER BY scraped_at DESC, rank ASC
  - WHERE slug = ? if slug given
  - LIMIT ? (default 100, cap 500)
- `findBySlug(slug: string, limit = 50): Ranking[]`
  - all snapshots for slug, newest first

**`src/models/news.ts`**

- `News` type
- `findLatest(opts: { limit?: number; type?: string; date?: string }): News[]`
  - ORDER BY scraped_at DESC, id DESC
  - WHERE type = ? / date = ? as given
  - LIMIT ? (default 50, cap 200)
- `findById(id: number): News | null`

## Controllers

Hono handlers. Each parses query, calls model, returns `c.json(...)`.
Validation: cap limit via shared helper, ignore unknown params (no 400s for
unknown keys in this spec).

**`src/controllers/rankings.ts`**

- `list(c)` — `findLatest` with `?limit=&slug=`
- `history(c)` — `findBySlug` with `:slug`, `?limit=`

**`src/controllers/news.ts`**

- `list(c)` — `findLatest` with `?limit=&type=&date=`
- `detail(c)` — `findById` with `:id`, 404 if null

**Error shape (all controllers):**

```json
{ "error": "<message>" }
```

## Routes

Per-resource Hono sub-app. Mounted in `app.ts`.

**`src/routes/rankings.ts`**

```
GET /         -> list
GET /:slug    -> history
```

**`src/routes/news.ts`**

```
GET /         -> list
GET /:id      -> detail
```

**`src/app.ts`** mounts:

```
app.route('/rankings', rankingsRouter)
app.route('/news',      newsRouter)
app.get('/healthz',     (c) => c.json({ ok: true }))
app.onError((err, c) => c.json({ error: err.message }, 500))
app.notFound((c) => c.json({ error: 'not found' }, 404))
```

`index.ts`:

```ts
export { default } from './app'
```

## package.json Scripts

```json
{
  "scripts": {
    "dev":       "bun run --hot src/index.ts",
    "db:migrate": "bun run src/db/migrate.ts",
    "db:reset":   "rm -rf data && bun run db:migrate"
  }
}
```

## Error Handling

- DB errors surface as 500 via `app.onError`.
- Missing rows: 404 from controller with `{ error: "..." }`.
- Bad `:id` (not numeric) on news detail: 400 from controller.

## Out of Scope (deferred)

- Write endpoints (POST/PUT/DELETE)
- Scraping / fetchers
- Auth, rate limiting
- Tests
- Seed data
- Drizzle / any ORM
- Migrations beyond `0001_initial`

## Open Questions

None at design time. All decisions resolved during brainstorming.
