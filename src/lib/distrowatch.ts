import { writeFileSync, mkdirSync } from 'node:fs'
import { resolve } from 'node:path'
import { randomUUIDv7 } from 'bun'
import puppeteer from 'puppeteer-core'
import { getDb } from '../db/connection'
import type { Ranking } from '../models/rankings'
import type { News, NewsLink } from '../models/news'

const DATA_DIR = resolve(import.meta.dir, '..', '..', 'data', 'distrowatch')
const BROWSER_WS = process.env.BROWSER_WS || 'ws://127.0.0.1:9222/devtools/browser'
const API_BASE = process.env.API_BASE_URL || 'http://localhost:3000'
const ROW_RE = /<tr>[\s\S]*?<th class="phr1">(\d+)<\/th>[\s\S]*?<td class="phr2"><a title="Based on: ([^"]*)" href="([^"]*)">([^<]+)<\/a><\/td>[\s\S]*?<td class="phr3" title="Yesterday: (\d+)">(\d+)<img[^>]*alt="([^"]*)"[^>]*><\/td>[\s\S]*?<\/tr>/g

const TREND_RE = /<tr>[\s\S]*?<th class="phr1">(\d+)<\/th>[\s\S]*?<td class="phr2"><a href="([^"]*)">([^<]+)<\/a><\/td>[\s\S]*?<td class="phr3">(\d+) <img[^>]*alt="([^"]*)"[^>]*><\/td>[\s\S]*?<\/tr>/g
const SIMPLE_RE = /<tr>[\s\S]*?<th class="phr1">(\d+)<\/th>[\s\S]*?<td class="phr2"><a href="([^"]*)">([^<]+)<\/a><\/td>[\s\S]*?<td class="phr3">([\d.]+)<\/td>[\s\S]*?<\/tr>/g

function mapTrend(alt: string): string {
  if (alt === '>' || alt === 'Up') return 'up'
  if (alt === '<' || alt === 'Down') return 'down'
  return 'level'
}

export function parseHtml(html: string, dataspan = '26'): Ranking[] {
  const now = new Date().toISOString()
  const items: Ranking[] = []
  let m: RegExpExecArray | null
  while ((m = ROW_RE.exec(html)) !== null) {
    const basedOn = m[2].split(',').map((s) => s.trim()).filter(Boolean)
    items.push({
      id: randomUUIDv7(),
      rank: Number(m[1]),
      name: m[4],
      slug: m[3],
      based_on: basedOn,
      hpd: Number(m[6]),
      yesterday: Number(m[5]),
      trend: mapTrend(m[7]),
      scraped_at: now,
      dataspan,
    })
  }
  return items
}

export function parseTrendingHtml(html: string, dataspan = '26'): Ranking[] {
  const now = new Date().toISOString()
  const items: Ranking[] = []
  let m: RegExpExecArray | null
  while ((m = TREND_RE.exec(html)) !== null) {
    items.push({
      id: randomUUIDv7(),
      rank: Number(m[1]),
      name: m[3],
      slug: m[2],
      based_on: [],
      hpd: Number(m[4]),
      yesterday: null,
      trend: mapTrend(m[5]),
      scraped_at: now,
      dataspan,
    })
  }
  return items
}

export function parseSimpleHtml(html: string, dataspan = '26'): Ranking[] {
  const now = new Date().toISOString()
  const items: Ranking[] = []
  let m: RegExpExecArray | null
  while ((m = SIMPLE_RE.exec(html)) !== null) {
    items.push({
      id: randomUUIDv7(),
      rank: Number(m[1]),
      name: m[3],
      slug: m[2],
      based_on: [],
      hpd: Number(m[4]),
      yesterday: null,
      trend: 'level',
      scraped_at: now,
      dataspan,
    })
  }
  return items
}

export function insertDb(items: Ranking[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO rankings (id, rank, name, slug, based_on, hpd, yesterday, trend, scraped_at, dataspan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Ranking[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.rank, r.name, r.slug, JSON.stringify(r.based_on), r.hpd, r.yesterday, r.trend, r.scraped_at, r.dataspan)
    }
  })
  tx(items)
}

export function saveJson(items: Ranking[] | News[], name = 'rankings'): void {
  const dir = resolve(DATA_DIR, 'json')
  mkdirSync(dir, { recursive: true })
  const ts = new Date().toISOString().replace(/[:.]/g, '-')
  writeFileSync(resolve(dir, `${ts}-${name}.json`), JSON.stringify(items, null, 2))
}

