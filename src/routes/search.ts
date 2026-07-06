import { Hono } from 'hono'
import { findSearchFilters, insertSearchFilters } from '../models/search-filters'
import { findSearchResults, insertSearchResults } from '../models/search-results'
import { isDev } from '../lib/parse'
import { scrapeSearchFilters, scrapeSearch } from '../lib/distrowatch'

const API_BASE = process.env.API_BASE_URL || 'http://localhost:3000'

function cacheKey(params: Record<string, string>): string {
  return Object.entries(params).sort().map(kv => kv.join('=')).join('&')
}

const app = new Hono()

app.get('/filters', async (c) => {
  if (!isDev()) {
    const data = findSearchFilters()
    if (data.length > 0) {
      const grouped: Record<string, { label: string; options: { value: string; label: string }[] }> = {}
      for (const row of data) {
        if (!grouped[row.category_name]) {
          grouped[row.category_name] = { label: row.category_label, options: [] }
        }
        grouped[row.category_name].options.push({ value: row.value, label: row.label })
      }
      return c.json({ data: grouped })
    }
  }

  try {
    const data = await scrapeSearchFilters()
    if (!isDev()) insertSearchFilters(data)
    const grouped: Record<string, { label: string; options: { value: string; label: string }[] }> = {}
    for (const row of data) {
      if (!grouped[row.category_name]) {
        grouped[row.category_name] = { label: row.category_label, options: [] }
      }
      grouped[row.category_name].options.push({ value: row.value, label: row.label })
    }
    return c.json({ data: grouped })
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/', async (c) => {
  const params: Record<string, string> = {}
  const allowed = ['ostype', 'category', 'origin', 'basedon', 'notbasedon', 'desktop',
    'architecture', 'package', 'rolling', 'isosize', 'netinstall', 'language', 'defaultinit', 'status']
  for (const k of allowed) {
    const v = c.req.query(k)
    if (v) params[k] = v
  }
  try {
    const key = cacheKey(params)
    if (!isDev()) {
      const cached = findSearchResults(key)
      if (cached) {
        const data = JSON.parse(cached)
        for (const r of data) r.slug = `${API_BASE}/api/distributions/${r.slug}`
        return c.json({ data })
      }
    }

    const results = await scrapeSearch(params)
    if (!isDev()) insertSearchResults(key, JSON.stringify(params), JSON.stringify(results))
    for (const r of results) r.slug = `${API_BASE}/api/distributions/${r.slug}`
    return c.json({ data: results })
  } catch (err) {
    const msg = err instanceof Error ? err.message : String(err)
    return c.json({ error: 'search failed', detail: msg }, 502)
  }
})

export default app
