import type { Context } from 'hono'
import { findLatest, findById } from '../models/news'
import { parseLimit } from '../lib/parse'

export function list(c: Context) {
  const limit = parseLimit(c.req.query('limit'), 50, 200)
  const type = c.req.query('type') || undefined
  const date = c.req.query('date') || undefined
  const data = findLatest({ limit, type, date })
  return c.json({ data, count: data.length })
}

export function detail(c: Context) {
  const raw = c.req.param('id')
  if (!raw) return c.json({ error: 'invalid id' }, 400)
  const id = Number.parseInt(raw, 10)
  if (!Number.isFinite(id) || id < 1) {
    return c.json({ error: 'invalid id' }, 400)
  }
  const row = findById(id)
  if (!row) return c.json({ error: 'not found' }, 404)
  return c.json({ data: row })
}