function absolutizeHtml(html: string): string {
  return html.replace(/<a\s+href="([^"]*?)">/gi, (_, href) => {
    if (/^https?:\/\//i.test(href)) return `<a href="${href}">`
    return `<a href="https://distrowatch.com/${href.replace(/^\//, '')}">`
  })
}

function stripHtml(s: string): string {
  return s.replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/\s+/g, ' ').trim()
}

function typeFromHeadline(h: string): string {
  if (/^Distribution Release:/i.test(h)) return 'distribution'
  if (/^Mobile OS Release:/i.test(h)) return 'mobile'
  if (/^DistroWatch Weekly/i.test(h)) return 'weekly'
  if (/^Featured Distribution:/i.test(h)) return 'featured'
  if (/^Development Release:/i.test(h)) return 'development'
  return 'announcement'
}

function parseAnnouncements(html: string, now: string): News[] {
  const items: News[] = []
  const section = html.match(/Latest News and Updates<\/th>([\s\S]*?)(?:Page Hit Ranking|$)/)
  if (!section) return items

  const rawItems = section[1].split('<td class="News1">').slice(1)

  for (const raw of rawItems) {
    const dateM = raw.match(/<td class="NewsDate">(?:<span[^>]*>)?([^<]*)/)
    const newM = raw.includes('<span style="color: #FF0000">NEW</span>')
    const hlM = raw.match(/<a href="([^"]*)">([^<]+)<\/a><\/td>/)
    const logoM = raw.match(/<td class="NewsLogo">([\s\S]*?)<\/td>/)
    const textM = raw.match(/<td class="NewsText"[^>]*>([\s\S]*?)<\/td>/)

    if (!hlM) continue

    const rawUrl = hlM[1]
    const headline = hlM[2]
    const logoHtml = logoM ? logoM[1] : ''
    const textHtml = textM ? textM[1] : ''

    const idMatch = rawUrl.match(/(\d+)$/)
    const distroMatch = rawUrl.match(/[?&]distribution=([a-z0-9]+)/i)
    const headlineSlug = idMatch
      ? `${API_BASE}/api/news/${idMatch[1]}`
      : distroMatch
        ? `${API_BASE}/api/news/${distroMatch[1]}`
        : rawUrl
    const headlineUrl = /^https?:\/\//i.test(rawUrl) ? rawUrl : `https://distrowatch.com/${rawUrl.replace(/^\//, '')}`

    const logoImg = logoHtml.match(/<img[^>]*src="([^"]*)"[^>]*>/)
    const screenshotImg = logoHtml.match(/<br><br><a href="([^"]+)">/)
    const ratingVal = logoHtml.match(/Rate this project[\s\S]*?\(([^)]*)\)/)

    const links: NewsLink[] = []
    const linkRe = /<a\s+href="([^"]*)"[^>]*>([^<]*)<\/a>/gi
    let lm: RegExpExecArray | null
    while ((lm = linkRe.exec(textHtml)) !== null) {
      const href = lm[1]
      const text = lm[2].trim()
      if (!text) continue
      const isExternal = /^https?:\/\//i.test(href)
      links.push({
        url: isExternal ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`,
        text,
        href,
      })
    }

    items.push({
      id: randomUUIDv7(),
      date: dateM ? dateM[1].trim() || null : null,
      is_new: newM ? true : false,
      type: typeFromHeadline(headline),
      headline,
      headline_slug: headlineSlug,
      headline_url: headlineUrl,
      logo: logoImg ? `https://distrowatch.com/${logoImg[1].replace(/^\//, '')}` : null,
      screenshot: screenshotImg ? `https://distrowatch.com/${screenshotImg[1].replace(/^\//, '')}` : null,
      rating: ratingVal ? Number(ratingVal[1]) : null,
      text: stripHtml(textHtml),
      text_html: absolutizeHtml(textHtml),
      links,
      scraped_at: now,
    })
  }
  return items
}

export function parseNewsHtml(html: string): News[] {
  const now = new Date().toISOString()
  return parseAnnouncements(html, now)
}

