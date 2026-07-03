# Diwa MVC Scaffold Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scaffold the `diwa` Hono API with MVC folder layout, a raw-SQL migration runner over bun:sqlite, typed model fns, per-resource routing, and JSON-only endpoints.

**Architecture:** Hono app composed from per-resource sub-apps. `db/connection.ts` opens `./data/app.db` with WAL+NORMAL pragmas; `db/migrate.ts` runs SQL files in `migrations/` against a `_migrations` tracking table. Models expose typed query fns; controllers parse query params and return JSON; routes wire HTTP to controllers.

**Tech Stack:** bun, hono 4, bun:sqlite, typescript (strict).

## Global Constraints

- Working dir: `/home/zzzul/experiment/js/hono/diwa`
- All ts files strict, no `any`, no comments (per repo style).
- JSON-only API. No views, no write endpoints, no auth, no scraping.
- Migration runner is CLI only — no auto-run on app boot.
- Pragma: `PRAGMA journal_mode=WAL` and `PRAGMA synchronous=NORMAL` on every connection open.
- Bun:sqlite has no multi-statement transaction helper — runner uses manual `BEGIN` / `COMMIT` / `ROLLBACK` via `db.run()`.
- Idempotent SQL: every DDL uses `IF NOT EXISTS`.
- Cap `limit` query param: rankings default 100, max 500; news default 50, max 200.
- JSON text cols `based_on` and `links` parsed with `JSON.parse` on read.

## File Structure

```
src/
  app.ts                   # build Hono, mount sub-apps, error/404 handlers
  index.ts                 # bun entry: `export { default } from './app'`
  db/
    connection.ts          # exports `db` (Database) + `getDb()`
    migrate.ts             # CLI runner
  migrations/
    0001_initial.sql       # rankings + news + indexes
  models/
    rankings.ts            # Ranking type + findLatest / findBySlug
    news.ts                # News type + findLatest / findById
  controllers/
    rankings.ts            # list, history
    news.ts                # list, detail
  routes/
    rankings.ts            # Hono sub-app
    news.ts                # Hono sub-app
  lib/
    parse.ts               # parseLimit helper
data/
  app.db                   # created by migrate (gitignored)
```

---

### Task 1: Repo scaffold (gitignore, scripts, dirs)

**Files:**

- Modify: `/home/zzzul/experiment/js/hono/diwa/.gitignore`
- Modify: `/home/zzzul/experiment/js/hono/diwa/package.json`
- Create: empty dirs via `mkdir -p` (no files yet)

**Interfaces:**

- Produces: working tree with `data/` and `src/{db,models,controllers,routes,migrations,lib}/` dirs and updated scripts.

- [ ] **Step 1: Create dirs**

```bash
mkdir -p data src/db src/models src/controllers src/routes src/migrations src/lib
```

- [ ] **Step 2: Update .gitignore**

Replace contents of `.gitignore` with:

```
node_modules/
data/
*.log
.DS_Store
```

- [ ] **Step 3: Update package.json scripts**

Replace `package.json` contents with:

```json
{
    "name": "diwa",
    "scripts": {
        "dev": "bun run --hot src/index.ts",
        "db:migrate": "bun run src/db/migrate.ts",
        "db:reset": "rm -rf data && bun run db:migrate"
    },
    "dependencies": {
        "hono": "^4.12.27"
    },
    "devDependencies": {
        "@types/bun": "latest"
    }
}
```

- [ ] **Step 4: Verify scripts**

Run: `bun run db:migrate 2>&1 | head -5`
Expected: command not found / ENOENT (runner file does not exist yet — that is fine, the script itself is registered).

- [ ] **Step 5: Commit**

```bash
git add .gitignore package.json
git commit -m "chore: scaffold dirs, scripts, gitignore"
```

---

### Task 2: Initial migration SQL

**Files:**

- Create: `src/migrations/0001_initial.sql`

**Interfaces:**

- Produces: file `src/migrations/0001_initial.sql` consumed by Task 4 runner.

- [ ] **Step 1: Write migration file**

`src/migrations/0001_initial.sql`:

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

- [ ] **Step 2: Commit**

