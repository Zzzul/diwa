import { Hono } from "hono";
import { scrapeDistribution, scrapeRandomDistribution, scrapeDistroList, scrapeLatestDistributions } from "../lib/distrowatch";
import { findBySlug, insert } from "../models/distributions";
import { isDev } from "../lib/parse";
import { findAll, insertMany } from '../models/distributions-list'
import { insert as insertRandom, findLatest } from '../models/random-distributions'
import { insertMany as insertLatest, findLatest as findLatestDist } from '../models/latest-distributions'

const app = new Hono();

const withFullSlug = (items: { slug: string; name: string; id?: string }[], origin: string) =>
    items.map(item => ({ ...item, slug: `${origin}/api/distributions/${item.slug}` }))

app.get('/', async (c) => {
    const origin = new URL(c.req.url).origin

    if (!isDev()) {
        const data = findAll()
        if (data.length > 0) return c.json({ data: withFullSlug(data, origin), count: data.length })
    }

    try {
        const data = await scrapeDistroList()
        if (!isDev()) {
            insertMany(data)
        }
        return c.json({ data: withFullSlug(data, origin), count: data.length })
    } catch (err) {
        return c.json({ error: 'fetch failed', detail: String(err) }, 502)
    }
})

app.get('/random', async (c) => {
  try {
    const data = await scrapeRandomDistribution()
    if (!isDev()) insertRandom(data)
    return c.json({ data })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
  }
})

app.get('/random/history', (c) => {
  const limit = Number(c.req.query('limit')) || 10
  const data = findLatest(limit)
  return c.json({ data, count: data.length })
})

app.get('/latest', async (c) => {
  const limit = Number(c.req.query('limit')) || 50

  if (!isDev()) {
    const data = findLatestDist(limit)
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeLatestDistributions()
    if (!isDev()) insertLatest(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
  }
})

app.get("/:slug", async (c) => {
    const slug = c.req.param("slug");
    if (!slug) return c.json({ error: "slug required" }, 400);

    if (!isDev()) {
        const cached = findBySlug(slug);
        if (cached) return c.json({ data: cached });
    }

    try {
        const data = await scrapeDistribution(slug);
        if (!isDev()) insert(data);
        return c.json({ data });
    } catch (err) {
        return c.json({ error: "fetch failed", detail: String(err) }, 502);
    }
});

export default app;
