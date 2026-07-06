import { Hono } from "hono";
import rankings from "./routes/rankings";
import news from "./routes/news";
import distributions from "./routes/distributions";
import newsFilters from "./routes/news-filters";
import weekly from "./routes/weekly";
import search from "./routes/search";
import latest from "./routes/latest";
import { setupOpenApi } from "./lib/openapi";
import { cleanupOldData } from "./lib/cleanup";
import { rateLimiter } from "hono-rate-limiter";
import { runMigration } from "./db/migrate";

const cmd = process.argv[2];
if (cmd === "db:migrate") {
    runMigration();
    process.exit(0);
}

const app = new Hono();

app.use(rateLimiter({
  windowMs: 60_000,
  limit: 30,
  keyGenerator: (c) => c.req.header("x-forwarded-for") ?? "unknown",
}))

app.get("/", (c) => c.json({ name: "diwa", version: "1.0.0", docs: `${new URL(c.req.url).origin}/api/docs` }))
app.get("/api/healthz", (c) => c.json({ ok: true }));
app.route("/api/rankings", rankings);
app.route("/api/news/filters", newsFilters);
app.route("/api/news", news);
app.route("/api/distributions", distributions);
app.route("/api/weekly", weekly);
app.route("/api/search", search);
app.route("/api/latest", latest);
setupOpenApi(app);

Bun.cron("0 * * * *", cleanupOldData)

app.notFound((c) => c.json({ error: "not found" }, 404));
app.onError((err, c) => {
    console.error("[error]", err);
    return c.json({ error: err.message || "internal error" }, 500);
});

export default app;
