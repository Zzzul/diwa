import { Scalar } from '@scalar/hono-api-reference'
import type { Hono } from 'hono'

const spec = {
  openapi: '3.1.0',
  info: {
    title: 'Diwa API',
    version: '1.0.0',
    description: 'API for DistroWatch data — rankings, distributions, news',
  },
  servers: [{ url: 'http://localhost:3000', description: 'local dev' }],
  paths: {
    '/api/healthz': {
      get: {
        summary: 'Health check',
        responses: { '200': { description: 'OK', content: { 'application/json': { schema: { type: 'object', properties: { ok: { type: 'boolean' } } } } } } },
      },
    },
    '/api/rankings': {
      get: {
        summary: 'Rankings list',
        parameters: [
          { name: 'limit', in: 'query', schema: { type: 'integer', default: 100 } },
          { name: 'slug', in: 'query', schema: { type: 'string' }, description: 'filter by distro slug' },
          { name: 'dataspan', in: 'query', schema: { type: 'string', default: '26' }, description: 'time span filter' },
        ],
        responses: { '200': { description: 'Rankings' } },
      },
    },
    '/api/rankings/dataspans': {
      get: {
        summary: 'Available dataspan options',
        responses: { '200': { description: 'Dataspans list' } },
      },
    },
    '/api/news': {
      get: {
        summary: 'News list',
        parameters: [
          { name: 'limit', in: 'query', schema: { type: 'integer', default: 100 } },
          { name: 'type', in: 'query', schema: { type: 'string' }, description: 'filter by news type' },
        ],
        responses: { '200': { description: 'News list' } },
      },
    },
    '/api/news/{id}': {
      get: {
        summary: 'News detail',
        parameters: [{ name: 'id', in: 'path', required: true, schema: { type: 'string' } }],
        responses: { '200': { description: 'News detail' } },
      },
    },
    '/api/distributions': {
      get: {
        summary: 'Distribution list',
        responses: { '200': { description: 'List of all distributions' } },
      },
    },
    '/api/distributions/random': {
      get: {
        summary: 'Random distribution',
        responses: { '200': { description: 'Random distribution detail' } },
      },
    },
    '/api/distributions/latest': {
      get: {
        summary: 'Latest distributions',
        parameters: [{ name: 'limit', in: 'query', schema: { type: 'integer', default: 50 } }],
        responses: { '200': { description: 'Latest distro releases from homepage' } },
      },
    },
    '/api/distributions/{slug}': {
      get: {
        summary: 'Distribution detail',
        parameters: [{ name: 'slug', in: 'path', required: true, schema: { type: 'string' } }],
        responses: { '200': { description: 'Distribution detail' } },
      },
    },
  },
}

export function setupOpenApi(app: Hono): void {
  app.get('/api/doc', (c) => c.json(spec))
  app.get('/api/docs', Scalar({ url: '/api/doc', pageTitle: 'Diwa API Docs' }))
}
