# Deployment (Shared Hosting — cPanel / DirectAdmin / Apache)

## Constraints this deployment respects

No Redis, Docker, Supervisor, Horizon, Node in production, or WebSockets — see [ARCHITECTURE.md](ARCHITECTURE.md#hosting-constraint).

## 1. Build assets locally, deploy the compiled output

```bash
npm install
npm run build
```

Deploy `public/build` along with the rest of the app. The production server never runs `npm`/`vite` — only the compiled static assets are served.

## 2. Point the document root at `public/`

cPanel/DirectAdmin: set the domain's document root to the app's `public/` directory (or symlink `public/` contents into `public_html/` if the host doesn't allow changing the document root).

## 3. Environment

Copy `.env.example` to `.env` on the server, set `APP_ENV=production`, `APP_DEBUG=false`, real `DB_*` credentials, and run:

```bash
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. The one cron entry that replaces Supervisor/Horizon

In cPanel's Cron Jobs UI, add a single entry running every minute:

```
* * * * * php /home/USER/path-to-app/artisan schedule:run >> /dev/null 2>&1
```

The Laravel Scheduler is responsible for everything that would otherwise need a persistent worker. Scheduled tasks are registered per-module (from each module's own `ServiceProvider::boot()`, not a shared `routes/console.php`), so adding or removing a module's scheduled work never touches a core file:

- Draining the `database` queue in short bursts (`queue:work --stop-when-empty --max-time=50`)
- `notifications:trial-ending` (daily, Module 12) — notifies company admins 3 days before their trial ends
- Rent-due checks, lease-expiry checks, subscription renewal checks, and any other periodic business-rule commands added by later modules

No second cron entry, no `supervisord.conf`, no `horizon` process to keep alive.

## 5. Storage

`php artisan storage:link` once, to expose `storage/app/public` as `public/storage`. If a company's `storage_disk` is a cloud disk (S3/R2/Spaces/GCS) instead, this step is irrelevant for that tenant — see [ARCHITECTURE.md §5](ARCHITECTURE.md#5-pluggable-integration-frameworks-built-starting-phase-b).

## 6. Zero-downtime-ish releases on shared hosting

Shared hosting has no blue/green deploy story. The safe sequence is: upload new code to a release folder → run `migrate --force` → `config:cache`/`route:cache`/`view:cache` → swap the symlink/document root atomically if the host supports it, otherwise `rsync` over the live folder during low traffic.
