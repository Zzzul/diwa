import { readFileSync, writeFileSync, mkdirSync } from 'node:fs'
import { resolve } from 'node:path'
import { $ } from 'bun'
import { getDb } from '../db/connection'
import type { Ranking } from '../models/rankings'
import type { News, NewsLink } from '../models/news'

const DATA_DIR = resolve(import.meta.dir, '..', '..', 'data', 'distrowatch')
const ROW_RE = /<tr>[\s\S]*?<th class="phr1">(\d+)<\/th>[\s\S]*?<td class="phr2"><a title="Based on: ([^"]*)" href="([^"]*)">([^<]+)<\/a><\/td>[\s\S]*?<td class="phr3" title="Yesterday: (\d+)">(\d+)<img[^>]*alt="([^"]*)"[^>]*><\/td>[\s\S]*?<\/tr>/g

function mapTrend(alt: string): string {
  if (alt === '>') return 'up'
  if (alt === '<') return 'down'
  return 'level'
}

export function parseHtml(html: string): Ranking[] {
  const now = new Date().toISOString()
  const items: Ranking[] = []
  let m: RegExpExecArray | null
  while ((m = ROW_RE.exec(html)) !== null) {
    const basedOn = m[2].split(',').map((s) => s.trim()).filter(Boolean)
    items.push({
      id: 0,
      rank: Number(m[1]),
      name: m[4],
      slug: m[3],
      based_on: basedOn,
      hpd: Number(m[6]),
      yesterday: Number(m[5]),
      trend: mapTrend(m[7]),
      scraped_at: now,
    })
  }
  return items
}

export async function fetchHtml(): Promise<string> {
  const now = new Date()
  const ts = now.toISOString().replace(/[:.]/g, '-')
  mkdirSync(DATA_DIR, { recursive: true })
  const outFile = `${DATA_DIR}/${ts}.html`
  await $`mkdir -p ${DATA_DIR}`
  await $`obscura fetch https://distrowatch.com --dump html --output ${outFile}`
  return readFileSync(outFile, 'utf8')
}

export function insertDb(items: Ranking[]): void {
  const db = getDb()
  const stmt = db.prepare(
    'INSERT INTO rankings (rank, name, slug, based_on, hpd, yesterday, trend, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: Ranking[]) => {
    for (const r of rows) {
      stmt.run(r.rank, r.name, r.slug, JSON.stringify(r.based_on), r.hpd, r.yesterday, r.trend, r.scraped_at)
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
      ? `http://localhost:3000/api/news/${idMatch[1]}`
      : distroMatch
        ? `http://localhost:3000/api/news/${distroMatch[1]}`
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
      id: 0,
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
    'INSERT INTO news (date, is_new, type, headline, headline_slug, headline_url, logo, screenshot, rating, text, text_html, links, scraped_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
  )
  const tx = db.transaction((rows: News[]) => {
    for (const r of rows) {
      stmt.run(r.date, r.is_new ? 1 : 0, r.type, r.headline, r.headline_slug, r.headline_url,
        r.logo, r.screenshot, r.rating, r.text, r.text_html, JSON.stringify(r.links), r.scraped_at)
    }
  })
  tx(items)
}

export async function fetchAndStoreNews(): Promise<News[]> {
  const html = await fetchHtml()
  const items = parseNewsHtml(html)
  if (items.length === 0) throw new Error('no news data in fetched html')
  saveJson(items, 'news')
  insertNews(items)
  return items
}

export async function fetchAndStore(): Promise<Ranking[]> {
  const html = await fetchHtml()
  const items = parseHtml(html)
  if (items.length === 0) throw new Error('no ranking data in fetched html')
  saveJson(items)
  insertDb(items)
  return items
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
