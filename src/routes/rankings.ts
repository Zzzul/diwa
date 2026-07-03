import { Hono } from 'hono'
import * as ctrl from '../controllers/rankings'

const router = new Hono()

router.get('/', ctrl.list)
router.get('/:slug', ctrl.history)

export default router
