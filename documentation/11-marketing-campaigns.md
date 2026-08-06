# Module 11 — Marketing & Campaigns

**Module:** Marketing & Campaigns (Marketing)
**Category:** Growth
**Purpose:** Plans and executes promotional campaigns across channels — broadcast emails, SMS, and in-app notifications — with lead capture and segmented audiences.

> Marketing converts the audience data already in the ERP into campaigns. Campaigns target customers, senders are configured once, and every broadcast is logged with delivery status for reporting.

## 1. Overview

The module covers:

- **Campaigns** — multi-channel broadcast (email, SMS, in-app).
- **Senders** — configured email/SMS sender identities.
- **Leads** — captured prospective customers.
- **Audience targeting** — select recipients by segment.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `campaigns` | Campaign header | name, subject, channel (email/sms/notification), sender_id, audience, status, scheduled_at, sent_at, fk_admin_id |
| `campaign_sms_senders` | SMS sender identities | name, sender_id, status |
| `campaign_email_senders` | Email sender identities | name, email, status |
| `campaign_recipients` | Targeted recipients | fk_campaign_id, fk_customer_id (nullable), email, phone, status (sent/failed/pending), sent_at |
| `leads` | Captured prospects | name, email, phone, company, source, status, note |
| `contacts` | Contact submissions | name, email, subject, message, status |

### 2.1 Campaign Status Lifecycle

`draft` → `scheduled` → `sending` → `sent` (with per-recipient `sent`/`failed`/`pending` statuses).

## 3. Key Models

- **Campaign** (`app/Models/Campaign.php`) — `recipients()`, `admin()`, sender polymorphic lookup (email vs SMS sender by channel).
- **CampaignRecipient** — delivery row per recipient with status + sent_at.
- **Lead** — captured via storefront forms / admin entry.
- **Contact** — public contact-form messages.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `campaigns` | CampaignController | campaigns.{view,save,edit,delete} |
| `campaign-email-senders` | CampaignEmailSenderController | campaigns.* |
| `campaign-sms-senders` | CampaignSmsSenderController | campaigns.* |
| `leads` | LeadController | leads.{view,save,edit,delete} |
| `contacts` | ContactController | contacts.{view,save,edit,delete} |

### 4.1 Campaign Creation

- Name, subject, channel (email/SMS/in-app notification), sender selection, audience scope.
- Audience: "All customers", a specific segment, or individually chosen recipients.
- Schedule (`scheduled_at`) or send immediately; status flows draft → sent.

### 4.2 Sender Management

- Email senders: name + email; SMS senders: name + sender id. Both status-toggled.

### 4.3 Leads & Contacts

- Lead CRUD with source tracking (form, admin entry, import).
- Contact messages surfaced for follow-up; status tracks handling.

## 5. Delivery & Reporting

- Each recipient row records delivery status (`sent`/`failed`/`pending`) and timestamp.
- Campaign report shows delivered vs failed counts for the channel.

## 6. AI Integration Points

- **AI campaign subject/targeting suggestions** — recommended segments and copy based on purchase history (see AI module doc).

## 7. Permissions

| Group | Permissions |
|---|---|
| Campaigns | `campaigns.view/save/edit/delete` |
| Leads | `leads.view/save/edit/delete` |
| Contacts | `contacts.view/save/edit/delete` |

## 8. Related Code Locations

- Models: `laravel/app/Models/{Campaign,CampaignRecipient,CampaignEmailSender,CampaignSmsSender,Lead,Contact}.php`
- Controllers: `laravel/app/Http/Controllers/{CampaignController,CampaignEmailSenderController,CampaignSmsSenderController,LeadController,ContactController}.php`
- Views: `laravel/resources/views/campaign/`
