import type { Context } from 'hono'
import { findLatest, findBySlug } from '../models/rankings'
import { parseLimit } from '../lib/parse'

export function list(c: Context) {
  const limit = parseLimit(c.req.query('limit'), 100, 500)
  const slug = c.req.query('slug') || undefined
  const data = findLatest({ limit, slug })
  return c.json({ data, count: data.length })
}

export function history(c: Context) {
  const slug = c.req.param('slug')
  if (!slug) return c.json({ error: 'slug required' }, 400)
  const limit = parseLimit(c.req.query('limit'), 50, 500)
  const data = findBySlug(slug, limit)
  return c.json({ data, count: data.length, slug })
}