export function insertNews(items: News[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO news (id, date, is_new, type, headline, headline_slug, headline_url, logo, screenshot, rating, text, text_html, links, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: News[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.is_new ? 1 : 0, r.type, r.headline, r.headline_slug, r.headline_url,
        r.logo, r.screenshot, r.rating, r.text, r.text_html, JSON.stringify(r.links), r.scraped_at)
    }
  })
  tx(items)
}

export type DistributionLink = {
  text: string
  url: string
}

export type LatestDist = {
  id: string
  date: string
  slug: string
  name: string
  description: string | null
  download_url: string
  version: string
  scraped_at: string
}

export type DistroItem = {
  id?: string
  slug: string
  name: string
}

export type ReviewLink = {
  url: string
  title: string
  language: string | null
}

export type ReaderReview = {
  version: string | null
  rating: number | null
  date: string | null
  country: string | null
  votes: number | null
  text: string | null
}

export type Distribution = {
  id?: string
  name: string | null
  slug: string
  logo: string | null
  screenshot: string | null
  last_update: string | null
  os_type: string | null
  based_on: DistributionLink[]
  origin: string | null
  architecture: string[]
  desktop: string[]
  category: string[]
  status: string | null
  popularity: { rank: number | null; hpd: number | null }
  description: string | null
  rating: number | null
  reviews_count: number | null
  home_page: string | null
  user_forums: string | null
  documentation: string | null
  screenshots: string | null
  download_mirrors: string | null
  bug_tracker: string | null
  reviews: { version: string; links: ReviewLink[] }[]
  where_to_donate: DistributionLink[]
  related_websites: DistributionLink[]
  reader_reviews: ReaderReview[]
  recent_releases: { date: string; id: string; title: string }[]
  recent_headlines: { date: string; url: string; title: string }[]
  scraped_at: string
}

