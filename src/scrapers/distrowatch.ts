import { $ } from 'bun'

const now = new Date()
const ts = now.toISOString().replace(/[:.]/g, '-')
const outDir = 'data/distrowatch'
const outFile = `${outDir}/${ts}.html`

await $`mkdir -p ${outDir}`
await $`obscura fetch https://distrowatch.com --dump html --output ${outFile}`

const f = Bun.file(outFile)
const size = await f.stat().then((s) => s.size)
console.log(`[scraper] distrowatch saved to ${outFile} (${size} bytes)`)
