import { Hono } from "hono";

const app = new Hono();

app.get("/image", async (c) => {
  const url = c.req.query("url");
  if (!url) return c.json({ error: "url required" }, 400);

  const allowedHosts = ["distrowatch.com", "dw.com"];
  try {
    const parsed = new URL(url);
    if (!allowedHosts.some((h) => parsed.hostname.endsWith(h))) {
      return c.json({ error: "host not allowed" }, 403);
    }
  } catch {
    return c.json({ error: "invalid url" }, 400);
  }

  const res = await fetch(url, {
    headers: { "User-Agent": "diwa/1.0" },
  });

  if (!res.ok) return c.json({ error: "fetch failed" }, res.status);

  const contentType = res.headers.get("content-type") || "image/png";
  const buffer = await res.arrayBuffer();

  return c.body(buffer, 200, {
    "Content-Type": contentType,
    "Cache-Control": "public, max-age=86400",
  });
});

export default app;
