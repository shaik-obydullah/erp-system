# Module 11 — Marketing & Campaigns

**Module:** Marketing & Campaigns (Marketing)
**Category:** Growth
**Purpose:** Plans and tracks marketing campaigns with budgets and spend, collects newsletter subscribers from the storefront, and supports flash-sale discount records.

> Marketing turns planned promotions into tracked spend. Campaigns capture budget, actual spend, and status, and each campaign's spend posts to the finance ledger as a marketing expense. Newsletter subscriptions are captured directly from the storefront.

## 1. Overview

The module covers:

- **Campaigns** — planned/active/completed/canceled promotions with budget and spent amount.
- **Newsletter subscribers** — email capture from the storefront.
- **Flash sales** — stock-level discount records (table exists; no UI/routes yet).
- **Content blocks** — hero/slider/page/FAQ marketing content is managed by the CMS (see Administration & System).

There are **no** `campaign_sms_senders`, `campaign_email_senders`, `campaign_recipients`, `leads`, or `contacts` tables, and campaigns have no channel, sender, audience, scheduled-send, or recipient-delivery columns.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `campaigns` | Campaign record | name, description, start_date, end_date, status, budget, spent_amount, journal_entry_id, accounting_version |
| `newsletter_subscribers` | Storefront email capture | email, created_at, updated_at |
| `flash_sales` | Stock-level discounts | name, start_date, end_date, fk_stock_id, discount |
| `contents` | Marketing/CMS blocks | See Administration & System module |

### 2.1 Campaign Status

`planned` → `active` → `completed`, or `canceled`.

The status is stored as a string; there is no scheduler or automatic transition.

## 3. Key Models

- **Campaign** (`app/Models/Campaign.php`) — `journalEntry()`. Table `campaigns`.
- **NewsletterSubscriber** — simple email record; `firstOrCreate` on storefront submit.
- **FlashSale** — there is currently no `FlashSale` model, controller, or route; the table is present in the schema only.
- **Content** — CMS blocks (`hero`, `slider`, `page`, `faq`, `banner`, `settings`) consumed by the storefront.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `campaigns` | CampaignController | resource except `show`/`destroy`; `campaigns.*` permission group |

The campaign resource routes currently rely on the `auth:admin` + `active.admin` group and menu visibility; explicit per-route `permission:` middleware is not applied.

### 4.1 Campaign Management

- Create/edit a campaign with name, description, start/end dates, status, budget, and spent amount.
- Saving posts `AccountingPoster::campaignSpend()`:
  - a `campaignExpense` transaction,
  - a cashbook outflow,
  - a balanced journal entry (marketing expense debit, cash credit) for the current `spent_amount`.
- Campaign spend is posted only when `spent_amount > 0`; repeated posting is prevented by `source_key` / `accounting_version`.

### 4.2 Newsletter

- `POST /newsletter/subscribe` on the storefront validates an email (throttled 10/min) and stores it in `newsletter_subscribers` via `firstOrCreate`.
- There is no admin subscriber list, unsubscribe flow, or bulk-email sending in the current code.

## 5. Delivery & Reporting

- Campaign reporting is financial: budget vs. spent amount, dates, and status.
- There are no delivery, open-rate, click, sent/failed, or recipient-level metrics because no sending/recipient tables or mail/SMS integration exist.
- Campaign spend appears in finance through the `campaignExpense` transaction, cashbook, and marketing-expense ledger account.

## 6. AI Integration Points

There is no AI campaign copy/targeting integration in the current codebase.

## 7. Permissions

| Group | Permissions |
|---|---|
| Campaigns | `campaigns.view/save/edit/delete` |

There are no Leads or Contacts permission groups.

## 8. Related Code Locations

- Models: `laravel/app/Models/{Campaign,NewsletterSubscriber,Content}.php`
- Controllers: `laravel/app/Http/Controllers/CampaignController.php`; storefront newsletter in `StorefrontController::newsletterSubscribe`
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`campaignSpend`)
- Views: `laravel/resources/views/campaign/`
