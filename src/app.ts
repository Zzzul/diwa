import { Hono } from 'hono'
import rankingsRouter from './routes/rankings'
import newsRouter from './routes/news'

const app = new Hono()

app.get('/healthz', (c) => c.json({ ok: true }))

app.route('/rankings', rankingsRouter)
app.route('/news', newsRouter)

app.notFound((c) => c.json({ error: 'not found' }, 404))
app.onError((err, c) => {
  console.error('[error]', err)
  return c.json({ error: err.message || 'internal error' }, 500)
})

export default app
