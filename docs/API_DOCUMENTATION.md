# API Documentation

REST API, versioned under `/api/v1/`, authenticated via Laravel Sanctum (`Authorization: Bearer <token>`). Grows incrementally per module — see [ARCHITECTURE.md §9](ARCHITECTURE.md#9-module-build-order-roadmap).

Every module's `Routes/api.php` is auto-mounted under `api/v1` with the `api` middleware group by `App\Core\Foundation\ModuleServiceProvider` — a module file only needs to declare its own routes, not the prefix.

## Companies — `/api/v1/companies`

Authorization mirrors `App\Modules\Companies\Policies\CompanyPolicy`: a token belonging to a user with `company_id === null` (SaaS-owner staff) can use every endpoint; a token belonging to a user with a `company_id` may only `show`/`update` that one company (`index`/`store`/`destroy` return `403`).

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/companies` | List (paginated, `?search=`) | SaaS-owner only |
| POST | `/api/v1/companies` | Create | SaaS-owner only; `slug` auto-generated/deduped if omitted |
| GET | `/api/v1/companies/{company:uuid}` | Show | Route-bound by `uuid`, not the internal id |
| PUT/PATCH | `/api/v1/companies/{company:uuid}` | Update | |
| DELETE | `/api/v1/companies/{company:uuid}` | Soft-delete | SaaS-owner only; `204 No Content` |

Response shape (`App\Modules\Companies\Http\Resources\CompanyResource`):

```json
{
  "data": {
    "uuid": "b042cfaf-247e-444c-a1ba-7bb2e14ee59b",
    "name": "Acme Property Group",
    "slug": "acme-property-group",
    "email": null,
    "phone": null,
    "website": null,
    "address": { "line1": null, "line2": null, "city": null, "state": null, "postal_code": null, "country_code": null },
    "timezone": "UTC",
    "locale": "en",
    "currency_code": "USD",
    "branding": { "logo_url": null, "primary_color": null, "secondary_color": null },
    "status": "trial",
    "trial_ends_at": "2026-08-15T00:00:00+00:00",
    "created_at": "2026-08-01T00:00:00+00:00",
    "updated_at": "2026-08-01T00:00:00+00:00"
  }
}
```

Branding (logo upload, colors) is web-only for now (`PUT /companies/{company}/branding`, multipart form) — no API endpoint yet; add one when a headless client needs it.

## Users — `/api/v1/users`

Authorization mirrors `App\Modules\Users\Policies\UserPolicy`: SaaS-owner staff can use every endpoint across every company; a company user needs the matching `users.*` Spatie permission **and** the target must be in their own company (`index`/`show`/`store`/`update`/`destroy` all return `403` otherwise). A user can never delete their own account.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/users` | List (paginated, `?search=`) | SaaS-owner sees every company; a company user sees only their own |
| POST | `/api/v1/users` | Create | `company_id` accepted only for a SaaS-owner caller; a company user's new user is always forced into their own company |
| GET | `/api/v1/users/{user:uuid}` | Show | |
| PUT/PATCH | `/api/v1/users/{user:uuid}` | Update | Accepts `roles: string[]` (role names) to sync in the same request |
| DELETE | `/api/v1/users/{user:uuid}` | Soft-delete | `204 No Content` |

Response shape (`App\Modules\Users\Http\Resources\UserResource`):

```json
{
  "data": {
    "uuid": "b042cfaf-247e-444c-a1ba-7bb2e14ee59b",
    "name": "Jane Admin",
    "email": "jane@example.com",
    "company": { "uuid": "...", "name": "Acme Property Group" },
    "roles": ["Admin"],
    "created_at": "2026-08-01T00:00:00+00:00",
    "updated_at": "2026-08-01T00:00:00+00:00"
  }
}
```

## Roles — web only, no API yet

`roles.*` (index/create/store/edit/update/destroy) exists only as Bootstrap 5 web routes for now — a deliberate scope cut in Module 3. Add an `/api/v1/roles` surface, mirroring the Users API's shape, when a headless client needs role/permission management.

## Settings — web only, no API yet

`/settings` (company) and `/settings/system` (SaaS-owner) exist only as Bootstrap 5 web routes — same deliberate scope cut as Roles. Add an API surface when a headless client needs to read/write settings.

## Audit Logs — web only, no API yet

`/audit-logs` exists only as a Bootstrap 5 web route — same deliberate scope cut as Roles/Settings. Add an API surface when a headless client needs to read activity history.

## Integrations — web only, no API yet

`/integrations` (hub/edit/update/test) exists only as a Bootstrap 5 web route — same deliberate scope cut as Roles/Settings/Audit Logs, and for the same reason: a company's own integration credentials only ever need managing by that company's own admins through the UI, not by a headless client. See ARCHITECTURE.md §5a.

## Storage — no API, internal service only

Module 8 has no HTTP surface of its own, API or web — `App\Modules\Storage\Services\StorageResolver` is consumed directly by other modules' services (Companies branding uploads today) to decide which disk a company's files live on. See ARCHITECTURE.md §5b.

## Email — no API, internal service only

Module 9 has no HTTP surface of its own either — `App\Modules\Email\Services\EmailResolver` is consumed by the Integrations Center's Test Connection action today, and will be consumed by whichever module actually sends business email (Notifications, Module 12). See ARCHITECTURE.md §5c.

## SMS — no API, internal service only

Module 10 has no HTTP surface of its own — `App\Modules\Sms\Services\SmsManager::send()` is ready for whichever module actually sends business SMS (Notifications, Module 12; rent reminders in Rent Collection) to call, and all six drivers double as the Integrations Center's Test Connection check today. See ARCHITECTURE.md §5d.

## Payments — no API, internal service only

Module 11 has no HTTP surface of its own — `App\Modules\Payments\Services\PaymentManager` (`charge`/`verify`/`refund`) is ready for whichever module actually moves business money (Rent Collection, Module 18) to call, and all twelve drivers double as the Integrations Center's Test Connection check today (five need a Base URL supplied via their credential form first — see ARCHITECTURE.md §5e). See ARCHITECTURE.md §5e.

## Notifications — web only, no API yet

`/notifications` (index/recent/read/mark-all-read) exists only as Bootstrap 5 web routes — same deliberate scope cut as Roles/Settings/Audit Logs/Integrations, and for the same reason: notifications are inherently personal to the signed-in web session, not something a headless client needs yet. See ARCHITECTURE.md §5f.

## Properties, Buildings & Units — `/api/v1/properties`, `/api/v1/buildings`, `/api/v1/units`

Authorization mirrors `PropertyPolicy`/`BuildingPolicy`/`UnitPolicy` (ARCHITECTURE.md §10a) — one `properties.*` permission group governs all three, and SaaS-owner staff have no access (a property only makes sense within one specific company).

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/properties` | List (paginated, `?search=&type=&status=`) | Includes `buildings_count`/`units_count` |
| POST | `/api/v1/properties` | Create | |
| GET | `/api/v1/properties/{property:uuid}` | Show | |
| PUT/PATCH | `/api/v1/properties/{property:uuid}` | Update | |
| DELETE | `/api/v1/properties/{property:uuid}` | Soft-delete | `204 No Content` |
| GET | `/api/v1/properties/{property:uuid}/buildings` | List a property's buildings | |
| POST | `/api/v1/properties/{property:uuid}/buildings` | Create a building | |
| GET | `/api/v1/buildings/{building:uuid}` | Show | Standalone once it exists — doesn't need the property in the URL |
| PUT/PATCH | `/api/v1/buildings/{building:uuid}` | Update | |
| DELETE | `/api/v1/buildings/{building:uuid}` | Soft-delete | |
| GET | `/api/v1/buildings/{building:uuid}/units` | List a building's units | |
| POST | `/api/v1/buildings/{building:uuid}/units` | Create a unit | |
| GET | `/api/v1/units/{unit:uuid}` | Show | Standalone once it exists |
| PUT/PATCH | `/api/v1/units/{unit:uuid}` | Update | |
| DELETE | `/api/v1/units/{unit:uuid}` | Soft-delete | |

Response shape (`PropertyResource`):

```json
{
  "data": {
    "uuid": "b042cfaf-247e-444c-a1ba-7bb2e14ee59b",
    "name": "Sunset Apartments",
    "type": "residential",
    "status": "active",
    "description": null,
    "address": { "line1": null, "line2": null, "city": "Dar es Salaam", "state": null, "postal_code": null, "country_code": "TZ" },
    "latitude": null,
    "longitude": null,
    "total_area": "1200.00",
    "area_unit": "sqm",
    "cover_photo_url": null,
    "buildings_count": 2,
    "units_count": 14,
    "created_at": "2026-08-03T00:00:00+00:00",
    "updated_at": "2026-08-03T00:00:00+00:00"
  }
}
```

`BuildingResource`/`UnitResource` follow the same shape as their web forms (ARCHITECTURE.md §10a) — see `App\Modules\Properties\Http\Resources`.

## Landlords — `/api/v1/landlords`

Authorization mirrors `LandlordPolicy` (ARCHITECTURE.md §10b) — its own `landlords.*` permission group, separate from `properties.*`. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/landlords` | List (paginated, `?search=&type=&status=`) | Includes `properties_count` |
| POST | `/api/v1/landlords` | Create | Individual `bank_*` fields (see below) assembled into `bank_details` server-side |
| GET | `/api/v1/landlords/{landlord:uuid}` | Show | |
| PUT/PATCH | `/api/v1/landlords/{landlord:uuid}` | Update | |
| DELETE | `/api/v1/landlords/{landlord:uuid}` | Soft-delete | `204 No Content` |

Request body accepts `bank_name`, `bank_account_name`, `bank_account_number`, `bank_branch`, `bank_swift_code` as flat fields — `LandlordService` assembles them into the single encrypted `bank_details` column.

Response shape (`LandlordResource`) — **`bank_details` is returned in full, not masked**, unlike an Integration's credentials (ARCHITECTURE.md §5a vs §10b): this is the calling company's own reference data about its landlord, not a third-party secret.

```json
{
  "data": {
    "uuid": "b042cfaf-247e-444c-a1ba-7bb2e14ee59b",
    "name": "Jane Doe",
    "type": "individual",
    "status": "active",
    "email": "jane@example.com",
    "phone": null,
    "secondary_phone": null,
    "address": { "line1": null, "line2": null, "city": null, "state": null, "postal_code": null, "country_code": null },
    "tax_id": null,
    "bank_details": {
      "bank_name": "CRDB Bank",
      "account_name": "Jane Doe",
      "account_number": "1234567890",
      "branch": "Dar es Salaam",
      "swift_code": "CORUTZTZ"
    },
    "notes": null,
    "photo_url": null,
    "properties_count": 2,
    "created_at": "2026-08-04T00:00:00+00:00",
    "updated_at": "2026-08-04T00:00:00+00:00"
  }
}
```

`PropertyResource` (Module 13) now also includes a `landlord: { uuid, name }` field when the relation is loaded.

## Tenants — `/api/v1/tenants`

Authorization mirrors `TenantPolicy` (ARCHITECTURE.md §10c) — its own `tenants.*` permission group, separate from `properties.*` and `landlords.*`. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/tenants` | List (paginated, `?search=&type=&status=`) | `status` includes `blacklisted` |
| POST | `/api/v1/tenants` | Create | |
| GET | `/api/v1/tenants/{tenant:uuid}` | Show | |
| PUT/PATCH | `/api/v1/tenants/{tenant:uuid}` | Update | |
| DELETE | `/api/v1/tenants/{tenant:uuid}` | Soft-delete | `204 No Content` |

Response shape (`TenantResource`) — **`identification_number` is returned in full, not masked**, unlike an Integration's credentials (ARCHITECTURE.md §5a vs §10c): this is the calling company's own screening record about its tenant, not a third-party secret. There is no `unit_id`/`property_id` field yet — see ARCHITECTURE.md §10c.

```json
{
  "data": {
    "uuid": "c153dfaf-358f-555d-b2cb-8cc3f25ff6ac",
    "name": "Jane Doe",
    "type": "individual",
    "status": "active",
    "email": "jane@example.com",
    "phone": null,
    "secondary_phone": null,
    "date_of_birth": "1990-01-01",
    "identification_type": "national_id",
    "identification_number": "ID-12345678",
    "address": { "line1": null, "line2": null, "city": null, "state": null, "postal_code": null, "country_code": null },
    "emergency_contact": { "name": null, "phone": null },
    "notes": null,
    "photo_url": null,
    "created_at": "2026-08-05T00:00:00+00:00",
    "updated_at": "2026-08-05T00:00:00+00:00"
  }
}
```

## Leases — `/api/v1/leases`

Authorization mirrors `LeasePolicy` (ARCHITECTURE.md §10d) — its own `leases.*` permission group, separate from `properties.*` and `tenants.*`. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/leases` | List (paginated, `?search=&status=`) | `search` matches tenant name or unit number |
| POST | `/api/v1/leases` | Create | Requires a vacant `unit_id` and a non-blacklisted `tenant_id` — see below. Marks the unit `occupied`. |
| GET | `/api/v1/leases/{lease:uuid}` | Show | |
| PUT/PATCH | `/api/v1/leases/{lease:uuid}` | Update | `tenant_id`/`unit_id` cannot be changed — not accepted by this endpoint |
| DELETE | `/api/v1/leases/{lease:uuid}` | Soft-delete | `204 No Content` |

Creating a lease validates `tenant_id` against the caller's own company **and** `status != 'blacklisted'`, and `unit_id` against the caller's own company **and** `status = 'vacant'` — both scoped `Rule::exists` checks (ARCHITECTURE.md §10d), not just UI-level filtering, so a direct API call can't bypass either rule. Updating to `status: "terminated"` auto-sets `terminated_at` and frees the unit back to `vacant`; a lease also auto-expires the day after `end_date` passes (`ExpireLeases`, scheduled daily), applying the same unit-freeing logic.

```json
{
  "data": {
    "uuid": "c8c5d21d-7925-495f-91f6-73ba7083752b",
    "status": "active",
    "start_date": "2026-08-01",
    "end_date": "2027-08-01",
    "billing_cycle": "monthly",
    "rent_amount": 750.0,
    "deposit_amount": null,
    "terminated_at": null,
    "termination_reason": null,
    "notes": null,
    "tenant": { "uuid": "ea2e54a1-1cff-467a-8a40-bf512aac569b", "name": "Jane Doe" },
    "unit": {
      "uuid": "bb4f680a-68e1-4fb8-ae67-d72000b07ee7",
      "unit_number": "12A",
      "building": {
        "uuid": "…",
        "name": "Block A",
        "property": { "uuid": "…", "name": "Riverside Apartments" }
      }
    },
    "created_at": "2026-08-06T00:00:00+00:00",
    "updated_at": "2026-08-06T00:00:00+00:00"
  }
}
```

`unit`/`building`/`property` nesting is only present `whenLoaded` — the index endpoint eager-loads `tenant` and `unit.building.property`, so it's always populated on list/show responses.

## Invoices — `/api/v1/invoices`

Authorization mirrors `InvoicePolicy` (ARCHITECTURE.md §10e) — its own `invoices.*` permission group, separate from `leases.*`. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/invoices` | List (paginated, `?search=&status=`) | `search` matches invoice number, tenant name, or unit number |
| POST | `/api/v1/invoices` | Create | `items` is a required array of `{description, quantity, unit_price}`; `total_amount` is computed server-side |
| GET | `/api/v1/invoices/{invoice:uuid}` | Show | |
| PUT/PATCH | `/api/v1/invoices/{invoice:uuid}` | Update | Rejected with a 422 unless the invoice is still `draft` (ARCHITECTURE.md §10e). `lease_id` cannot be changed. |
| DELETE | `/api/v1/invoices/{invoice:uuid}` | Soft-delete | Rejected with a 422 unless the invoice is `draft` or `cancelled`. `204 No Content` on success. |
| POST | `/api/v1/invoices/{invoice:uuid}/send` | Transition `draft` → `sent` | Rejected with a 422 if not currently `draft` |
| POST | `/api/v1/invoices/{invoice:uuid}/cancel` | Transition → `cancelled` | Rejected with a 422 if already `paid` or `cancelled` |
| POST | `/api/v1/invoices/{invoice:uuid}/payments` | Record a payment | Body: `{amount, paid_on, method, reference?, notes?}`. Rejected with a 422 unless the invoice is `sent`/`partially_paid`/`overdue`. Moves `status` to `partially_paid` or `paid` automatically — never accepted as input. |

`status` is never accepted directly by the create/update endpoints — it only changes through the `send`/`cancel`/`payments` actions above, so `amount_paid` and `status` can never disagree (ARCHITECTURE.md §10e).

```json
{
  "data": {
    "uuid": "b3cae6b4-1e1d-4428-bc53-f4529be31b68",
    "invoice_number": "INV-000022",
    "status": "paid",
    "issue_date": "2026-08-01",
    "due_date": "2026-08-15",
    "total_amount": 520.0,
    "amount_paid": 520.0,
    "balance_due": 0.0,
    "notes": null,
    "lease": {
      "uuid": "8d9462ed-e3da-4e61-bbe4-be95ac5a37fb",
      "tenant": { "uuid": "…", "name": "Jane Doe" },
      "unit": { "uuid": "…", "unit_number": "12A" }
    },
    "items": [
      { "uuid": "…", "description": "Rent - August 2026", "quantity": 1.0, "unit_price": 500.0, "amount": 500.0 },
      { "uuid": "…", "description": "Water", "quantity": 1.0, "unit_price": 20.0, "amount": 20.0 }
    ],
    "payments": [
      { "uuid": "…", "amount": 520.0, "paid_on": "2026-08-02", "method": "cash", "reference": null, "notes": null, "recorded_by": "Jane Admin" }
    ],
    "created_at": "2026-08-01T00:00:00+00:00",
    "updated_at": "2026-08-02T00:00:00+00:00"
  }
}
```

`items`/`payments` are only present `whenLoaded` — `show` eager-loads both, `index`/`store`/`update` eager-load `items` only (payments aren't relevant until an invoice has been sent).

## Rent Collection — `/api/v1/invoices/{invoice}/charge`, `/api/v1/invoices/{invoice}/payment-attempts/{attempt}/verify`

Authorization uses the same `invoices.update` permission as every other Invoice action (ARCHITECTURE.md §11) — no separate permission group.

| Method | URI | Action | Notes |
|---|---|---|---|
| POST | `/api/v1/invoices/{invoice:uuid}/charge` | Initiate an online charge | Body: `{amount}` (required, ≤ balance due). Rejected with a 422 if the invoice isn't `sent`/`partially_paid`/`overdue`, the amount is invalid, or the company has no active payment integration. |
| POST | `/api/v1/invoices/{invoice:uuid}/payment-attempts/{attempt:uuid}/verify` | Re-check a pending attempt against its gateway | Returns the full `InvoiceResource` — a newly-confirmed attempt is reflected in `status`/`amount_paid`/`balance_due` immediately. |

`charge` never returns a `paid`/`successful` result synchronously, even for gateways that respond immediately — every real driver in this app's catalog reports its initial charge as "accepted, awaiting confirmation," not "money received" (ARCHITECTURE.md §11). Poll `verify`, or wait for the webhook below.

```json
{
  "data": {
    "uuid": "8f2c1a3e-...",
    "status": "pending",
    "provider": "stripe",
    "reference": "cs_1",
    "message": "Checkout session created."
  }
}
```

## Payment Webhook — `POST /api/v1/webhooks/payments/{provider}`

Public, unauthenticated (no Sanctum token — this is called by the payment gateway itself, not an app user), throttled to 60 requests/minute. Never trusts the payload's claimed outcome — it only extracts a `reference` to identify which pending `InvoicePaymentAttempt` to re-check, then re-verifies against the real gateway API before recording anything (ARCHITECTURE.md §11). Configure this URL (with the correct `{provider}` segment, e.g. `.../webhooks/payments/stripe`) as the callback/webhook URL in each gateway's own dashboard, or in that integration's `webhook_url` field.

Always returns `200` (even for an unmatched/already-resolved reference — there's nothing wrong from the gateway's perspective) except `422` when no reference could be extracted from the payload at all.

## Accounts — `/api/v1/accounts`

Authorization mirrors `AccountPolicy` (ARCHITECTURE.md §12) — its own `accounts.*` permission group. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/accounts` | List (paginated, `?search=&type=`) | Includes each account's live `balance` |
| POST | `/api/v1/accounts` | Create | `code` must be unique within the company |
| GET | `/api/v1/accounts/{account:uuid}` | Show | |
| PUT/PATCH | `/api/v1/accounts/{account:uuid}` | Update | |
| DELETE | `/api/v1/accounts/{account:uuid}` | Soft-delete | `204 No Content` |

```json
{
  "data": {
    "uuid": "9a1f2c3e-...",
    "code": "1200",
    "name": "Accounts Receivable",
    "type": "asset",
    "description": null,
    "is_active": true,
    "balance": 650.0,
    "created_at": "2026-08-09T00:00:00+00:00",
    "updated_at": "2026-08-09T00:00:00+00:00"
  }
}
```

## Journal Entries — `/api/v1/journal-entries`

Authorization mirrors `JournalEntryPolicy` (ARCHITECTURE.md §12) — its own `journal-entries.*` permission group, with `void` as its own permission (there's no `update` action to fold it into — a posted entry is immutable).

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/journal-entries` | List (paginated, `?search=&status=`) | |
| POST | `/api/v1/journal-entries` | Post a manual entry | Body: `{entry_date, reference?, description, lines: [{account_id, debit, credit, memo?}, ...]}` (min 2 lines). Rejected with a 422 unless debits and credits balance and total more than zero, and no single line is both a debit and a credit. |
| GET | `/api/v1/journal-entries/{entry:uuid}` | Show | |
| POST | `/api/v1/journal-entries/{entry:uuid}/void` | Void | Body: `{reason?}`. Posts a real reversing entry and marks this one `voided` — never edits or deletes it. |

```json
{
  "data": {
    "uuid": "12a057be-110c-4219-ae2b-85dca81661bc",
    "entry_date": "2026-08-04",
    "reference": null,
    "description": "Rent invoice INV-000023 issued",
    "status": "posted",
    "source_type": "App\\Modules\\Invoices\\Models\\Invoice",
    "total": 650.0,
    "created_by": null,
    "lines": [
      { "uuid": "...", "account": { "uuid": "...", "code": "1200", "name": "Accounts Receivable" }, "debit": 650.0, "credit": 0.0, "memo": null },
      { "uuid": "...", "account": { "uuid": "...", "code": "4000", "name": "Rental Income" }, "debit": 0.0, "credit": 650.0, "memo": null }
    ],
    "created_at": "2026-08-04T00:00:00+00:00"
  }
}
```

`created_by` is `null` for automatically-posted entries (revenue recognition, cash receipts) — only manual entries have a posting user.

## Expenses — `/api/v1/expenses`

Authorization mirrors `ExpensePolicy` (ARCHITECTURE.md §13) — its own `expenses.*` permission group. No `delete` endpoint — an expense always has at least one posted journal entry, so it can never be hard-deleted; `void` is the only way to retract one.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/expenses` | List (paginated, `?search=&property_id=&payment_status=`) | |
| POST | `/api/v1/expenses` | Record an expense | Posts its ledger entry immediately — `Dr {category} / Cr Accounts Payable` if `payment_status: unpaid`, or `Dr {category} / Cr Cash or Bank` if `paid` (`payment_method` required when `paid`) |
| GET | `/api/v1/expenses/{expense:uuid}` | Show | |
| PUT/PATCH | `/api/v1/expenses/{expense:uuid}` | Update | Only `property_id`/`vendor_id`/`vendor_name`/`description`/`reference`/`notes` — `account_id`/`amount`/`expense_date`/`payment_status` are not accepted |
| POST | `/api/v1/expenses/{expense:uuid}/mark-paid` | Settle an unpaid expense | Body: `{payment_method, paid_on}`. Posts `Dr Accounts Payable / Cr Cash or Bank`. Rejected with a 422 if already paid or voided. |
| POST | `/api/v1/expenses/{expense:uuid}/void` | Void | Body: `{reason?}`. Reverses every journal entry this expense posted (initial + settlement, if any). Rejected with a 422 if already voided. |

`vendor_id` (optional, scoped to the caller's company) and `vendor_name` (free text) both exist on the same record and neither requires the other — see ARCHITECTURE.md §14. `vendor_display_name` in the response is the one field to read regardless of which was used: `vendor?.name ?? vendor_name`.

```json
{
  "data": {
    "uuid": "e2acb53f-edbd-4a46-9f15-6d6062142c0e",
    "description": "Plumbing repair",
    "vendor_name": null,
    "vendor_display_name": "Ace Plumbing Co",
    "amount": 225.5,
    "expense_date": "2026-08-04",
    "payment_status": "paid",
    "payment_method": "bank_transfer",
    "paid_on": "2026-08-04",
    "reference": null,
    "notes": null,
    "status": "active",
    "account": { "uuid": "...", "code": "5000", "name": "Maintenance Expense" },
    "property": { "uuid": "...", "name": "Riverside Apartments" },
    "vendor": { "uuid": "...", "name": "Ace Plumbing Co" },
    "recorded_by": "Jane Admin",
    "created_at": "2026-08-04T00:00:00+00:00",
    "updated_at": "2026-08-04T00:00:00+00:00"
  }
}
```

## Vendors — `/api/v1/vendors`

Authorization mirrors `VendorPolicy` (ARCHITECTURE.md §14) — its own `vendors.*` permission group, separate from `expenses.*`. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/vendors` | List (paginated, `?search=&category=&status=`) | Includes `expenses_count` |
| POST | `/api/v1/vendors` | Create | Individual `bank_*` fields (see Landlords) assembled into `bank_details` server-side |
| GET | `/api/v1/vendors/{vendor:uuid}` | Show | |
| PUT/PATCH | `/api/v1/vendors/{vendor:uuid}` | Update | |
| DELETE | `/api/v1/vendors/{vendor:uuid}` | Soft-delete | `204 No Content` |

Response shape (`VendorResource`) mirrors `LandlordResource` closely — **`bank_details` is returned in full, not masked** (ARCHITECTURE.md §10b vs §14): this is the calling company's own reference data about its vendor, not a third-party secret.

```json
{
  "data": {
    "uuid": "8a5d0c06-f4e3-4ef2-b31b-2fb1881c5f21",
    "name": "Ace Plumbing Co",
    "type": "company",
    "category": "plumbing",
    "status": "active",
    "email": "contact@aceplumbing.test",
    "phone": null,
    "secondary_phone": null,
    "address": { "line1": null, "line2": null, "city": null, "state": null, "postal_code": null, "country_code": null },
    "tax_id": null,
    "bank_details": {
      "bank_name": "CRDB Bank",
      "account_name": null,
      "account_number": "99988877",
      "branch": null,
      "swift_code": null
    },
    "notes": null,
    "photo_url": null,
    "expenses_count": 1,
    "created_at": "2026-08-11T00:00:00+00:00",
    "updated_at": "2026-08-11T00:00:00+00:00"
  }
}
```

## Maintenance — `/api/v1/maintenance-requests`

Authorization mirrors `MaintenanceRequestPolicy` (ARCHITECTURE.md §15) — its own `maintenance.*` permission group. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/maintenance-requests` | List (paginated, `?search=&property_id=&status=&priority=`) | |
| POST | `/api/v1/maintenance-requests` | Log a request | `property_id` required; `unit_id`/`tenant_id`/`vendor_id` optional |
| GET | `/api/v1/maintenance-requests/{maintenance_request:uuid}` | Show | |
| PUT/PATCH | `/api/v1/maintenance-requests/{maintenance_request:uuid}` | Update | `status` transitioning to `completed` auto-sets `completed_date` unless one is supplied |
| DELETE | `/api/v1/maintenance-requests/{maintenance_request:uuid}` | Soft-delete | `204 No Content`. Ordinary delete — no voiding, unlike Expense |
| POST | `/api/v1/maintenance-requests/{maintenance_request:uuid}/record-expense` | Record the actual cost | Body: `{account_id, amount, expense_date, payment_status, payment_method?, paid_on?}`. Creates a real `Expense` via `ExpenseService::create()` (posts to the ledger, ARCHITECTURE.md §13), links it back via `expense_id`, and copies its amount into `actual_cost`. Rejected with a 422 if the request already has a linked expense. |

```json
{
  "data": {
    "uuid": "ee2f2eaa-a3c3-4bdf-a64a-0e770b2f5c88",
    "title": "Leaking kitchen faucet",
    "description": null,
    "category": "plumbing",
    "priority": "high",
    "status": "completed",
    "reported_date": "2026-08-04",
    "scheduled_date": null,
    "completed_date": "2026-08-04",
    "estimated_cost": null,
    "actual_cost": 175.5,
    "notes": null,
    "property": { "uuid": "...", "name": "Riverside Apartments" },
    "unit": null,
    "tenant": null,
    "vendor": { "uuid": "...", "name": "Ace Plumbing Co" },
    "expense": { "uuid": "...", "amount": 175.5, "payment_status": "paid" },
    "reported_by": "Jane Admin",
    "created_at": "2026-08-04T00:00:00+00:00",
    "updated_at": "2026-08-04T00:00:00+00:00"
  }
}
```

## Inventory — `/api/v1/inventory-items`

Authorization mirrors `InventoryItemPolicy` (ARCHITECTURE.md §16) — its own `inventory.*` permission group. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/inventory-items` | List (paginated, `?search=&category=&status=&low_stock=1`) | |
| POST | `/api/v1/inventory-items` | Add an item | `quantity_on_hand` is not an accepted field — every new item starts at zero, regardless of what's sent |
| GET | `/api/v1/inventory-items/{inventory_item:uuid}` | Show | Includes `recent_transactions` and `transactions_count` |
| PUT/PATCH | `/api/v1/inventory-items/{inventory_item:uuid}` | Update | `quantity_on_hand` is silently ignored if sent — it can only move via `record-transaction` |
| DELETE | `/api/v1/inventory-items/{inventory_item:uuid}` | Soft-delete | `204 No Content`. Ordinary delete — its transaction history is untouched |
| POST | `/api/v1/inventory-items/{inventory_item:uuid}/record-transaction` | Record a stock movement | Body: `{type, quantity, direction?, reason?, maintenance_request_id?, transaction_date}`. `type` is `receipt`/`usage`/`adjustment`; `quantity` is always a positive magnitude — `direction` (`increase`/`decrease`) is required only when `type` is `adjustment`, and `reason` is required then too. Rejected with a 422 if the resulting balance would go below zero. |

```json
{
  "data": {
    "uuid": "facf1b1e-3485-413d-95f9-a2826cf7caff",
    "name": "Air Filter",
    "sku": null,
    "category": "hardware",
    "unit_of_measure": "piece",
    "quantity_on_hand": 15.0,
    "reorder_level": 5.0,
    "unit_cost": 12.5,
    "is_low_stock": false,
    "status": "active",
    "notes": null,
    "property": { "uuid": "...", "name": "Riverside Apartments" },
    "vendor": { "uuid": "...", "name": "Ace Plumbing Co" },
    "transactions_count": 3,
    "recent_transactions": [
      { "uuid": "...", "type": "adjustment", "quantity": -2.0, "quantity_after": 15.0, "transaction_date": "2026-08-04" },
      { "uuid": "...", "type": "usage", "quantity": -3.0, "quantity_after": 17.0, "transaction_date": "2026-08-04" },
      { "uuid": "...", "type": "receipt", "quantity": 20.0, "quantity_after": 20.0, "transaction_date": "2026-08-04" }
    ],
    "created_at": "2026-08-04T00:00:00+00:00",
    "updated_at": "2026-08-04T00:00:00+00:00"
  }
}
```

## Tenant Portal — web only, no API

`portal.*` (login/logout/dashboard/leases/invoices/pay/maintenance-requests/documents) exists only as Bootstrap 5 web routes under `/portal`, gated by its own `tenant` auth guard - a deliberate scope cut in Module 26 (see ARCHITECTURE.md §19). A tenant-facing JSON API (for a future mobile app, say) would be its own later module, not bolted onto this one; Module 30 (Public REST API hardening) is scoped to the staff-facing API instead.

## Landlord Portal — web only, no API

`landlord-portal.*` (login/logout/dashboard/properties/expenses/maintenance-requests/documents) exists only as Bootstrap 5 web routes under `/landlord-portal`, gated by its own `landlord` auth guard - the same scope cut as Module 26, for the same reasoning (see ARCHITECTURE.md §20).

## Reports — web only, no API

`reports.*` (the hub plus Rent Roll/Occupancy/Income Statement/AR Aging, each with a PDF/Excel/CSV export action) exists only as Bootstrap 5 web routes under `/reports` - a report's natural interface is a browser view or a file download, not a JSON payload (see ARCHITECTURE.md §21).

## Documents — `/api/v1/documents`

Authorization mirrors `DocumentPolicy` (ARCHITECTURE.md §17) — its own `documents.*` permission group. SaaS-owner staff have no access. `POST`/`PUT` bodies here are `multipart/form-data`, not JSON, since both accept a `file` upload.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/documents` | List (paginated, `?search=&category=&documentable_type=&expiring_soon=1`) | `documentable_type` is one of the five short keys below |
| POST | `/api/v1/documents` | Upload a document | `file` required (pdf/doc/docx/xls/xlsx/jpg/jpeg/png, max 10MB). `documentable_type`/`documentable_id` optional — omit both for a standalone, company-wide document |
| GET | `/api/v1/documents/{document:uuid}` | Show | |
| PUT/PATCH | `/api/v1/documents/{document:uuid}` | Update | `documentable_type`/`documentable_id` are not accepted — fixed at upload time. `file` is optional; the existing one is kept if omitted |
| DELETE | `/api/v1/documents/{document:uuid}` | Soft-delete | `204 No Content` |

`documentable_type` accepts/returns one of five short keys, not a raw class name: `property`, `landlord`, `tenant`, `lease`, `vendor`.

```json
{
  "data": {
    "uuid": "f9c3d51a-9fea-44f3-a39e-62269aee5438",
    "title": "Property Deed",
    "category": "deed",
    "description": null,
    "expiry_date": null,
    "is_expired": false,
    "is_expiring_soon": false,
    "documentable_type": "property",
    "documentable": { "uuid": "...", "name": "Riverside Apartments" },
    "file_url": "https://.../storage/.../deed.pdf",
    "file_name": "deed.pdf",
    "uploaded_by": "Jane Admin",
    "created_at": "2026-08-04T00:00:00+00:00",
    "updated_at": "2026-08-04T00:00:00+00:00"
  }
}
```

## Visitors — `/api/v1/visitors`

Authorization mirrors `VisitorPolicy` (ARCHITECTURE.md §18) — its own `visitors.*` permission group. SaaS-owner staff have no access.

| Method | URI | Action | Notes |
|---|---|---|---|
| GET | `/api/v1/visitors` | List (paginated, `?search=&property_id=&status=&purpose=`) | `status` is `checked_in` or `checked_out` |
| POST | `/api/v1/visitors` | Check in a visitor | `property_id` required; `check_in_at` optional, defaults to now |
| GET | `/api/v1/visitors/{visitor:uuid}` | Show | |
| PUT/PATCH | `/api/v1/visitors/{visitor:uuid}` | Update | `check_out_at` is not accepted here — only `check-out` sets it |
| DELETE | `/api/v1/visitors/{visitor:uuid}` | Soft-delete | `204 No Content` |
| POST | `/api/v1/visitors/{visitor:uuid}/check-out` | Check out | Body: `{check_out_at?}`, defaults to now. Rejected with a 422 if already checked out |

```json
{
  "data": {
    "uuid": "5d8da759-fc62-4f26-a4dc-59746255e42c",
    "visitor_name": "Jane Visitor",
    "visitor_phone": "555-0100",
    "host_display_name": "John Tenant",
    "purpose": "meeting",
    "vehicle_plate": "SMK-001",
    "badge_number": null,
    "check_in_at": "2026-08-04T12:20:00+00:00",
    "check_out_at": null,
    "is_checked_in": true,
    "notes": null,
    "property": { "uuid": "...", "name": "Riverside Apartments" },
    "unit": null,
    "tenant": { "uuid": "...", "name": "John Tenant" },
    "vendor": null,
    "checked_in_by": "Jane Admin",
    "checked_out_by": null,
    "created_at": "2026-08-04T12:20:00+00:00",
    "updated_at": "2026-08-04T12:20:00+00:00"
  }
}
```

## Subscriptions & Billing — web only, no API

`subscription-plans.*`, `companies.subscription.*`, `subscriptions.*`, `subscription-invoices.*`, and `billing.index` (the SaaS-owner's plan/subscription/invoice management wired into Company's own show page, plus the company-facing read-only Billing page) exist only as Bootstrap 5 web routes - the same scope cut as Modules 3, 26, 27, and 28. This is entirely internal SaaS-operator tooling and a company's own read-only billing summary; there is no headless-client use case for it in the current phase (see ARCHITECTURE.md §22).
