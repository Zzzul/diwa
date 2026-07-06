<div align="center">
<h1>Diwa: unofficiall Distrowatch API</h1>
</div>

Diwa is an open-source project and simple unofficial API from the [Distrowatch](https://distrowatch.com/) site to get some public data of open-source operation systems like Linux, BSD, etc.

## Setup

```bash
bun install
bun run db:migrate
bun run dev
```

## Docs

Interactive API reference at `/api/docs`. Shows all endpoints, request params, response shapes.

## Showcase

Use diwa in your project? Open a PR to add yourself here.

## Contributing

PRs welcome. Keep it simple — this is a scraper API, not a framework.

## Caching

Data cached in SQLite for 12 hours. Not real-time with DistroWatch. Cron cleans stale data every hour.

If cache miss → fresh scrape. Response time 1–5s depends on DistroWatch speed, Obscura & Puppeteer overhead.

## Disclaimer

diwa is **not affiliated with, endorsed by, or connected to DistroWatch.com**. All data is scraped from publicly available pages.

**You are responsible for usage of this API.** Be respectful toward DistroWatch's servers — don't spam requests. No sponsor. No warranty. Open source under MIT.
