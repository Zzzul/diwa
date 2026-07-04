import { Hono } from 'hono'
import { scrapeDistroList } from '../lib/distrowatch'
import { findAll, insertMany } from '../models/distributions-list'
import { isDev } from '../lib/parse'

const app = new Hono()

app.get('/', async (c) => {
  if (!isDev()) {
    const data = findAll()
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeDistroList()
    if (!isDev()) {
      insertMany(data)
    }
    return c.json({ data, count: data.length })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
  }
})

export default app
