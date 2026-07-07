import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { Hono } from 'hono'
import type { Context } from 'hono';
import type { NewsItem } from '../db/types.js';

const app = new Hono()

const ALLOWED_HOSTS = ['distrowatch.com', 'dw.com']

let cacheDir: string;

function isAllowed(url: string): boolean {
  try {
    const parsed = new URL(url)
    return ALLOWED_HOSTS.some(h => parsed.hostname.endsWith(h))
  } catch {
    return false
  }
}

export async function initCache(dir?: string): Promise<void> {
  cacheDir = resolve(dir || process.env.CACHE_DIR || './cache', 'images');
  if (!existsSync(cacheDir)) {
    mkdirSync(cacheDir, { recursive: true });
  }
}

function filenameFromUrl(url: string): string {
  const u = new URL(url);
  const parts = u.pathname.split('/');
  return parts[parts.length - 1] || 'unknown';
}

export async function serveImage(url: string | undefined, c: Context): Promise<Response> {
  if (!url) return c.text('missing url', 400);
  if (!isAllowed(url)) return c.text('host not allowed', 403);
  const name = filenameFromUrl(url);
  const filePath = resolve(cacheDir, name);

  if (existsSync(filePath)) {
    const buf = readFileSync(filePath);
    const ext = name.split('.').pop();
    const mime = ext === 'png' ? 'image/png' : ext === 'jpg' || ext === 'jpeg' ? 'image/jpeg' : ext === 'svg' ? 'image/svg+xml' : 'image/png';
    return new Response(buf, {
      headers: { 'content-type': mime, 'cache-control': 'public, max-age=31536000' },
    });
  }

  try {
    const res = await fetch(url, {
      headers: { 'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36' },
    });
    if (!res.ok) return new Response(await res.arrayBuffer(), { status: res.status });
    const buf = await res.arrayBuffer();
    writeFileSync(filePath, Buffer.from(buf));
    return new Response(buf, {
      headers: { 'content-type': res.headers.get('content-type') || 'image/png', 'cache-control': 'public, max-age=86400' },
    });
  } catch {
    return c.text('proxy error', 502);
  }
}

export async function preCacheImages(items: NewsItem[]): Promise<void> {
  if (!Array.isArray(items)) {
    console.warn('preCacheImages: items is not an array, got', typeof items, items)
    return
  }
  const urls: string[] = [];
  for (const item of items) {
    if (item.screenshot && isAllowed(item.screenshot)) urls.push(item.screenshot);
    if (item.logo && isAllowed(item.logo)) urls.push(item.logo);
  }
  for (const url of urls) {
    const name = filenameFromUrl(url);
    const filePath = resolve(cacheDir, name);
    if (existsSync(filePath)) continue;
    try {
      const res = await fetch(url, {
        headers: { 'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36' },
      });
      if (res.ok) {
        const buf = await res.arrayBuffer();
        writeFileSync(filePath, Buffer.from(buf));
        console.log(`  cached image: ${name}`);
      }
    } catch {
      // skip — will be cached on first proxy request
    }
  }
}

app.get('/image', async (c) => serveImage(c.req.query('url'), c))

export default app
