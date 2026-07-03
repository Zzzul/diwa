import { Hono } from 'hono'
import * as ctrl from '../controllers/news'

const router = new Hono()

router.get('/', ctrl.list)
router.get('/:id', ctrl.detail)

export default router
