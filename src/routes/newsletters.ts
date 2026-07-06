import { Hono } from 'hono'
import { findLatest } from '../models/newsletters'
import { parseLimit, isDev } from '../lib/parse'
import { scrapeNewsletters, insertNewsletters } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const limit = parseLimit(c.req.query('limit'), 13, 200)

  if (!isDev()) {
    const data = findLatest(limit)
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeNewsletters()
    if (!isDev()) insertNewsletters(data)
    const sliced = data.slice(0, limit)
    return c.json({ data: sliced, count: sliced.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

export default app
