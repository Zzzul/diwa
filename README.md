<div align="center">
<h1>Diwa: unofficiall Distrowatch API</h1>
</div>

Diwa is an open-source project and simple unofficial API from the [Distrowatch](https://distrowatch.com/) site to get some public data of open-source operation systems like Linux, BSD, etc.

## Development Setup

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

## Production Setup

Requirement: [Bun](https://bun.sh) runtime.

```bash
# install deps
bun install --production

# build standalone binary (includes Bun runtime, zero system dep)
bun run build

# run migration + start server
bun start
```

Binary `./diwa` is self-contained. Deploy with `data/` (SQLite DB) and `migrations/` (SQL files for initial setup).

### Manual production start (without binary)

```bash
NODE_ENV=production bun run src/index.ts
```

## Systemd Service

Run as daemon with auto-restart.

### 1. Create user

```bash
sudo useradd -r -s /bin/false diwa
```

### 2. Place binary and migrations

```bash
sudo mkdir -p /opt/diwa/data
sudo cp diwa /opt/diwa/
sudo cp -r migrations /opt/diwa/
sudo chown -R diwa:diwa /opt/diwa
```

### 3. Create service file

Save as `/etc/systemd/system/diwa.service`:

```ini
[Unit]
Description=Diwa - Unofficial DistroWatch API
Documentation=https://github.com/zzzul/diwa
After=network.target

[Service]
Type=simple
WorkingDirectory=/opt/diwa
ExecStartPre=/opt/diwa/diwa db:migrate
ExecStart=/opt/diwa/diwa
Restart=on-failure
RestartSec=5
User=diwa
Group=diwa

# Logging — journald
StandardOutput=journal
StandardError=journal

# Log rotation (auto via journald)
# For file-based logging, uncomment:
# StandardOutput=append:/var/log/diwa.log
# StandardError=append:/var/log/diwa.log

# Security
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/opt/diwa/data
PrivateTemp=true

[Install]
WantedBy=multi-user.target
```

Or copy from repo:

```bash
sudo cp deploy/diwa.service /etc/systemd/system/
```

### 4. Start service

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now diwa
```

### 5. Verify

```bash
systemctl status diwa
```

### Logging

```bash
# tail logs
journalctl -u diwa -f

# last 100 lines
journalctl -u diwa -n 100

# log file (optional, edit service file to uncomment)
# StandardOutput=append:/var/log/diwa.log
# StandardError=append:/var/log/diwa.log
```

### Stop / restart

```bash
sudo systemctl stop diwa
sudo systemctl restart diwa
```

## Disclaimer

diwa is **not affiliated with, endorsed by, or connected to DistroWatch.com**. All data is scraped from publicly available pages.

**You are responsible for usage of this API.** Be respectful toward DistroWatch's servers — don't spam requests. No sponsor. No warranty. Open source under MIT.
