import { Hono } from "hono";
import { cors } from "hono/cors";
import rankings from "./routes/rankings";
import news from "./routes/news";
import distributions from "./routes/distributions";
import newsFilters from "./routes/news-filters";
import weekly from "./routes/weekly";
import search from "./routes/search";
import latest from "./routes/latest";
import proxy, { initCache } from "./routes/proxy";
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

app.use(cors())

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
app.route("/api/proxy", proxy);
setupOpenApi(app);

Bun.cron("0 * * * *", cleanupOldData)

app.notFound((c) => c.json({ error: "not found" }, 404));
app.onError((err, c) => {
    console.error("[error]", err);
    return c.json({ error: err.message || "internal error" }, 500);
});

const port = Number(process.env.PORT) || 3000;
const host = process.env.HOST || "0.0.0.0";

console.log(`[config] NODE_ENV=${process.env.NODE_ENV || "development"}`);
console.log(`[config] PORT=${port}`);
console.log(`[config] HOST=${host}`);
console.log(`[config] API_BASE_URL=${process.env.API_BASE_URL || "not set"}`);
console.log(`[config] BROWSER_WS=${process.env.BROWSER_WS ? "set" : "not set"}`);
console.log(`Started server: http://${host}:${port}`);

await initCache();

Bun.serve({
    fetch: app.fetch,
    port,
    hostname: host,
});
