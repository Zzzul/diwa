import { Hono } from 'hono'
import { findLatest, findById } from '../models/news'
import { parseLimit } from '../lib/parse'
import { fetchAndStoreNews } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const limit = parseLimit(c.req.query('limit'), 13, 200)
  const type = c.req.query('type') || undefined
  const date = c.req.query('date') || undefined
  let data = findLatest({ limit, type, date })

  if (data.length === 0) {
    try {
      await fetchAndStoreNews()
      data = findLatest({ limit, type, date })
    } catch (err) {
      return c.json({ error: 'fetch failed', detail: String(err) }, 502)
    }
  }

  return c.json({ data, count: data.length })
})

app.get('/:id', (c) => {
  const raw = c.req.param('id')
  if (!raw) return c.json({ error: 'invalid id' }, 400)
  const id = Number.parseInt(raw, 10)
  if (!Number.isFinite(id) || id < 1) return c.json({ error: 'invalid id' }, 400)
  const row = findById(id)
  if (!row) return c.json({ error: 'not found' }, 404)
  return c.json({ data: row })
})

export default app
