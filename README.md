# Real Estate ERP SaaS

An enterprise-grade, multi-tenant Real Estate ERP built on Laravel 12, designed for landlords, property management companies, malls, commercial/office buildings, warehouses, hostels, hotels and mixed-use developments — and to run on ordinary shared hosting (Apache/cPanel/DirectAdmin), no Redis/Docker/Supervisor/Horizon/Node-in-production required.

## Documentation

| Doc | Purpose |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Full software/database/module/integration architecture (source of truth) |
| [docs/INSTALL.md](docs/INSTALL.md) | Local development setup |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Shared-hosting deployment (cPanel/DirectAdmin) |
| [docs/DATABASE_SCHEMA.md](docs/DATABASE_SCHEMA.md) | Table-by-table schema reference, grows per module |
| [docs/API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md) | REST API (`/api/v1`) reference, grows per module |
| [docs/USER_MANUAL.md](docs/USER_MANUAL.md) | End-user guide, grows per module |
| [docs/CHANGELOG.md](docs/CHANGELOG.md) | Per-module completion log |

## Status

**Phase A (Foundation), Phase B (Integration Backbone), Phase C (Core Real Estate Domain), and Phase D (Financial & Operations) are all complete, and Phase E (Portals, Reporting, API, AI) is underway** — Modules 1–29. Every Storage/Email/SMS/Payment provider in the catalog has a real, working driver. The real-estate domain spans Properties → Buildings → Units, Landlords, Tenants, Lease Management, Invoices, and Rent Collection (online gateway payments with a real webhook receiver and polling fallback, plus automatic tenant email/SMS reminders) — full CRUD (web + API) throughout, with the Dashboard showing real portfolio KPIs. Underneath all of it sits a real double-entry Accounting ledger (chart of accounts + journal entries, with proper void-via-reversal instead of editing history) that posts itself automatically the moment an invoice is sent or paid; Expenses feeds into the same ledger the other direction, recording bills and payments against it (unpaid ones tracked as Accounts Payable until settled); Vendors gives those expenses a real, reusable record to point at (bank details included, for when it's time to pay them), the same shape as Landlords for the money going the other way; Maintenance ties Property/Unit/Tenant/Vendor together into a real work-tracking record that can record its own actual cost as a genuine ledgered Expense, closing the loop from "something's broken" to "here's what it cost"; Inventory tracks real physical stock (an immutable receipt/usage/adjustment ledger, never a naive running total) that a maintenance request can draw parts from without ever going negative; Documents attaches real, downloadable files (contracts, deeds, ID scans, certificates with expiry tracking) to any Property, Landlord, Tenant, Lease, or Vendor, or keeps them standalone as company-wide records; and Visitor Management logs a real front-desk check-in/check-out record against a property, optionally against a tenant being visited or a vendor doing work. Phase E opens with two real, independent self-service portals — a Tenant Portal (its own `tenant` auth guard) where a tenant can view their own lease and invoices, pay online, report a maintenance issue, and see their own documents; and a Landlord Portal (its own `landlord` auth guard) giving landlords read-only visibility into their own properties, expenses, maintenance activity, and documents — plus a real Reports module: Rent Roll, Occupancy, Income Statement, and AR Aging, every one of them viewable on-screen and downloadable as a genuine PDF, Excel, or CSV file. Phase E now also includes Subscriptions & Billing: a real subscription-plan catalog, an immutable per-company subscription history, and the SaaS owner's own invoicing of companies for their platform subscription — with `companies.status` (suspended/cancelled) now actually enforced app-wide for the first time, cutting off access uniformly across every guard while always leaving a suspended user a way to log out. A full visual design pass also landed earlier (Stripe/Linear-inspired, self-hosted Inter font, dark-mode-consistent throughout). Public REST API hardening (Module 30) is next. See [docs/CHANGELOG.md](docs/CHANGELOG.md) for full progress against the roadmap in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md#9-module-build-order-roadmap).

## Quick Start

See [docs/INSTALL.md](docs/INSTALL.md).

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
```
