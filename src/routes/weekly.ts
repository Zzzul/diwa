import { Hono } from "hono";
import { findWeeklyIssue, insertWeeklyIssue } from "../models/weekly";
import { isDev } from "../lib/parse";
import { scrapeWeeklyIssue } from "../lib/distrowatch";

const app = new Hono();

app.get("/:id", async (c) => {
    const id = c.req.param("id");
    if (!id || !/^\d{8}$/.test(id))
        return c.json({ error: "invalid id (use YYYYMMDD)" }, 400);

    if (!isDev()) {
        const cached = findWeeklyIssue(id);
        if (cached) return c.json({ data: cached });
    }

    try {
        const data = await scrapeWeeklyIssue(id);
        if (!isDev()) insertWeeklyIssue(data);
        return c.json({ data });
    } catch (err) {
        const msg =
            err instanceof ErrorEvent
                ? `puppeteer conn failed: ${err.message}`
                : String(err);
        return c.json({ error: "fetch failed", detail: msg }, 502);
    }
});

export default app;
