# diwa

Ranking & news scraper API. Dibangun dengan [Hono](https://hono.dev) + `bun:sqlite`.

## Setup

```bash
bun install
bun run db:migrate
```

## Menjalankan

```bash
bun run dev
```

Server: `http://localhost:3000`

## Endpoint

| Method | Path | Deskripsi |
|--------|------|-----------|
| GET | `/api/healthz` | Cek server |
| GET | `/api/rankings` | Ranking snapshot. Query: `?limit=`, `?slug=` |
| GET | `/api/rankings/:slug` | Riwayat ranking per slug. Query: `?limit=` |
| GET | `/api/news` | Daftar news. Query: `?limit=`, `?type=`, `?date=` |
| GET | `/api/news/:id` | Detail news (400 jika id invalid, 404 jika tidak ditemukan) |

Limit default: `/api/rankings` = 100 (max 500), `/api/news` = 50 (max 200).

### Contoh

```bash
curl http://localhost:3000/api/healthz
curl http://localhost:3000/api/rankings
curl http://localhost:3000/api/rankings/Bitcoin?limit=10
curl http://localhost:3000/api/news?type=announcement
curl http://localhost:3000/api/news/1
```

## Lainnya

| Perintah | Efek |
|----------|------|
| `bun run db:migrate` | Jalankan migration yg belum diaplikasikan |
| `bun run db:reset` | Hapus DB + migrate ulang |
| `bun run scrape:distrowatch` | Scrape distrowatch.com, simpan HTML ke `data/distrowatch/` |

## Struktur

```
src/
  app.ts            # app Hono, mount routes
  index.ts          # entry bun
  db/               # koneksi DB + migration runner
  migrations/       # file SQL migration
  models/           # query functions (rankings, news)
  controllers/      # handler
  routes/           # sub-app per resource
  lib/              # helper
```
