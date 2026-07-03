import { Hono } from 'hono'
import { findLatest, findBySlug } from '../models/rankings'
import { parseLimit } from '../lib/parse'
import { fetchAndStore } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const limit = parseLimit(c.req.query('limit'), 100, 500)
  const slug = c.req.query('slug') || undefined
  let data = findLatest({ limit, slug })

  if (data.length === 0) {
    try {
      await fetchAndStore()
      data = findLatest({ limit, slug })
    } catch (err) {
      return c.json({ error: 'fetch failed', detail: String(err) }, 502)
    }
  }

  return c.json({ data, count: data.length })
})

app.get('/:slug', (c) => {
  const slug = c.req.param('slug')
  if (!slug) return c.json({ error: 'slug required' }, 400)
  const limit = parseLimit(c.req.query('limit'), 50, 500)
  const data = findBySlug(slug, limit)
  return c.json({ data, count: data.length, slug })
})

export default app