function parseDistribution(html: string, slug: string): Distribution {
  const now = new Date().toISOString()

  const nameM = html.match(/<h1>([^<]+)<\/h1>/)
  const logoM = html.match(/<img[^>]*src="([^"]+)"[^>]*class="logo"/) || html.match(/<img[^>]*class="logo"[^>]*src="([^"]+)"/)
  const screenshotM = html.match(/<a href="(images\/slinks\/[^"]+)">/)
  const updateM = html.match(/<h2>Last Update:\s*([^<]+)<\/h2>/)

  const osTypeM = html.match(/<li><b>OS Type:<\/b>\s*<a[^>]*>([^<]+)<\/a>/)
  const originM = html.match(/<li><b>Origin:<\/b>\s*<a[^>]*>([^<]+)<\/a>/)
  const statusM = html.match(/<li><b>Status:<\/b>\s*<font[^>]*>([^<]+)<\/font>/)
  const popularityM = html.match(/<b>Popularity:<\/b>\s*<a[^>]*>(\d+)\s*\(([^)]+)\)<\/a>/)

  const basedOn: DistributionLink[] = []
  const basedRe = /<li><b>Based on:<\/b>([\s\S]*?)<\/li>/
  const basedSection = html.match(basedRe)
  if (basedSection) {
    const linkRe = /<a\s+href="([^"]*)"[^>]*>([^<]+)<\/a>/g
    let m: RegExpExecArray | null
    while ((m = linkRe.exec(basedSection[1])) !== null) {
      basedOn.push({ text: m[2].trim(), url: `https://distrowatch.com/${m[1].replace(/^\//, '')}` })
    }
  }

  const arch: string[] = []
  const archRe = /<li><b>Architecture:<\/b>([\s\S]*?)<\/li>/
  const archSection = html.match(archRe)
  if (archSection) {
    const linkRe = /<a[^>]*>([^<]+)<\/a>/g
    let m: RegExpExecArray | null
    while ((m = linkRe.exec(archSection[1])) !== null) {
      arch.push(m[1].trim())
    }
  }

  const desktop: string[] = []
  const deskRe = /<li><b>Desktop:<\/b>([\s\S]*?)<\/li>/
  const deskSection = html.match(deskRe)
  if (deskSection) {
    const linkRe = /<a[^>]*>([^<]+)<\/a>/g
    let m: RegExpExecArray | null
    while ((m = linkRe.exec(deskSection[1])) !== null) {
      desktop.push(m[1].trim())
    }
  }

  const category: string[] = []
  const catRe = /<li><b>Category:<\/b>([\s\S]*?)<\/li>/
  const catSection = html.match(catRe)
  if (catSection) {
    const linkRe = /<a[^>]*>([^<]+)<\/a>/g
    let m: RegExpExecArray | null
    while ((m = linkRe.exec(catSection[1])) !== null) {
      category.push(m[1].trim())
    }
  }

  const titleSection = html.match(/<td class="TablesTitle">([\s\S]*?)<\/td>/)
  const titleHtml = titleSection ? titleSection[1] : ''

  const descM = titleHtml.match(/<\/ul>\s*([\s\S]*?)<br>\s*<br>\s*<b>/)
  const desc = descM ? stripHtml(descM[1]).trim() || null : null

  const ratingM = html.match(/Average visitor rating<\/a><\/b>: <b>([\d.]+)<\/b>\/10 from <b>(\d+)<\/b>/)

  const summary: Record<string, string> = {}
  const summaryHtml: Record<string, string> = {}
  const summaryRe = /<th class="Info">([^<]+)<\/th>\s*<td class="Info">([\s\S]*?)<\/td>/g
  let sm: RegExpExecArray | null
  while ((sm = summaryRe.exec(html)) !== null) {
    const label = sm[1].trim()
    summaryHtml[label] = sm[2]
    summary[label] = stripHtml(sm[2])
  }

  const reviews: { version: string; links: ReviewLink[] }[] = []
  const reviewsRaw = summaryHtml['Reviews'] || ''
  if (reviewsRaw) {
    const linkRe = /<a\s+href="([^"]*)"[^>]*>([^<]+)<\/a>\s*(?:\(([^)]*)\))?/g
    const lines = reviewsRaw.split(/<br>\s*/).filter((l: string) => l.trim())
    for (const line of lines) {
      const vlM = line.match(/^(?:<b>)?([^<:]+?)(?:<\/b>)?:\s*/)
      const version = vlM ? stripHtml(vlM[1]).trim().replace(/["']/g, '') : null
      if (!version) continue
      const links: ReviewLink[] = []
      let lm: RegExpExecArray | null
      while ((lm = linkRe.exec(line)) !== null) {
        links.push({
          url: lm[1].startsWith('http') ? lm[1] : `https://distrowatch.com/${lm[1].replace(/^\//, '')}`,
          title: lm[2].trim(),
          language: lm[3]?.trim() || null,
        })
      }
      if (links.length > 0) reviews.push({ version, links })
    }
  }

  function parseLinksFromHtml(raw: string): DistributionLink[] {
    const result: DistributionLink[] = []
    const lr = /<a\s+href="([^"]*)"[^>]*>([^<]+)<\/a>/g
    let m: RegExpExecArray | null
    while ((m = lr.exec(raw)) !== null) {
      const href = m[1]
      result.push({
        url: href.startsWith('http') ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`,
        text: m[2].trim(),
      })
    }
    return result
  }

  const whereToDonate = parseLinksFromHtml(summaryHtml['Where To Donate, Buy or Try'] || '')
  const relatedWebsites = parseLinksFromHtml(summaryHtml['Related Websites'] || '')

  const readerReviews: ReaderReview[] = []
  const ratingsSection = html.match(/Reader Ratings<\/th>([\s\S]*?)<\/table>\s*\n\s*<\/blockquote>/)
  if (ratingsSection) {
    const reviewRe = /<tr style="outline:\s*thin\s*solid\s*black">([\s\S]*?)<\/tr>/g
    let rr: RegExpExecArray | null
    while ((rr = reviewRe.exec(ratingsSection[1])) !== null) {
      const row = rr[1]
      const vM = row.match(/<b>Version:<\/b>\s*([^<]*)/)
      const rM = row.match(/<b>Rating:<\/b>\s*(\d+)/)
      const dM = row.match(/<b>Date:<\/b>\s*([^<]*)/)
      const cM = row.match(/<b>Country:<\/b>\s*([^<]*)/)
      const voM = row.match(/<b>Votes:<\/b>\s*(\d+)/)
      const tds = row.match(/<td[^>]*>([\s\S]*?)<\/td>\s*<td[^>]*>([\s\S]*?)<\/td>/)
      const raw = tds ? stripHtml(tds[2]).trim() || null : null
      const text = raw ? raw.replace(/\s*Was this review helpful\?.*/, '').trim() || null : null

      if (vM || rM || dM) {
        readerReviews.push({
          version: vM ? vM[1].trim() || null : null,
          rating: rM ? Number(rM[1]) : null,
          date: dM ? dM[1].trim() || null : null,
          country: cM ? cM[1].trim() || null : null,
          votes: voM ? Number(voM[1]) : null,
          text,
        })
      }
    }
  }

  const releases: { date: string; id: string; title: string }[] = []
  const relRe = /•\s*(\d{4}-\d{2}-\d{2}):\s*<a\s+href="(\d+)">([^<]+)<\/a>/g
  let rm: RegExpExecArray | null
  while ((rm = relRe.exec(html)) !== null) {
    releases.push({ date: rm[1], id: rm[2], title: rm[3].trim() })
  }

  const headlines: { date: string; url: string; title: string }[] = []
  const hlRe = /•\s*(\d{4}-\d{2}-\d{2})\s*<a\s+href="([^"]+)">([^<]+)<\/a>/g
  let hm: RegExpExecArray | null
  while ((hm = hlRe.exec(html)) !== null) {
    headlines.push({
      date: hm[1],
      url: `https://distrowatch.com/${hm[2].replace(/^\//, '')}`,
      title: hm[3].trim(),
    })
  }

  return {
    id: randomUUIDv7(),
    name: nameM ? nameM[1].trim() : null,
    slug,
    logo: logoM ? `https://distrowatch.com/${logoM[1].replace(/^\//, '')}` : null,
    screenshot: screenshotM ? `https://distrowatch.com/${screenshotM[1].replace(/^\//, '')}` : null,
    last_update: updateM ? updateM[1].trim() : null,
    os_type: osTypeM ? osTypeM[1].trim() : null,
    based_on: basedOn,
    origin: originM ? originM[1].trim() : null,
    architecture: arch,
    desktop,
    category,
    status: statusM ? statusM[1].trim() : null,
    popularity: popularityM ? { rank: Number(popularityM[1]), hpd: Number(popularityM[2].replace(/[^0-9]/g, '')) } : { rank: null, hpd: null },
    description: desc,
    rating: ratingM ? Number(ratingM[1]) : null,
    reviews_count: ratingM ? Number(ratingM[2]) : null,
    home_page: summary['Home Page'] || null,
    user_forums: summary['User Forums'] || null,
    documentation: summary['Documentation'] || null,
    screenshots: summary['Screenshots'] || null,
    download_mirrors: summary['Download Mirrors'] || null,
    bug_tracker: summary['Bug Tracker'] || null,
    reviews,
    where_to_donate: whereToDonate,
    related_websites: relatedWebsites,
    reader_reviews: readerReviews,
    recent_releases: releases,
    recent_headlines: headlines,
    scraped_at: now,
  }
}

