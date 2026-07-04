import { Hono } from 'hono'
import { findLatest, findById } from '../models/news'
import { parseLimit, isDev } from '../lib/parse'
import { scrapeNews, insertNews } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const limit = parseLimit(c.req.query('limit'), 13, 200)
  const type = c.req.query('type') || undefined
  const date = c.req.query('date') || undefined

  if (!isDev()) {
    const data = findLatest({ limit, type, date })
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeNews()
    if (!isDev()) insertNews(data)
    const sliced = data.slice(0, limit)
    return c.json({ data: sliced, count: sliced.length })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
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
