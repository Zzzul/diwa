import { Hono } from 'hono'
import { findLatest } from '../models/podcasts'
import { isDev } from '../lib/parse'
import { scrapePodcasts, insertPodcasts } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  if (!isDev()) {
    const data = findLatest()
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapePodcasts()
    if (!isDev()) insertPodcasts(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

export default app
