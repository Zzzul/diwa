import { Hono } from 'hono'
import { isDev } from '../lib/parse'
import { findLatest as findHeadlines } from '../models/headlines'
import { findLatest as findPackages } from '../models/packages'
import { findLatest as findReviews } from '../models/reviews'
import { findLatest as findNewsletters } from '../models/newsletters'
import { findLatest as findPodcasts } from '../models/podcasts'
import { findLatest as findAdditions } from '../models/additions'
import { findLatest as findWaiting } from '../models/waiting-list'
import { insertMany as insertLatest, findLatest as findLatestDist } from '../models/latest-distributions'
import { scrapeHeadlines, insertHeadlines } from '../lib/distrowatch'
import { scrapePackages, insertPackages } from '../lib/distrowatch'
import { scrapeReviews, insertReviews } from '../lib/distrowatch'
import { scrapeNewsletters, insertNewsletters } from '../lib/distrowatch'
import { scrapePodcasts, insertPodcasts } from '../lib/distrowatch'
import { scrapeAdditions, insertAdditions } from '../lib/distrowatch'
import { scrapeWaitingList, insertWaitingList } from '../lib/distrowatch'
import { scrapeLatestDistributions } from '../lib/distrowatch'

const app = new Hono()

app.get('/distributions', async (c) => {
  const limit = Number(c.req.query('limit')) || 50
  if (!isDev()) {
    const data = findLatestDist(limit)
    if (data.length > 0) return c.json({ data, count: data.length })
  }
  try {
    const data = await scrapeLatestDistributions()
    if (!isDev()) insertLatest(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    return c.json({ error: 'fetch failed', detail: String(err) }, 502)
  }
})

app.get('/headlines', async (c) => {
  if (!isDev()) { const d = findHeadlines(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapeHeadlines()
    if (!isDev()) insertHeadlines(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/packages', async (c) => {
  if (!isDev()) { const d = findPackages(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapePackages()
    if (!isDev()) insertPackages(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/reviews', async (c) => {
  if (!isDev()) { const d = findReviews(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapeReviews()
    if (!isDev()) insertReviews(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/newsletters', async (c) => {
  if (!isDev()) { const d = findNewsletters(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapeNewsletters()
    if (!isDev()) insertNewsletters(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/podcasts', async (c) => {
  if (!isDev()) { const d = findPodcasts(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapePodcasts()
    if (!isDev()) insertPodcasts(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/additions', async (c) => {
  if (!isDev()) { const d = findAdditions(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapeAdditions()
    if (!isDev()) insertAdditions(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

app.get('/waiting-list', async (c) => {
  if (!isDev()) { const d = findWaiting(); if (d.length > 0) return c.json({ data: d, count: d.length }) }
  try {
    const data = await scrapeWaitingList()
    if (!isDev()) insertWaitingList(data)
    return c.json({ data, count: data.length })
  } catch (err) {
    const msg = err instanceof ErrorEvent ? `puppeteer conn failed: ${err.message}` : String(err)
    return c.json({ error: 'fetch failed', detail: msg }, 502)
  }
})

export default app
