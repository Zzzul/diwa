import { Hono } from "hono";
import rankings from "./routes/rankings";
import news from "./routes/news";
import distributions from "./routes/distributions";
import headlines from "./routes/headlines";
import packages from "./routes/packages";
import reviews from "./routes/reviews";
import newsletters from "./routes/newsletters";
import podcasts from "./routes/podcasts";
import additions from "./routes/additions";
import waitingList from "./routes/waiting-list";
import newsFilters from "./routes/news-filters";
import weekly from "./routes/weekly";
import { setupOpenApi } from "./lib/openapi";
import { cleanupOldData } from "./lib/cleanup";
import { rateLimiter } from "hono-rate-limiter";

const app = new Hono();

app.use(rateLimiter({
  windowMs: 60_000,
  limit: 30,
  keyGenerator: (c) => c.req.header("x-forwarded-for") ?? "unknown",
}))

app.get("/api/healthz", (c) => c.json({ ok: true }));
app.route("/api/rankings", rankings);
app.route("/api/news/filters", newsFilters);
app.route("/api/news", news);
app.route("/api/distributions", distributions);
app.route("/api/headlines", headlines);
app.route("/api/packages", packages);
app.route("/api/reviews", reviews);
app.route("/api/newsletters", newsletters);
app.route("/api/podcasts", podcasts);
app.route("/api/additions", additions);
app.route("/api/waiting-list", waitingList);
app.route("/api/weekly", weekly);
setupOpenApi(app);

Bun.cron("0 * * * *", cleanupOldData)

app.notFound((c) => c.json({ error: "not found" }, 404));
app.onError((err, c) => {
    console.error("[error]", err);
    return c.json({ error: err.message || "internal error" }, 500);
});

export default app;