export async function scrapeRandomDistribution(): Promise<Distribution> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com/random.php', { waitUntil: 'networkidle0' })
  const slug = new URL(page.url()).searchParams.get('distribution')
  await browser.disconnect()
  if (!slug) throw new Error('random redirect failed')
  return scrapeDistribution(slug)
}

export async function scrapeDistribution(slug: string): Promise<Distribution> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto(`https://distrowatch.com/table.php?distribution=${slug}`, { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseDistribution(html, slug)
}

export function parseLatestDistributions(html: string): LatestDist[] {
  const now = new Date().toISOString()
  const year = new Date().getFullYear()
  const items: LatestDist[] = []
  const section = html.match(/Latest Distributions\s*<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<tr>\s*<th class="News">([^<]+)<\/th>\s*<td class="News"><a title="([^"]*)" href="([^"]+)">([^<]+)<\/a>\s*•\s*<a href="([^"]+)">([^<]+)<\/a><\/td>\s*<\/tr>/g
  let m: RegExpExecArray | null
  while ((m = rowRe.exec(section[1])) !== null) {
    items.push({
      id: randomUUIDv7(),
      date: `${year}-${m[1]}`,
      slug: `${API_BASE}/api/distributions/${m[3]}`,
      name: m[4],
      description: m[2] || null,
      download_url: m[5],
      version: m[6],
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeLatestDistributions(): Promise<LatestDist[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseLatestDistributions(html)
}

export async function scrapeDistroList(): Promise<DistroItem[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()

  const items: DistroItem[] = []
  const section = html.match(/<select\s+name="distribution">([\s\S]*?)<\/select>/i)
  if (!section) return items
  const optRe = /<option value="([^"]+)">([^<]+)<\/option>/g
  let m: RegExpExecArray | null
  while ((m = optRe.exec(section[1])) !== null) {
    if (!m[1] || m[1] === 'all') continue
    items.push({ id: randomUUIDv7(), slug: m[1], name: m[2].trim().replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#39;/g, "'") })
  }
  return items
}

export async function scrapeRankings(dataspan = '26'): Promise<Ranking[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto(`https://distrowatch.com/index.php?dataspan=${dataspan}`, { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()

  if (dataspan.startsWith('trending-')) return parseTrendingHtml(html, dataspan)
  if (/title="Yesterday: \d+"/.test(html)) return parseHtml(html, dataspan)
  return parseSimpleHtml(html, dataspan)
}

export async function scrapeNews(): Promise<News[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseNewsHtml(html)
}

export async function fetchAndStoreNews(): Promise<News[]> {
  const items = await scrapeNews()
  if (items.length === 0) throw new Error('no news data scraped')
  saveJson(items, 'news')
  insertNews(items)
  return items
}

export async function fetchAndStore(dataspan = '26'): Promise<Ranking[]> {
  const items = await scrapeRankings(dataspan)
  if (items.length === 0) throw new Error('no ranking data scraped')
  saveJson(items)
  insertDb(items)
  return items
}

export type Headline = {
  id: string
  story_id: number
  title: string
  url: string
  position: number
  scraped_at: string
}

export function parseHeadlinesHtml(html: string): Headline[] {
  const now = new Date().toISOString()
  const items: Headline[] = []
  const section = html.match(/Latest Headlines<\/td>\s*<\/tr>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<tr>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a><\/td>\s*<\/tr>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const href = m[1]
    const title = m[2].trim()
    const storyMatch = href.match(/story=(\d+)/)
    const storyId = storyMatch ? Number(storyMatch[1]) : 0
    const url = href.startsWith('http') ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      story_id: storyId,
      title,
      url,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeHeadlines(): Promise<Headline[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseHeadlinesHtml(html)
}

export function insertHeadlines(items: Headline[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO headlines (id, story_id, title, url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Headline[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.story_id, r.title, r.url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type Package = {
  id: string
  date: string
  name: string
  description: string | null
  package_url: string
  version: string
  download_url: string
  position: number
  scraped_at: string
}

export function parsePackagesHtml(html: string): Package[] {
  const now = new Date().toISOString()
  const items: Package[] = []
  const section = html.match(/Latest Packages<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a title="([^"]*)" href="([^"]+)">([^<]+)<\/a>\s*•\s*<a href="([^"]+)">([^<]+)<\/a><\/td>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const pkgUrl = m[3].startsWith('http') ? m[3] : `https://distrowatch.com/${m[3].replace(/^\//, '')}`
    const dlUrl = m[5].startsWith('http') ? m[5] : `https://distrowatch.com/${m[5].replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      name: m[4].trim(),
      description: m[2].trim() || null,
      package_url: pkgUrl,
      version: m[6].trim(),
      download_url: dlUrl,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapePackages(): Promise<Package[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parsePackagesHtml(html)
}

export function insertPackages(items: Package[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO packages (id, date, name, description, package_url, version, download_url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Package[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.name, r.description, r.package_url, r.version, r.download_url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type Review = {
  id: string
  date: string
  title: string
  url: string
  position: number
  scraped_at: string
}

export function parseReviewsHtml(html: string): Review[] {
  const now = new Date().toISOString()
  const items: Review[] = []
  const section = html.match(/Latest Reviews<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a><\/td>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const href = m[2]
    const url = href.startsWith('http') ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      title: m[3].trim(),
      url,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeReviews(): Promise<Review[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseReviewsHtml(html)
}

export function insertReviews(items: Review[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO reviews (id, date, title, url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Review[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.title, r.url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type Newsletter = {
  id: string
  date: string
  title: string
  url: string
  position: number
  scraped_at: string
}

export function parseNewslettersHtml(html: string): Newsletter[] {
  const now = new Date().toISOString()
  const items: Newsletter[] = []
  const section = html.match(/Latest Newsletters<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a><\/td>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const href = m[2]
    const url = href.startsWith('http') ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      title: m[3].trim(),
      url,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeNewsletters(): Promise<Newsletter[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseNewslettersHtml(html)
}

export function insertNewsletters(items: Newsletter[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO newsletters (id, date, title, url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Newsletter[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.title, r.url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type Podcast = {
  id: string
  date: string
  title: string
  url: string
  episode: string | null
  episode_url: string | null
  mp3_url: string | null
  position: number
  scraped_at: string
}

export function parsePodcastsHtml(html: string): Podcast[] {
  const now = new Date().toISOString()
  const items: Podcast[] = []
  const section = html.match(/Latest Podcasts<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a>\s*-\s*<a href="([^"]+)">([^<]+)<\/a>\s*\(\s*<a href="([^"]+)">[^<]+<\/a>\s*\)/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const url = m[2].startsWith('http') ? m[2] : `https://distrowatch.com/${m[2].replace(/^\//, '')}`
    const epUrl = m[4].startsWith('http') ? m[4] : `https://distrowatch.com/${m[4].replace(/^\//, '')}`
    const mp3Url = m[6].startsWith('http') ? m[6] : `https://distrowatch.com/${m[6].replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      title: m[3].trim(),
      url,
      episode: m[5].trim(),
      episode_url: epUrl,
      mp3_url: mp3Url,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapePodcasts(): Promise<Podcast[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parsePodcastsHtml(html)
}

export function insertPodcasts(items: Podcast[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO podcasts (id, date, title, url, episode, episode_url, mp3_url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Podcast[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.title, r.url, r.episode, r.episode_url, r.mp3_url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type Addition = {
  id: string
  date: string
  name: string
  slug: string
  position: number
  scraped_at: string
}

export function parseAdditionsHtml(html: string): Addition[] {
  const now = new Date().toISOString()
  const items: Addition[] = []
  const section = html.match(/Latest Additions<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a><\/td>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      name: m[3].trim(),
      slug: m[2].trim(),
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeAdditions(): Promise<Addition[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseAdditionsHtml(html)
}

export function insertAdditions(items: Addition[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO additions (id, date, name, slug, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Addition[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.name, r.slug, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export type WaitingListItem = {
  id: string
  date: string
  name: string
  url: string
  position: number
  scraped_at: string
}

export function parseWaitingListHtml(html: string): WaitingListItem[] {
  const now = new Date().toISOString()
  const items: WaitingListItem[] = []
  const section = html.match(/New To Waiting List<\/th>([\s\S]*?)<\/tbody>/)
  if (!section) return items
  const rowRe = /<th class="News">([^<]+)<\/th>\s*<td class="News"><a href="([^"]+)">([^<]+)<\/a><\/td>/g
  let m: RegExpExecArray | null
  let position = 0
  while ((m = rowRe.exec(section[1])) !== null) {
    const href = m[2]
    const url = href.startsWith('http') ? href : `https://distrowatch.com/${href.replace(/^\//, '')}`
    items.push({
      id: randomUUIDv7(),
      date: `${now.slice(0, 4)}-${m[1].trim()}`,
      name: m[3].trim(),
      url,
      position: ++position,
      scraped_at: now,
    })
  }
  return items
}

export async function scrapeWaitingList(): Promise<WaitingListItem[]> {
  const browser = await puppeteer.connect({ browserWSEndpoint: BROWSER_WS })
  const page = await browser.newPage()
  await page.goto('https://distrowatch.com', { waitUntil: 'networkidle0' })
  const html = await page.content()
  await browser.disconnect()
  return parseWaitingListHtml(html)
}

export function insertWaitingList(items: WaitingListItem[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO waiting_list (id, date, name, url, position, scraped_at) VALUES (?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: WaitingListItem[]) => {
    for (const r of rows) {
      stmt.run(r.id, r.date, r.name, r.url, r.position, r.scraped_at)
    }
  })
  tx(items)
}

export function getLatestRankings(limit: number, slug?: string): Ranking[] {
  const db = getDb()
  const rows = db.query<any[], [number]>(
    slug
      ? 'SELECT * FROM rankings WHERE slug = ? ORDER BY scraped_at DESC, rank ASC LIMIT ?'
      : 'SELECT * FROM rankings ORDER BY scraped_at DESC, rank ASC LIMIT ?'
  ).all(...(slug ? [slug as any, limit] : [limit]))
  return rows.map((r: any) => ({
    ...r,
    based_on: (() => { try { const v = JSON.parse(r.based_on); return Array.isArray(v) ? v : [] } catch { return [] } })(),
  }))
}