```bash
git add src/migrations/0001_initial.sql
git commit -m "feat(db): add initial migration for rankings and news"
```

---

### Task 3: DB connection module

**Files:**

- Create: `src/db/connection.ts`

**Interfaces:**

- Produces: exports `getDb(): Database` (idempotent singleton) and `DB_PATH: string`. Consumed by Tasks 4, 5, 6, 7, 8, 9, 10.

- [ ] **Step 1: Write connection module**

`src/db/connection.ts`:

```ts
import { Database } from "bun:sqlite";
import { mkdirSync } from "node:fs";
import { dirname, resolve } from "node:path";

export const DB_PATH = resolve(process.cwd(), "data", "app.db");

let instance: Database | null = null;

export function getDb(): Database {
    if (instance) return instance;
    mkdirSync(dirname(DB_PATH), { recursive: true });
    const db = new Database(DB_PATH, { create: true });
    db.run("PRAGMA journal_mode = WAL");
    db.run("PRAGMA synchronous = NORMAL");
    instance = db;
    return db;
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/db/connection.ts 2>&1 | head -20`
Expected: exit 0, no errors.

- [ ] **Step 3: Commit**

```bash
git add src/db/connection.ts
git commit -m "feat(db): connection module with WAL pragmas"
```

---

### Task 4: Migration runner

**Files:**

- Create: `src/db/migrate.ts`

**Interfaces:**

- Consumes: `getDb()` from Task 3, files in `src/migrations/*.sql`.
- Produces: pending migrations applied; rows in `_migrations`; CLI exit code 0/1.

- [ ] **Step 1: Write runner**

`src/db/migrate.ts`:

```ts
import { readdirSync, readFileSync } from "node:fs";
import { resolve } from "node:path";
import { getDb } from "./connection";

const MIGRATIONS_DIR = resolve(import.meta.dir, "..", "migrations");
const NAME_RE = /^(\d+)_([a-z0-9_]+)\.sql$/;

function ensureMigrationsTable() {
    const db = getDb();
    db.run(
        "CREATE TABLE IF NOT EXISTS _migrations (version INTEGER PRIMARY KEY, name TEXT NOT NULL, applied_at TEXT NOT NULL)",
    );
    return db;
}

function appliedVersions(db: ReturnType<typeof getDb>): Set<number> {
    const rows = db
        .query<{ version: number }>("SELECT version FROM _migrations")
        .all();
    return new Set(rows.map((r) => r.version));
}

function run() {
    const db = ensureMigrationsTable();
    const applied = appliedVersions(db);

    const files = readdirSync(MIGRATIONS_DIR)
        .filter((f) => f.endsWith(".sql"))
        .sort();

    let count = 0;
    for (const file of files) {
        const match = NAME_RE.exec(file);
        if (!match) {
            console.error(`[migrate] skip (bad name): ${file}`);
            continue;
        }
        const version = Number(match[1]);
        const name = match[2];
        if (applied.has(version)) {
            console.log(`[migrate] skip (applied): ${file}`);
            continue;
        }

        const sql = readFileSync(resolve(MIGRATIONS_DIR, file), "utf8");
        db.run("BEGIN");
        try {
            db.run(sql);
            db.query(
                "INSERT INTO _migrations (version, name, applied_at) VALUES (?, ?, ?)",
            ).run(version, name, new Date().toISOString());
            db.run("COMMIT");
            console.log(`[migrate] applied: ${file}`);
            count++;
        } catch (err) {
            db.run("ROLLBACK");
            console.error(`[migrate] failed: ${file}`);
            throw err;
        }
    }

    console.log(`[migrate] done (${count} applied)`);
}

run();
```

- [ ] **Step 2: Run migration**

Run: `bun run db:migrate`
Expected output (order may vary, must include these lines):

```
[migrate] applied: 0001_initial.sql
[migrate] done (1 applied)
```

- [ ] **Step 3: Verify idempotency**

Run: `bun run db:migrate`
Expected:

```
[migrate] skip (applied): 0001_initial.sql
[migrate] done (0 applied)
```

- [ ] **Step 4: Verify tables exist**

