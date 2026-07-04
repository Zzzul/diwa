import { Hono } from "hono";
import { scrapeDistribution } from "../lib/distrowatch";
import { findBySlug, insert } from "../models/distributions";
import { isDev } from "../lib/parse";

const app = new Hono();

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
