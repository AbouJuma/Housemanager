# Install (Local Development)

## Requirements

- PHP 8.2+ (target 8.3+ where available — see [ARCHITECTURE.md](ARCHITECTURE.md#1-core-architectural-decisions))
- Composer 2.x
- MySQL 8+ (MariaDB 10.4+ works for development)
- Apache or PHP's built-in server

## Steps

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` and point `DB_*` at your MySQL server, then create the database:

```sql
CREATE DATABASE realestate_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations, then build frontend assets (Bootstrap 5/Vite — see [DEPLOYMENT.md](DEPLOYMENT.md) for why Node never runs in production) and seed some demo data:

```bash
php artisan migrate
npm install && npm run build
php artisan db:seed
```

Serve the app:

```bash
php artisan serve
```

Run the test suite:

```bash
php artisan test
```

## Notes

- Queue, cache, and session all default to the `database` driver — no Redis/Memcached required. Run `php artisan queue:work` locally to process jobs, or rely on `php artisan schedule:work` to simulate the production cron loop (see [DEPLOYMENT.md](DEPLOYMENT.md)).
- Frontend assets are built with Vite at dev/build time only; the production server never runs Node (see [DEPLOYMENT.md](DEPLOYMENT.md)).
- There's no public sign-up page (see ARCHITECTURE.md §1's "Self-registration" row). After seeding, sign in at `/login` as:
  - **SaaS owner**: `test@example.com` / `password` — manages every company.
  - **A company admin**: `php artisan tinker --execute="echo App\Models\User::whereNotNull('company_id')->first()->email;"` to find one, password is also `password`.