Run: `bun -e "import {Database} from 'bun:sqlite'; const db = new Database('data/app.db'); console.log(db.query(\"SELECT name FROM sqlite_master WHERE type='table' ORDER BY name\").all())"`
Expected: rows include `rankings`, `news`, `_migrations`.

- [ ] **Step 5: Commit**

```bash
git add src/db/migrate.ts
git commit -m "feat(db): migration runner with _migrations tracking"
```

---

### Task 5: parseLimit helper

**Files:**

- Create: `src/lib/parse.ts`

**Interfaces:**

- Produces: `parseLimit(raw: string | undefined, def: number, max: number): number` consumed by Tasks 7 and 8.

- [ ] **Step 1: Write helper**

`src/lib/parse.ts`:

```ts
export function parseLimit(
    raw: string | undefined,
    def: number,
    max: number,
): number {
    if (raw === undefined) return def;
    const n = Number.parseInt(raw, 10);
    if (!Number.isFinite(n) || n < 1) return def;
    return Math.min(n, max);
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/lib/parse.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 3: Commit**

```bash
git add src/lib/parse.ts
git commit -m "feat(lib): parseLimit query helper"
```

---

### Task 6: Rankings model

**Files:**

- Create: `src/models/rankings.ts`

**Interfaces:**

- Consumes: `getDb()`.
- Produces: `Ranking` type, `findLatest({ limit, slug })`, `findBySlug(slug, limit)`.

- [ ] **Step 1: Write model**

`src/models/rankings.ts`:

```ts
import { getDb } from "../db/connection";

export type Ranking = {
    id: number;
    rank: number;
    name: string;
    slug: string;
    based_on: string[];
    hpd: number | null;
    yesterday: number | null;
    trend: string | null;
    scraped_at: string;
};

type Row = Omit<Ranking, "based_on"> & { based_on: string };

function hydrate(row: Row): Ranking {
    let based_on: string[];
    try {
        const v = JSON.parse(row.based_on);
        based_on = Array.isArray(v) ? v : [];
    } catch {
        based_on = [];
    }
    return { ...row, based_on };
}

export function findLatest(opts: { limit: number; slug?: string }): Ranking[] {
    const db = getDb();
    if (opts.slug) {
        const rows = db
            .query<Row>(
                "SELECT * FROM rankings WHERE slug = ? ORDER BY scraped_at DESC, rank ASC LIMIT ?",
            )
            .all(opts.slug, opts.limit);
        return rows.map(hydrate);
    }
    const rows = db
        .query<Row>(
            "SELECT * FROM rankings ORDER BY scraped_at DESC, rank ASC LIMIT ?",
        )
        .all(opts.limit);
    return rows.map(hydrate);
}

