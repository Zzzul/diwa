import { Hono } from 'hono'
import { findLatest, findById } from '../models/news'
import { isDev } from '../lib/parse'
import { scrapeNews, insertNews } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const type = c.req.query('type') || undefined
  const date = c.req.query('date') || undefined

  if (!isDev()) {
    const data = findLatest({ type, date })
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeNews()
    if (!isDev()) insertNews(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/:id', (c) => {
  const id = c.req.param('id')
  if (!id) return c.json({ error: 'invalid id' }, 400)
  const row = findById(id)
  if (!row) return c.json({ error: 'not found' }, 404)
  return c.json({ data: row })
})

export default app
