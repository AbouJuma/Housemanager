# User Manual

Grows per module as user-facing screens land — see [ARCHITECTURE.md §9](ARCHITECTURE.md#9-module-build-order-roadmap).

## Dashboard

**Home** (`/dashboard`) — What you see right after signing in, and different depending on who you are. SaaS-owner staff get platform-wide numbers (total/active/trial companies, total users), a chart of new company signups over the last 14 days, and a breakdown of companies by status — plus an activity feed spanning every company. A company user gets their own company's user count, property and unit counts, an occupancy rate (shown once you have at least one unit), and days left on trial if applicable — plus a breakdown of their team by role and an activity feed scoped to just their company.

**Search everywhere** (Ctrl+K, or the search button next to the theme toggle) — Opens a quick-search box from any page. Start typing a company or user name and matching results appear as you type, grouped by type; click one to jump straight there. You only ever see what you'd otherwise be allowed to see on the Companies/Users pages — the search box doesn't grant any extra access.

**Notifications** (the bell icon next to the theme toggle) — Shows a red badge when you have unread notifications. Click it for your most recent ones, or "View all" for the full list (`/notifications`) with mark-read/mark-all-read. Right now the one thing that lands here is a trial-ending reminder, sent to company admins 3 days before their company's trial expires (in-app and by email) — more notification types arrive as the modules that trigger them do.

## Properties, Buildings & Units (company users with the Properties permission)

**Properties list** (`/properties`) — Search by name/city, filter by type (Residential, Commercial, Mixed Use, Warehouse, Retail, Hotel, Hostel, Industrial, Land, Other) and status, with a count of buildings and units per property. SaaS-owner staff don't see this — like Roles, Settings, and Integrations, property management only makes sense within one specific company.

**A property's page** (`/properties/{property}`) — Details (address, assigned landlord if any, total area, coordinates if set), an uploaded photo, and its buildings listed inline with their own unit counts and status. Add a building right from here.

**A building's page** — Floor count, status, and its units listed inline (type, status, rent). Add a unit right from here.

**A unit's page** — Type, status (Vacant, Occupied, Under Maintenance, Reserved — set manually for now), bedrooms/bathrooms, area, and rent amount. Unit numbers must be unique within their building, but the same number can be reused in a different building.

**Who can do what**: governed by one permission group (`properties.*`) covering properties, buildings, and units together — there's no separate permission for "buildings only" or "units only," since they're always managed as part of managing a property. A company admin's role gets this automatically; other roles need it granted explicitly (see Roles, below).

## Landlords (company users with the Landlords permission)

**Landlords list** (`/landlords`) — Search by name/email/phone, filter by type (Individual or Company) and status, with a count of properties per landlord.

**Adding/editing a landlord** — Name, type, contact details, address, and Tax ID. A **Bank Details** section (bank name, account name, account number, branch, SWIFT/branch code) is entirely optional, for when you're ready to pay rent out to them — filled in now or added later, whichever you prefer. Unlike a payment integration's API keys, these details show their real values when you come back to edit them, since they're your own reference information, not a third-party secret.

**A landlord's page** (`/landlords/{landlord}`) — Contact info, bank details (if set), and every property currently assigned to them.

**Assigning a landlord to a property** — On a property's create/edit form, pick from a dropdown of your company's landlords (or leave it as "None" — a property doesn't need a landlord assigned right away). This uses the `properties.*` permission, not `landlords.*` — you don't need landlord-management access just to assign one that already exists.

**Who can do what**: `landlords.*` is a separate permission group from `properties.*`, since landlord records hold sensitive financial information (bank details, Tax ID) that not everyone who manages properties necessarily needs access to. SaaS-owner staff don't see this screen — same reasoning as Properties.

## Tenants (company users with the Tenants permission)

**Tenants list** (`/tenants`) — Search by name/email/phone, filter by type (Individual or Company) and status (Active, Inactive, Blacklisted).

**Adding/editing a tenant** — Name, type, contact details, date of birth, an ID document (type + number), address, and an emergency contact. The identification number shows its real value when you come back to edit it, since it's your own screening record, not a third-party secret — the same treatment as a landlord's bank details.

**A tenant's page** (`/tenants/{tenant}`) — Contact, Identification, and Emergency Contact details, plus a photo if one was uploaded. If the tenant is marked **Blacklisted**, a red warning banner appears at the top of the page so front-desk staff can't miss it.

**Blacklisting a tenant** — Set their status to Blacklisted from the edit form. This keeps the full record (so you'll recognize the same person if they apply again) rather than deleting it — it's a screening flag, not a way to remove someone from the system.

**No unit assignment yet** — Tenants aren't linked to a specific property or unit at this stage; that connection is made when you create a lease (coming in Lease Management).

**Who can do what**: `tenants.*` is its own permission group, separate from `properties.*` and `landlords.*`, since tenant records hold sensitive personal data (identification, blacklist status) that not everyone who manages properties necessarily needs access to. SaaS-owner staff don't see this screen — same reasoning as Properties and Landlords.

## Leases (company users with the Leases permission)

**Leases list** (`/leases`) — Search by tenant name or unit number, filter by status (Active, Expired, Terminated).

**Starting a lease** — Two ways in: the "New Lease" button on the Leases list (pick any vacant unit from the dropdown), or the "Start Lease" button on a vacant unit's own page (the unit is pre-selected for you). Pick a tenant, a vacant unit, the start/end dates, billing cycle, and rent amount — the unit automatically becomes Occupied the moment the lease is created. Only vacant units and non-blacklisted tenants show up as options.

**A lease's page** (`/leases/{lease}`) — Lease terms, a link to the tenant, and a link to the unit. A red banner appears if the lease was terminated (with the reason, if one was given); a grey banner appears if it simply expired.

**Ending a lease** — On the edit form, change Status to Terminated and optionally give a reason. The unit automatically becomes Vacant again. A lease also expires on its own the day after its end date passes (checked once daily) — you don't have to do anything for a lease that simply runs its course.

**Renewing a lease** — Extend the End Date on the edit form. The tenant and unit on a lease can't be changed once it's created — to move a tenant to a different unit, terminate the current lease and start a new one on the new unit.

**A unit's page** now shows its current lease (tenant, dates) if occupied, or a "Start Lease" button if vacant. **A tenant's page** now shows their full lease history.

**Who can do what**: `leases.*` is its own permission group, separate from `properties.*` and `tenants.*`, since lease terms (rent, deposit amounts) are sensitive financial data. SaaS-owner staff don't see this screen — same reasoning as Properties, Landlords, and Tenants.

## Invoices (company users with the Invoices permission)

**Invoices list** (`/invoices`) — Search by invoice number, tenant name, or unit number, filter by status (Draft, Sent, Partially Paid, Paid, Overdue, Cancelled).

**Creating an invoice** — Two ways in: the "New Invoice" button on the Invoices list, or the "New Invoice" button on a lease's own page (the lease is pre-selected for you). Pick a lease, an issue date, a due date, and add one or more line items (description, quantity, unit price) — a running total updates automatically as you type. Every new invoice starts as a **Draft**.

**An invoice's page** (`/invoices/{invoice}`) — Details, line items with totals, and payment history. While a lease is a Draft, you can still Edit it (add/remove line items, change dates) or delete it entirely.

**Sending an invoice** — Click "Send Invoice" on a draft invoice's page. Once sent, the invoice can no longer be edited or deleted — to fix a mistake, cancel it and create a new one instead.

**Recording a payment** — On a sent (or overdue) invoice's page, use the "Record a Payment" form: amount, date paid, method (Cash, Bank Transfer, Mobile Money, Cheque, Other), and an optional reference/notes. The invoice automatically becomes **Partially Paid** or **Paid** depending on how much has been paid so far — you never set that status yourself.

**Cancelling an invoice** — Available on any invoice that isn't already paid or cancelled. A cancelled invoice is kept for the record, not deleted.

**Overdue invoices** — Once a day, any sent (or partially paid) invoice whose due date has passed is automatically marked **Overdue**. You can still record a payment against it at any time, which moves it to Partially Paid or Paid as usual.

**A lease's page** now shows every invoice billed against it, with a "New Invoice" shortcut.

**Who can do what**: `invoices.*` is its own permission group, separate from `leases.*`, since invoices are financial documents. Sending, cancelling, and recording payments all use the same update permission as editing — there's no separate permission per action. SaaS-owner staff don't see this screen — same reasoning as Properties, Landlords, Tenants, and Leases.

## Rent Collection

There's no separate "Rent Collection" screen — this adds an **Online Payment** section directly to an invoice's page, once your company has an active payment integration configured (Integrations, Module 7).

**Charging a tenant online** — On a sent (or overdue) invoice, enter an amount (defaults to the full balance due) and click "Charge Online." What happens next depends on the gateway: some show the charge as pending immediately (the tenant needs to approve a phone prompt or complete a checkout page); a status check a little later will confirm whether it went through.

**Checking on a pending charge** — Every payment attempt (successful, pending, or failed) is listed on the invoice's page. For anything still pending, click "Check Status" to ask the gateway directly whether it's gone through yet. This also happens automatically every 15 minutes in the background, and via an instant notification from the gateway itself when one arrives — you don't have to keep clicking it.

**When a charge succeeds**, it's recorded exactly like a manual payment (the invoice's balance and status update the same way), just tagged "Gateway" instead of "Cash" or "Bank Transfer" so you can tell how it came in.

**Payment attempts that go nowhere** — If a pending charge is never confirmed within 24 hours, it's automatically marked failed. Nothing is charged in that case; you (or the tenant) can simply try again.

**Automatic rent reminders** — Tenants now receive an automatic email and SMS reminder 3 days before an invoice is due, and another if it becomes overdue (repeating every 7 days while it stays unpaid). These require no setup beyond the tenant having a valid email/phone on file and your company having active email/SMS integrations configured.

**Who can do what**: charging an invoice and checking payment status use the same `invoices.update` permission as everything else on the invoice — there's no separate permission for this.

## Companies (SaaS-owner staff and company admins)

**Companies list** (`/companies`) — SaaS-owner staff see every company with a search box, status badge, and quick actions (view/edit/delete). Company users don't see this list; they're taken straight to their own company's page.

**Creating a company** (`/companies/create`, SaaS-owner only) — Fill in the name and optional details (contact info, address, timezone, locale, currency). The URL slug is generated automatically from the name if left blank, and de-duplicated automatically if it collides with an existing one. New companies start on a trial (length and default currency configurable in System Settings, below — 14 days / USD out of the box).

**Viewing/editing a company** (`/companies/{company}`) — SaaS-owner staff can view and edit any company; a company's own users can view and edit only their own company's details (name, contact info, address, timezone, locale, currency, status). Only SaaS-owner staff can delete a company.

**Branding** — From a company's page, upload a logo (shown at up to 2MB, common image formats) and set primary/secondary brand colors (hex color pickers). Changes save immediately and don't require the main "Edit" form. The logo is stored on whichever storage provider is currently Enabled under Integrations (below) — Local Storage by default.

**Who can do what**: SaaS-owner staff (accounts not tied to any company) manage every company. A company's own users can only view and update that one company — they can't see other companies, create new ones, or delete their own.

## Signing in

Visit `/login` and sign in with your email and password. There's no public sign-up page — accounts are created for you by a SaaS-owner or your company's admin (see Users below), not self-registered. Forgot your password? Use the "Forgot your password?" link on the sign-in page. Your profile page (top-right menu → Profile) lets you update your name/email/phone, change your password, or delete your own account. Adding a phone number is optional — it's used for SMS notifications if your company has an SMS provider configured (see Integrations, below).

## Users (company admins and SaaS-owner staff)

**Users list** (`/users`) — SaaS-owner staff see every user across every company, with which company each belongs to; a company admin sees only their own company's users. Each row shows the user's assigned role(s).

**Creating a user** (`/users/create`) — Name, email, and a starting password. A SaaS-owner creating a user picks which company it belongs to (or leaves it blank to create another SaaS-owner-tier account); a company admin's new users are always created in their own company. Roles aren't set here — assign them afterward from the user's edit page, once the user (and therefore their company) definitely exists.

**Editing a user** (`/users/{user}/edit`) — Update name/email, optionally set a new password, and check/uncheck the roles to assign. You can't delete your own account through this screen, even if you otherwise have permission to delete users.

**Who can do what**: SaaS-owner staff manage every user in every company. A company admin needs the relevant `users.*` permission (via their role) **and** the target user must be in their own company — having the permission never grants access across companies.

## Roles (company admins only)

**Roles list / editor** (`/roles`) — Every new company automatically gets two roles: **Admin** (every permission) and **Staff** (view-only access everywhere). Create additional roles with a custom name and pick exactly which permissions they grant, grouped by area (Users, Roles, and more as future modules add their own). SaaS-owner staff don't see this screen — role management only makes sense within one specific company, and the SaaS owner isn't tied to one.

## Settings

**Company settings** (`/settings`, company admins with the Settings permission) — Currently covers the date format used throughout your company's screens. SaaS-owner staff don't see this screen, same reasoning as Roles.

**System settings** (`/settings/system`, SaaS-owner only) — Platform-wide defaults applied to every *new* company at the moment it's created: the trial length (in days) and the default currency used when none is specified. Changing these doesn't affect existing companies, only ones created afterward.

## Audit Logs

**Activity log** (`/audit-logs`) — Every change to a company's profile or a user's account shows when it happened, who did it, what kind of record it was, and which fields changed. SaaS-owner staff see activity across every company (with a Company column to tell them apart); a company user with the Audit Logs permission sees only their own company's history. Filter by area (Company or User) using the dropdown at the top.

## Integrations (company admins only)

**Integrations hub** (`/integrations`) — Every payment gateway, SMS provider, email provider, storage backend, map provider, and AI provider the platform will eventually support, grouped into six sections, each showing whether it's Not Configured, Disabled, or Enabled for your company. SaaS-owner staff don't see this screen — same reasoning as Roles and Settings, an integration only makes sense within one specific company.

**Configuring a provider** — Click any provider to open its settings: an Enabled/Disabled switch, Sandbox/Production environment, and (for most providers) credential fields — API Key, API Secret, Merchant ID, Username, Password, Webhook URL, Callback URL. Leave a credential field blank when saving to keep whatever was already stored — you're never shown a saved secret's real value, only a "•••• saved" placeholder, so there's nothing to accidentally overwrite by leaving a field empty. A few providers need no credentials at all (Local Storage) — their settings page skips straight to Save.

**Test Connection** — Available once a provider has been saved at least once. For providers whose integration hasn't been built yet, clicking it will honestly tell you so rather than faking a result — Test Connection lights up for each provider as its module lands. **Storage, Email, SMS, and Payments** are all fully wired — every provider except Google Cloud Storage and NextSMS (which don't have a confirmed connectivity check yet) does a real, safe, read-only credentials check: no file is kept, no email/SMS is charged for beyond what Email/SMS testing itself does, and **no Payments provider ever moves money to test the connection** — it only confirms your credentials are accepted.

**Five Payment providers need one more detail before Test Connection will work**: Tigo Pesa, HaloPesa, NMB, CRDB, and NBC each need a **Base URL** pasted into their Password field (see below) — these providers don't have a publicly confirmed API address built into the app, since they're typically issued directly under a merchant agreement rather than published openly. Everything else about them is ready; that one field is genuinely all that's missing.

**Storage credential fields, relabeled** — For the Storage type, the same five fields carry storage-specific labels instead of the generic payment-style ones: **Access Key ID**, **Secret Access Key**, **Bucket Name**, **Region**, and **Custom Endpoint** (only needed for Cloudflare R2 / DigitalOcean Spaces). Whichever storage provider is switched Enabled is where your company's uploaded files (starting with the logo on the Companies branding screen) actually get written.

**Email credential fields, relabeled** — For the Email type, the same five fields become **Host**, **Port**, **Encryption** (`tls`, `ssl`, or `none`), **Username**, and **Password**. For every provider except plain SMTP (Gmail, Microsoft 365, Mailgun, SendGrid, Amazon SES), Host/Port/Encryption already have sensible defaults built in — leave them blank and just fill in Username/Password, or override Host if your provider needs a non-default endpoint (e.g. Mailgun's EU region).

**SMS credential fields, relabeled** — For the SMS type, the same five fields become **API Key / Account SID / Username**, **API Secret / Auth Token / Password**, **Sender ID (From)**, **Base URL** (Infobip only — your account's own subdomain, e.g. `xxxxx.api.infobip.com`), and **Password** (unused by most providers). Exactly which field means what depends on the provider you pick — see the field labels shown on each provider's own settings page.

**Payment credential fields, relabeled** — For the Payment type, the same five fields become **API Key / Client ID / Public Key**, **API Secret / Client Secret**, **Merchant ID / Vendor ID / Shortcode**, **Username / Passkey / Country Code**, and **Password / Base URL Override**. Exactly which of these a given provider needs varies — see each provider's own field hints. For Stripe you only need the Secret Key; for PayPal you need both the Client ID and Client Secret; for Tigo Pesa, HaloPesa, NMB, CRDB, and NBC, the Password field is where you paste that provider's Base URL once your integration agreement supplies one (Sandbox/Production for the others is chosen by the existing Environment switch, no separate URL to configure).

## Accounting (company users with the Accounts / Journal Entries permissions)

**Chart of Accounts** (`/accounts`) — Every company starts with a ready-made set of 12 accounts (Cash, Bank, Accounts Receivable, Accounts Payable, Security Deposits Held, Owner's Equity, Rental Income, Late Fees Income, and four expense categories) the moment it's created — nothing to set up before you can start using the module. Add your own accounts, edit names/types, or mark one Inactive from here. Each account's page shows its current running balance and recent activity.

**Journal Entries** (`/journal-entries`) — A full log of every transaction posted to your books, both the ones you post yourself and the ones the system posts automatically (see below). Each entry shows its date, description, total, and status (Posted or Voided).

**Posting a manual entry** — Click "New Journal Entry," fill in the date and description, then add at least two lines: pick an account for each, and enter either a debit or a credit amount (never both, and never neither). A running total shows whether your entry balances as you type — you can't submit until debits equal credits.

**Correcting a mistake** — A posted entry can never be edited or deleted. Instead, open it and click "Void" (with an optional reason) — this posts a real reversing entry alongside the original, so your books always show a full, honest history rather than silently changed numbers.

**Automatic postings — the real payoff of this module** — You don't need to do any bookkeeping for rent you invoice and collect through this system: the moment you **Send** an invoice, it's automatically recorded as revenue (debiting Accounts Receivable, crediting Rental Income); the moment a payment is **recorded** against it (manually or via an online gateway, Rent Collection), it's automatically recorded as cash received (debiting Cash or Bank depending on how it was paid, crediting Accounts Receivable back down). These show up in Journal Entries exactly like a manual entry, just with a link back to the invoice that generated them. If this ever doesn't seem to be happening for a company, check that its Cash/Bank/Accounts Receivable/Rental Income accounts (codes 1000/1100/1200/4000) haven't been renamed, deleted, or marked Inactive — the automatic posting quietly skips itself (without blocking the invoice action) if it can't find the account it needs.

**Who can do what**: `accounts.*` and `journal-entries.*` are two separate permission groups — a bookkeeper who posts day-to-day entries doesn't necessarily need access to restructure the chart of accounts itself, and vice versa. SaaS-owner staff don't see this module — a company's books are its own.

## Expenses (company users with the Expenses permission)

**Expenses list** (`/expenses`) — Search by description, vendor, or reference, filter by property or payment status (Unpaid, Paid). A voided expense shows with a struck-through row so it's clearly not part of your active spending.

**Recording an expense** — Click "New Expense," pick a category from your Chart of Accounts (only expense-type accounts are offered), optionally tie it to a specific property, and fill in the amount, date, and vendor. Choose whether it's **Unpaid** (a bill you owe, recorded as Accounts Payable) or **Paid Now** (choose how — Cash, Bank Transfer, Cheque, Mobile Money, Other — and it's recorded as money already spent). Either way, the correct bookkeeping entry is posted automatically the moment you save — no manual journal entry needed.

**Marking a bill as paid** — On an unpaid expense's page, use "Mark as Paid" once you've actually settled it. This posts a second entry clearing it off Accounts Payable and records it as cash/bank spent, and updates the expense's status.

**Editing an expense** — Only the description, vendor, property, reference, and notes can be changed after the fact — the amount, category, date, and paid/unpaid status are locked in once recorded, since they've already been posted to your books. To fix a genuine mistake in the amount or category, void the expense and record a corrected one.

**Voiding an expense** — Available any time an expense isn't already voided. This reverses every ledger entry the expense ever posted (including a later "marked paid" settlement, if there was one) with real offsetting journal entries — never silently deleting the record, so your books always show a full history. There's no way to delete an expense outright; voiding is the only way to retract one.

**Who can do what**: Recording, editing, marking paid, and voiding all use the same `expenses.update`/`expenses.create` permissions — there's no separate permission per action. SaaS-owner staff don't see this screen — a company's spending is its own.

## Vendors (company users with the Vendors permission)

**Vendors list** (`/vendors`) — Search by name, email, or phone, filter by category (Plumbing, Electrical, HVAC, Cleaning, Landscaping, Pest Control, General Contractor, Utility Provider, Security, Other) or status.

**Adding/editing a vendor** — Name, type, category, contact details, address, and Tax ID, plus an optional **Bank Details** section for when you're ready to pay them electronically. Like a landlord's bank details, these show their real values when you come back to edit them — they're your own reference information, not a third-party secret.

**A vendor's page** (`/vendors/{vendor}`) — Contact info, bank details (if set), and every expense recorded against this vendor.

**Using a vendor on an expense** — When recording an expense, pick from a dropdown of your company's vendors, or just type a name in the "Vendor Name" field if this is a one-off vendor not worth adding as a full record — both are fine, and either way the expense shows who it was paid to. This doesn't require any special vendor permission, just the usual `expenses.create`/`expenses.update`.

**Who can do what**: `vendors.*` is a separate permission group from `expenses.*`, the same reasoning as Landlords being separate from Properties — vendor records hold sensitive financial information (bank details, Tax ID) that not everyone who records expenses necessarily needs access to. SaaS-owner staff don't see this screen — a company's vendors are its own.

## Maintenance (company users with the Maintenance permission)

**Maintenance list** (`/maintenance-requests`) — Search by title or description, filter by property, status (Open, In Progress, Completed, Cancelled), or priority (Low, Medium, High, Urgent).

**Logging a request** — Click "New Request" (or "New Request" from a property's own page, which pre-fills that property), pick a property and, if relevant, a specific unit, tenant, category, and priority. You can log a request without knowing who'll handle it yet — vendor is optional.

**Updating a request** — Change status as work progresses (Open → In Progress → Completed, or Cancelled at any point). Marking a request **Completed** automatically fills in today's date as the Completed Date — you can still adjust that date afterward if the work actually finished on a different day than you got around to updating it.

**Recording the cost** — Once a request has an assigned vendor, its page offers a "Record Expense" form: pick an expense account, enter the amount, date, and payment status, and it creates a real expense entry — posted to your books exactly like any expense you'd record directly (see Expenses, above) — and links it back to the request so its actual cost is visible right there. This is a separate, deliberate step from marking a request Completed — plenty of completed requests cost nothing (a warranty repair, a tenant's own fix), so nothing is charged unless you explicitly record it. A request can only have one recorded expense.

**A property's or vendor's page** — Both show their ten most recent maintenance requests inline, so you can see what's been reported on a property, or what a vendor has been assigned, without leaving that page.

**Deleting a request** — Ordinary delete, available any time — a maintenance request is a work-tracking record, not a financial one, so (unlike an Expense) there's no need to void it; deleting it doesn't touch any expense already linked to it.

**Who can do what**: `maintenance.*` is its own permission group. SaaS-owner staff don't see this screen — a company's maintenance activity is its own.

## Inventory (company users with the Inventory permission)

**Inventory list** (`/inventory-items`) — Search by name or SKU, filter by category or status, or tick "Low stock only" to see just the items at or below their reorder level.

**Adding an item** — Click "New Item" (or "New Item" from a property's own page, which pre-fills that property), give it a name, category, and unit of measure (piece, box, liter, etc.), and optionally tie it to a property and/or a preferred vendor. New items always start at zero on hand — once saved, go to the item's own page and record a **Receipt** to bring real stock onto the books.

**Recording a transaction** — From an item's own page, use "Record Transaction": **Receipt** (stock coming in — a delivery), **Usage** (stock consumed — optionally tied to a specific maintenance request via the "Used On" dropdown, so you can see later what a repair actually used), or **Adjustment** (a correction — choose Increase or Decrease and give a reason, e.g. a stock count found extra units or something was damaged and written off). Every transaction is permanent — quantity on hand can never go below zero, and there's no way to edit or delete a past transaction. To fix a mistake, record a new adjustment instead.

**Transaction History** — Every item's page shows its full stock movement log: date, type, quantity, and the resulting balance, plus what it was linked to (a reason, or a maintenance request) and who recorded it.

**Low stock** — Set a Reorder Level when adding or editing an item, and it's flagged with a "Low Stock" badge on both the list and its own page the moment quantity on hand drops to that level or below.

**A property's or vendor's page** — Both show the items tied to them inline (a property's own stock, or everything a vendor supplies), and a maintenance request that consumed inventory shows a "Parts Used" section listing what was used and when.

**Deleting an item** — Ordinary delete, available any time — an item catalog entry is operational metadata, not a financial record, so (unlike an Expense) there's no need to void it. Its transaction history is untouched by deleting it.

**Who can do what**: `inventory.*` is its own permission group; recording a transaction uses the same `inventory.update` permission as editing an item. SaaS-owner staff don't see this screen — a company's stock is its own.

## Documents (company users with the Documents permission)

**Documents list** (`/documents`) — Search by title, filter by category, by what it's attached to (Properties, Landlords, Tenants, Leases, Vendors), or tick "Expiring within 30 days" to see what needs renewing soon.

**Uploading a standalone document** — Click "New Document" from the main Documents page for a company-wide document that isn't tied to any specific property, landlord, tenant, lease, or vendor — a business license or a master insurance policy, for example.

**Attaching a document to a record** — Use the "Add Document" button on a property's, landlord's, tenant's, lease's, or vendor's own page instead. The upload form automatically shows what you're attaching to and can't be changed to a different record afterward — if you attached something to the wrong place, delete it and upload it again correctly.

**Every upload needs a file** (PDF, Word, Excel, or an image — up to 10MB), a title, and a category (Contract, Lease Agreement, ID Document, Certificate, Insurance, Deed, Invoice, License, Other). An Expiry Date is optional but recommended for anything that needs renewing.

**Editing a document** — Title, category, description, and expiry date can all be changed. The file itself can be replaced by choosing a new one — leave the file field blank to keep the current one. What the document is attached to cannot be changed here.

**Expiry tracking** — Once a document has an Expiry Date, it's flagged "Expiring Soon" starting 30 days out, and "Expired" once that date has passed — visible on the document list and its own page.

**A property's, landlord's, tenant's, lease's, or vendor's page** — All five show their own attached documents inline, with a direct "Add Document" button.

**Deleting a document** — Ordinary delete, available any time.

**Who can do what**: `documents.*` is its own permission group. SaaS-owner staff don't see this screen — a company's documents are its own.

## Visitors (company users with the Visitors permission)

**Visitors list** (`/visitors`) — Search by name or vehicle plate, filter by property, by whether they're Checked In or Checked Out, or by purpose (Delivery, Meeting, Maintenance, Interview, Personal, Other).

**Checking in a visitor** — Click "Check In Visitor" (or "Check In Visitor" from a property's own page, which pre-fills that property). Give a name, purpose, and property; everything else — phone, vehicle plate, badge number, who they're visiting — is optional. If they're visiting a tenant, pick them from the dropdown; otherwise type who they're seeing (e.g. "Building Management") in the Host field. If they represent a vendor doing work, select that vendor too. Check-in time defaults to right now unless you set it manually.

**Checking a visitor out** — On a still-checked-in visitor's page, click "Check Out." This records the exact time and who checked them out. A visitor can only be checked out once — the button disappears afterward.

**A property's or tenant's page** — Both show their own recent visitors inline: a property shows everyone who's come through it, a tenant's page shows everyone who's come to see them specifically.

**Editing a visitor record** — Everything except the check-out itself can be corrected after the fact — useful for fixing a typo in a name or plate number.

**Deleting a visitor record** — Ordinary delete, available any time.

**Who can do what**: `visitors.*` is its own permission group; checking a visitor out uses the same `visitors.update` permission as editing. SaaS-owner staff don't see this screen — a company's visitor log is its own.

## Tenant Portal

This is a separate, self-service login area for your tenants — completely apart from the staff-facing screens documented above. Tenants sign in at `/portal/login` with their own email and password, not through the main `/login` page.

**Giving a tenant access** — On a tenant's own page (company users with the Tenants permission), find the "Portal Access" card and click "Enable Portal Access" (requires the tenant to have an email address on file). A random password is generated and shown to you exactly once, in a yellow banner — copy it and share it with the tenant right away, since it can't be retrieved again afterward. If they forget it, come back to this same card and click "Reset Password" for a new one. "Disable Access" locks them out entirely (their record and history are untouched, only the login itself is removed).

**What a tenant sees once logged in**:
- **Dashboard** — their outstanding balance, their active lease(s), and their most recent maintenance requests.
- **My Leases** — every lease they've ever had with you, with full terms.
- **My Invoices** — every invoice billed against any of their leases, with a **Pay Online** button on anything still owed (uses whichever payment gateway you've activated for your company, same as the staff-side "Charge" flow — see the Rent Collection section above).
- **Maintenance** — every request they've ever reported, plus a "Report an Issue" button to submit a new one against one of their own active leases (they can't report an issue against a unit or property they have no lease on).
- **Documents** — anything attached directly to them, plus anything attached to any of their own leases (their signed lease agreement, most commonly) — merged into one list.

**A tenant can only ever see their own data** — leases, invoices, maintenance requests, and documents belonging to any other tenant (even one at the same company) are not visible, and direct-URL attempts to view them are rejected.

**Who can do what**: enabling/resetting/disabling portal access uses the same `tenants.update` permission as editing a tenant — there's no separate permission for it. The portal itself has no staff-facing permission at all, since tenants aren't part of the staff Users/Roles/Permissions system.

## Landlord Portal

A second, separate self-service login area — this one for your landlords, at `/landlord-portal/login`. Unlike the Tenant Portal, this one is read-only: a landlord can see everything relevant to them, but can't submit or pay anything from here.

**Giving a landlord access** — On a landlord's own page (company users with the Landlords permission), find the "Portal Access" card and click "Enable Portal Access" (requires an email address on file). Exactly like the Tenant Portal, a random password is shown once — copy it and share it immediately, since it's never shown again. "Reset Password" generates a new one; "Disable Access" locks them out without touching their record.

**What a landlord sees once logged in**:
- **Dashboard** — how many properties and units they have (occupied vs. vacant), total rent collected across all of them, total expenses recorded against them, and the resulting net figure, plus their most recent maintenance activity.
- **My Properties** — every property they own, with a detail page showing every building and unit.
- **Expenses** — every expense ever recorded against any of their properties (voided ones shown struck through), so they can see exactly what's been spent on their behalf.
- **Maintenance** — every maintenance request logged against any of their properties, with its current status.
- **Documents** — anything attached directly to them, plus anything attached to any of their own properties (a title deed, most commonly).

**A landlord can only ever see their own properties and everything tied to them** — another landlord's properties, expenses, or maintenance history (even at the same company) are never visible.

**Who can do what**: enabling/resetting/disabling portal access uses the same `landlords.update` permission as editing a landlord. The portal itself has no staff-facing permission, since landlords aren't part of the staff Users/Roles/Permissions system.

## Reports (company users with the Reports permission)

**Reports hub** (`/reports`) — Four reports, each with an on-screen view and PDF/Excel/CSV download buttons at the top of the page:

- **Rent Roll** — every unit on file, its status, current tenant (if any), lease dates, and rent amount.
- **Occupancy** — one row per property: total units, occupied, vacant, and occupancy rate.
- **Income Statement** — income and expenses for a date range (defaults to the current month), pulled straight from your Chart of Accounts and journal entries, with a Net Income figure at the bottom. Change the date range at the top of the page and click Apply — the export buttons use whatever range is currently showing.
- **AR Aging** — every invoice still owed, grouped into Current, 1-30, 31-60, 61-90, and Over 90 days past due, with a running total for each bucket and a grand total.

**Downloading a report** — Click PDF, Excel, or CSV at the top of any report page. The file downloads immediately with the exact data currently on screen.

**Who can do what**: `reports.view` is the only permission this module has — there's nothing to create, edit, or delete, just view and export. SaaS-owner staff don't see this screen — a company's reports are its own.

## Subscriptions & Billing

**Subscription Plans (SaaS-owner staff only)** (`/subscription-plans`) — The catalog of plans companies can subscribe to: name, price, billing cycle (monthly/yearly), and optional limits on properties/units/users (leave blank for unlimited — these limits are informational only right now, not yet enforced). Create, edit, deactivate, or delete plans from here. Deactivating a plan hides it from the "Change Plan" dropdown on a company's page without affecting companies already on it.

**Managing a company's subscription (SaaS-owner staff only)** — Open any company's page (`/companies/{company}`) to find its **Subscription** card:

- **Change Plan** — pick a plan and, if the company has no active subscription yet, a starting status (Trialing or Active). Switching plans takes effect immediately and starts a new billing period.
- **Extend Trial** — add extra days to a trialing subscription's period.
- **Renew** — start a new period on the same plan, picking up from wherever the current period ends.
- **Cancel Subscription** — marks the subscription canceled and the company `cancelled`.
- **Generate Invoice** — bills the subscription's current plan price as a new, unpaid invoice. This is a manual action, not automatic — bill on whatever schedule makes sense.
- **Reactivate Company** — appears only when a company is suspended or cancelled; restores its access. This button is deliberately SaaS-owner-only — a suspended company can never un-suspend itself.

**Billing History** — every invoice ever generated for the company, with **Mark Paid**/**Void** actions on each unpaid one. A paid invoice can't be voided — it's a settled record.

**Billing (company users)** (`/billing`) — Every company user (not SaaS-owner staff) can see this read-only page: their company's current plan, status, price, and current billing period, plus their own full invoice history. There's nothing to change here — plan and billing decisions are made by the SaaS owner.

**What happens if a company doesn't pay or its trial runs out**: a scheduled daily check suspends any company whose trial period has ended without converting to a paid plan, and flags any paid subscription whose period has lapsed without renewal as **Past Due** (this does not suspend the company automatically — that stays a manual decision). A suspended or cancelled company's users are blocked from the entire app with a message to contact support — the one exception is they can always still log out.