export function findBySlug(slug: string, limit = 50): Ranking[] {
    const db = getDb();
    const rows = db
        .query<Row>(
            "SELECT * FROM rankings WHERE slug = ? ORDER BY scraped_at DESC, rank ASC LIMIT ?",
        )
        .all(slug, limit);
    return rows.map(hydrate);
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/models/rankings.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 3: Commit**

```bash
git add src/models/rankings.ts
git commit -m "feat(models): rankings query functions"
```

---

### Task 7: News model

**Files:**

- Create: `src/models/news.ts`

**Interfaces:**

- Consumes: `getDb()`.
- Produces: `News` type, `findLatest({ limit, type, date })`, `findById(id)`.

- [ ] **Step 1: Write model**

`src/models/news.ts`:

```ts
import { getDb } from "../db/connection";

export type News = {
    id: number;
    date: string | null;
    is_new: number | null;
    type: string | null;
    headline: string | null;
    headline_slug: string | null;
    headline_url: string | null;
    logo: string | null;
    screenshot: string | null;
    rating: number | null;
    text: string | null;
    text_html: string | null;
    links: string[];
    scraped_at: string;
};

type Row = Omit<News, "links"> & { links: string };

function hydrate(row: Row): News {
    let links: string[];
    try {
        const v = JSON.parse(row.links);
        links = Array.isArray(v) ? v : [];
    } catch {
        links = [];
    }
    return { ...row, links };
}

export function findLatest(opts: {
    limit: number;
    type?: string;
    date?: string;
}): News[] {
    const db = getDb();
    const where: string[] = [];
    const params: (string | number)[] = [];
    if (opts.type) {
        where.push("type = ?");
        params.push(opts.type);
    }
    if (opts.date) {
        where.push("date = ?");
        params.push(opts.date);
    }
    const whereSql = where.length ? `WHERE ${where.join(" AND ")}` : "";
    const sql = `SELECT * FROM news ${whereSql} ORDER BY scraped_at DESC, id DESC LIMIT ?`;
    const rows = db.query<Row>(sql).all(...params, opts.limit);
    return rows.map(hydrate);
}

export function findById(id: number): News | null {
    const db = getDb();
    const row = db.query<Row>("SELECT * FROM news WHERE id = ?").get(id);
    return row ? hydrate(row) : null;
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/models/news.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 3: Commit**

```bash
git add src/models/news.ts
git commit -m "feat(models): news query functions"
```

---

### Task 8: Rankings controller

**Files:**

- Create: `src/controllers/rankings.ts`

**Interfaces:**

- Consumes: `findLatest`, `findBySlug`, `parseLimit`.
- Produces: `list(c)`, `history(c)` Hono handlers.

- [ ] **Step 1: Write controller**

`src/controllers/rankings.ts`:

```ts
import type { Context } from "hono";
import { findLatest, findBySlug } from "../models/rankings";
import { parseLimit } from "../lib/parse";

export function list(c: Context) {
    const limit = parseLimit(c.req.query("limit"), 100, 500);
    const slug = c.req.query("slug") || undefined;
    const data = findLatest({ limit, slug });
    return c.json({ data, count: data.length });
}

export function history(c: Context) {
    const slug = c.req.param("slug");
    const limit = parseLimit(c.req.query("limit"), 50, 500);
    const data = findBySlug(slug, limit);
    return c.json({ data, count: data.length, slug });
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/controllers/rankings.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 3: Commit**

```bash
git add src/controllers/rankings.ts
git commit -m "feat(controllers): rankings list and history"
```

---

### Task 9: News controller

**Files:**

- Create: `src/controllers/news.ts`

**Interfaces:**

- Consumes: `findLatest`, `findById`, `parseLimit`.
- Produces: `list(c)`, `detail(c)` Hono handlers. `detail` returns 400 for non-numeric id, 404 for missing row.

- [ ] **Step 1: Write controller**

`src/controllers/news.ts`:

```ts
import type { Context } from "hono";
import { findLatest, findById } from "../models/news";
import { parseLimit } from "../lib/parse";

export function list(c: Context) {
    const limit = parseLimit(c.req.query("limit"), 50, 200);
    const type = c.req.query("type") || undefined;
    const date = c.req.query("date") || undefined;
    const data = findLatest({ limit, type, date });
    return c.json({ data, count: data.length });
}

export function detail(c: Context) {
    const raw = c.req.param("id");
    const id = Number.parseInt(raw, 10);
    if (!Number.isFinite(id) || id < 1) {
        return c.json({ error: "invalid id" }, 400);
    }
    const row = findById(id);
    if (!row) return c.json({ error: "not found" }, 404);
    return c.json({ data: row });
}
```

- [ ] **Step 2: Type-check**

Run: `bunx tsc --noEmit src/controllers/news.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 3: Commit**

```bash
git add src/controllers/news.ts
git commit -m "feat(controllers): news list and detail"
```

---

### Task 10: Per-resource routes

**Files:**

- Create: `src/routes/rankings.ts`
- Create: `src/routes/news.ts`

**Interfaces:**

- Produces: `rankingsRouter: Hono`, `newsRouter: Hono`. Mounted by Task 11.

- [ ] **Step 1: Write rankings route**

`src/routes/rankings.ts`:

```ts
import { Hono } from "hono";
import * as ctrl from "../controllers/rankings";

const router = new Hono();

router.get("/", ctrl.list);
router.get("/:slug", ctrl.history);

export default router;
```

- [ ] **Step 2: Write news route**

`src/routes/news.ts`:

```ts
import { Hono } from "hono";
import * as ctrl from "../controllers/news";

const router = new Hono();

router.get("/", ctrl.list);
router.get("/:id", ctrl.detail);

export default router;
```

- [ ] **Step 3: Type-check**

Run: `bunx tsc --noEmit src/routes/rankings.ts src/routes/news.ts 2>&1 | head -20`
Expected: exit 0.

- [ ] **Step 4: Commit**

```bash
git add src/routes/rankings.ts src/routes/news.ts
git commit -m "feat(routes): per-resource Hono sub-apps"
```

---

### Task 11: App composition

**Files:**

- Create: `src/app.ts`
- Modify: `src/index.ts` (replace existing)

**Interfaces:**

- Produces: default-exported Hono app from `src/app.ts` and `src/index.ts` that mounts `/rankings`, `/news`, `/healthz`, with error + 404 handlers.

- [ ] **Step 1: Write app.ts**

`src/app.ts`:

```ts
import { Hono } from "hono";
import rankingsRouter from "./routes/rankings";
import newsRouter from "./routes/news";

const app = new Hono();

app.get("/healthz", (c) => c.json({ ok: true }));

app.route("/rankings", rankingsRouter);
app.route("/news", newsRouter);

app.notFound((c) => c.json({ error: "not found" }, 404));
app.onError((err, c) => {
    console.error("[error]", err);
    return c.json({ error: err.message || "internal error" }, 500);
});

export default app;
```

- [ ] **Step 2: Replace src/index.ts**

Overwrite `src/index.ts` with:

```ts
export { default } from "./app";
```

- [ ] **Step 3: Type-check whole project**

Run: `bunx tsc --noEmit 2>&1 | head -40`
Expected: exit 0.

- [ ] **Step 4: Commit**

```bash
git add src/app.ts src/index.ts
git commit -m "feat: compose Hono app with mounted sub-apps"
```

---

### Task 12: End-to-end smoke

**Files:** none modified (verification only).

- [ ] **Step 1: Start dev server in background**

```bash
bun run dev > /tmp/diwa-dev.log 2>&1 &
echo $! > /tmp/diwa-dev.pid
sleep 2
```

- [ ] **Step 2: Hit healthz**

Run: `curl -s http://localhost:3000/healthz`
Expected: `{"ok":true}`

- [ ] **Step 3: Hit empty rankings list**

Run: `curl -s http://localhost:3000/rankings`
Expected: `{"data":[],"count":0}`

- [ ] **Step 4: Hit empty news list**

Run: `curl -s http://localhost:3000/news`
Expected: `{"data":[],"count":0}`

- [ ] **Step 5: Hit news detail with bad id**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/news/abc`
Expected: `400`

- [ ] **Step 6: Hit news detail with missing id**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/news/99999`
Expected: `404`

- [ ] **Step 7: Hit unknown route**

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:3000/nope`
Expected: `404`

- [ ] **Step 8: Hit news detail with valid seed**

Run:

```bash
bun -e "import {Database} from 'bun:sqlite'; const db = new Database('data/app.db'); db.query('INSERT INTO news (date, type, headline, headline_url, text, scraped_at, links) VALUES (?, ?, ?, ?, ?, ?, ?)').run('2026-07-03', 'announcement', 'Test headline', 'https://example.com', 'body', new Date().toISOString(), '[]'); console.log(db.query('SELECT last_insert_rowid() as id').get());"
```

Then: `curl -s http://localhost:3000/news/1`
Expected: `{"data":{"id":1,...}}` with `headline: "Test headline"`.

- [ ] **Step 9: Stop dev server**

```bash
kill "$(cat /tmp/diwa-dev.pid)" 2>/dev/null || true
rm -f /tmp/diwa-dev.pid /tmp/diwa-dev.log
```

- [ ] **Step 10: Reset db to leave clean state**

Run: `bun run db:reset`
Expected: tables recreated, seed row gone.

- [ ] **Step 11: Final commit if any cleanup**

```bash
git status
```

If dirty: review and commit any cleanup. If clean: done.
