import { Hono } from "hono";
import { scrapeDistribution, scrapeRandomDistribution, scrapeDistroList } from "../lib/distrowatch";
import { findBySlug, insert } from "../models/distributions";
import { isDev } from "../lib/parse";
import { findAll, insertMany } from '../models/distributions-list'
import { insert as insertRandom, findLatest } from '../models/random-distributions'

const app = new Hono();

const proxyImg = (url: string | null, type: 'logo' | 'screenshot', origin: string) =>
  url ? `${origin}/api/proxy/image?url=${encodeURIComponent(url)}&type=${type}` : null

const rewriteImages = <T extends { logo?: string | null; screenshot?: string | null }>(d: T, origin: string): T => ({
  ...d,
  logo: proxyImg(d.logo ?? null, 'logo', origin),
  screenshot: proxyImg(d.screenshot ?? null, 'screenshot', origin),
})

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
    return c.json({ data: rewriteImages(data, new URL(c.req.url).origin) })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
  }
})

app.get('/random/history', (c) => {
  const limit = Number(c.req.query('limit')) || 10
  const origin = new URL(c.req.url).origin
  const data = findLatest(limit).map(d => rewriteImages(d, origin))
  return c.json({ data, count: data.length })
})

app.get("/:slug", async (c) => {
    const slug = c.req.param("slug");
    if (!slug) return c.json({ error: "slug required" }, 400);
    const origin = new URL(c.req.url).origin

    if (!isDev()) {
        const cached = findBySlug(slug);
        if (cached) return c.json({ data: rewriteImages(cached, origin) });
    }

    try {
        const data = await scrapeDistribution(slug);
        if (!isDev()) insert(data);
        return c.json({ data: rewriteImages(data, origin) });
    } catch (err) {
        return c.json({ error: "fetch failed", detail: String(err) }, 502);
    }
});

export default app;
