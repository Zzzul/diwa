import { Hono } from "hono";
import rankings from "./routes/rankings";
import news from "./routes/news";
import distributions from "./routes/distributions";

const app = new Hono();

app.get("/api/healthz", (c) => c.json({ ok: true }));
app.route("/api/rankings", rankings);
app.route("/api/news", news);
app.route("/api/distributions", distributions);

app.notFound((c) => c.json({ error: "not found" }, 404));
app.onError((err, c) => {
    console.error("[error]", err);
    return c.json({ error: err.message || "internal error" }, 500);
});

export default app;
