import { Hono } from 'hono'
import rankingsRouter from './routes/rankings'
import newsRouter from './routes/news'

const app = new Hono()

const api = new Hono()

api.get('/healthz', (c) => c.json({ ok: true }))

api.route('/rankings', rankingsRouter)
api.route('/news', newsRouter)

app.route('/api', api)

app.notFound((c) => c.json({ error: 'not found' }, 404))
app.onError((err, c) => {
  console.error('[error]', err)
  return c.json({ error: err.message || 'internal error' }, 500)
})

export default app
