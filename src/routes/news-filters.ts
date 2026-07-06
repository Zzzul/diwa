import { Hono } from 'hono'
import { findNewsFilterOptions } from '../models/news-filter-options'
import { isDev } from '../lib/parse'
import { scrapeNewsFilterOptions, insertNewsFilterOptions } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  if (!isDev()) {
    const data = findNewsFilterOptions()
    if (data.length > 0) {
      const grouped: Record<string, { value: string; label: string }[]> = {}
      for (const row of data) {
        if (!grouped[row.category]) grouped[row.category] = []
        grouped[row.category].push({ value: row.value, label: row.label })
      }
      return c.json({ data: grouped })
    }
  }

  try {
    const data = await scrapeNewsFilterOptions()
    if (!isDev()) insertNewsFilterOptions(data)
    const grouped: Record<string, { value: string; label: string }[]> = {}
    for (const row of data) {
      if (!grouped[row.category]) grouped[row.category] = []
      grouped[row.category].push({ value: row.value, label: row.label })
    }
    return c.json({ data: grouped })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

export default app
