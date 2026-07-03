import { readdirSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { parseHtml, saveJson, insertDb } from '../lib/distrowatch'

const DATA_DIR = resolve(import.meta.dir, '..', '..', 'data', 'distrowatch')
const HTML_PATTERN = /^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}-\d{3}Z\.html$/

const files = readdirSync(DATA_DIR).filter((f) => HTML_PATTERN.test(f)).sort().reverse()
if (files.length === 0) {
  console.error('[err] no html files in', DATA_DIR)
  process.exit(1)
}

const htmlPath = resolve(DATA_DIR, files[0])
console.log(`[parse] reading: ${htmlPath}`)
const html = readFileSync(htmlPath, 'utf8')
const items = parseHtml(html)

if (items.length === 0) {
  console.error('[err] no ranking data found')
  process.exit(1)
}

saveJson(items)
insertDb(items)
console.log(`[done] ${items.length} entries saved`)
