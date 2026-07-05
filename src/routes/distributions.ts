import { Hono } from "hono";
import { scrapeDistribution } from "../lib/distrowatch";
import { findBySlug, insert } from "../models/distributions";
import { isDev } from "../lib/parse";
import { scrapeDistroList } from '../lib/distrowatch'
import { findAll, insertMany } from '../models/distributions-list'

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
