import { Hono } from 'hono'
import { findLatest, findBySlug } from '../models/rankings'
import { isDev } from '../lib/parse'
import { scrapeRankings, insertDb } from '../lib/distrowatch'

const app = new Hono()

app.get('/', async (c) => {
  const slug = c.req.query('slug') || undefined
  const dataspan = c.req.query('dataspan') || '26'

  if (!isDev()) {
    const data = findLatest({ slug, dataspan })
    if (data.length > 0) return c.json({ data, count: data.length })
  }

  try {
    const data = await scrapeRankings(dataspan)
    if (!isDev()) insertDb(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

const DATASPANS = [
  { value: '2002', label: 'Year 2002' },
  { value: '2003', label: 'Year 2003' },
  { value: '2004', label: 'Year 2004' },
  { value: '2005', label: 'Year 2005' },
  { value: '2006', label: 'Year 2006' },
  { value: '2007', label: 'Year 2007' },
  { value: '2008', label: 'Year 2008' },
  { value: '2009', label: 'Year 2009' },
  { value: '2010', label: 'Year 2010' },
  { value: '2011', label: 'Year 2011' },
  { value: '2012', label: 'Year 2012' },
  { value: '2013', label: 'Year 2013' },
  { value: '2014', label: 'Year 2014' },
  { value: '2015', label: 'Year 2015' },
  { value: '2016', label: 'Year 2016' },
  { value: '2017', label: 'Year 2017' },
  { value: '2018', label: 'Year 2018' },
  { value: '2019', label: 'Year 2019' },
  { value: '2020', label: 'Year 2020' },
  { value: '2021', label: 'Year 2021' },
  { value: '2022', label: 'Year 2022' },
  { value: '2023', label: 'Year 2023' },
  { value: '2024', label: 'Year 2024' },
  { value: '2025', label: 'Year 2025' },
  { value: '52', label: 'Last 12 months' },
  { value: '26', label: 'Last 6 months' },
  { value: '13', label: 'Last 3 months' },
  { value: '4', label: 'Last 30 days' },
  { value: '1', label: 'Last 7 days' },
  { value: 'score', label: 'Average Rating' },
  { value: 'votes', label: 'Most Ratings' },
  { value: 'trending-52', label: 'Trending past 12 months' },
  { value: 'trending-26', label: 'Trending past 6 months' },
  { value: 'trending-13', label: 'Trending past 3 months' },
  { value: 'trending-4', label: 'Trending past 30 days' },
  { value: 'trending-1', label: 'Trending past 7 days' },
]

app.get('/dataspans', (c) => c.json({ data: DATASPANS }))

app.get('/:slug', (c) => {
  const slug = c.req.param('slug')
  if (!slug) return c.json({ error: 'slug required' }, 400)
  const data = findBySlug(slug)
  return c.json({ data, count: data.length, slug })
})

export default app
