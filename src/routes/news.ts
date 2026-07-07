import { Hono } from 'hono'
import type { News } from '../models/news'
import { findLatest, findById } from '../models/news'
import { findNewsDetail, insertNewsDetail } from '../models/news-detail'
import { findNewsCache, insertNewsCache } from '../models/news-cache'
import { isDev } from '../lib/parse'
import { scrapeNews, insertNews, scrapeNewsDetail } from '../lib/distrowatch'

const proxyImg = (url: string | null, origin: string) =>
  url ? `${origin}/api/proxy/image?url=${encodeURIComponent(url)}` : null

const rewriteImages = <T extends { logo?: string | null; screenshot?: string | null }>(d: T, origin: string): T => ({
  ...d,
  logo: proxyImg(d.logo ?? null, origin),
  screenshot: proxyImg(d.screenshot ?? null, origin),
})

function enrich(item: News): News {
  if (item.type === 'weekly' && item.headline_slug) {
    return { ...item, headline_slug: item.headline_slug.replace('/api/news/', '/api/weekly/') }
  }
  return item
}

function cacheKey(filters: Record<string, string | undefined>): string {
  const parts = Object.entries(filters)
    .filter(kv => kv[1] !== undefined)
    .sort()
    .map(kv => `${kv[0]}=${kv[1]}`)
  return parts.length ? parts.join('&') : 'default'
}

const app = new Hono()

app.get('/', async (c) => {
  const origin = new URL(c.req.url).origin
  const mapOut = (data: News[]) => data.map(d => rewriteImages(enrich(d), origin))
  const type = c.req.query('type') || undefined
  const date = c.req.query('date') || undefined
  const distribution = c.req.query('distribution') || undefined
  const release = c.req.query('release') || undefined
  const month = c.req.query('month') || undefined
  const year = c.req.query('year') || undefined
  const filters = { type, date, distribution, release, month, year }
  const key = cacheKey(filters)

  if (!isDev()) {
    const cached = findNewsCache(key)
    if (cached) {
      const parsed = JSON.parse(cached)
      parsed.data = mapOut(parsed.data)
      return c.json(parsed)
    }
    const data = findLatest({ ...filters })
    if (data.length > 0) return c.json({ data: mapOut(data), count: data.length })
  }

  try {
    const data = await scrapeNews({ distribution, release, month, year })
    if (!isDev()) {
      insertNews(data)
      insertNewsCache(key, JSON.stringify(filters), JSON.stringify({ data, count: data.length }))
    }
    return c.json({ data: mapOut(data), count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/:id', async (c) => {
  const id = c.req.param('id')
  const origin = new URL(c.req.url).origin
  if (!id) return c.json({ error: 'invalid id' }, 400)

  if (!isDev() && /^\d+$/.test(id)) {
    const cached = findNewsDetail(id)
    if (cached) {
      if (cached.type === 'weekly') return c.json({ error: 'weekly news not supported' }, 400)
      return c.json({ data: rewriteImages(cached, origin) })
    }
  }

  if (/^\d+$/.test(id)) {
    try {
      const data = await scrapeNewsDetail(id)
      if (data.type === 'weekly') return c.json({ error: 'weekly news not supported' }, 400)
      if (!isDev()) insertNewsDetail(data)
      return c.json({ data: rewriteImages(data, origin) })
    } catch (err) {
      const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
      return c.json({ error: 'fetch failed', detail: msg }, 502)
    }
  }

  const row = findById(id)
  if (!row) return c.json({ error: 'not found' }, 404)
  return c.json({ data: rewriteImages(enrich(row), origin) })
})

export default app
