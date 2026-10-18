# LandBridge — Transparent Land Ownership & Transfer Tracking for Bangladesh

> **LandTech 2026 · Track: Digital Land Records, Verification & Digitization**
> **Problem Statement: P4 — Smart e-Mutation & Registry Workflow**

LandBridge is a bilingual (বাংলা / English) land ownership and transfer-tracking prototype. It gives citizens, registration officers, and land office officers a single, transparent workflow for recording ownership shares, submitting transfer requests, and moving a case from **Submitted → Identity Verified → Registration Review → Land Office Review → Approved → Record Updated** — with a full audit trail at every step.

---

## ⚠️ Hackathon Prototype Notice

This is a **hackathon prototype**. All users, parcels, documents, and transfer cases in this application are **fictional, synthetic demo data** created by the team.

- No real NID, phone numbers, addresses, or government land records are used or displayed.
- No government portal is scraped, queried, or accessed.
- The application is not connected to any government system (Bhumi Ministry, AC Land, Sub-Registry, or otherwise).
- See [Data, Privacy & Security](#data-privacy--security) below.

---

## Table of Contents

1. [Problem Statement](#problem-statement)
2. [Solution Overview](#solution-overview)
3. [Key Features](#key-features)
4. [Demo Accounts](#demo-accounts)
5. [Tech Stack](#tech-stack)
6. [Setup Instructions](#setup-instructions)
7. [Database Schema](#database-schema)
8. [Workflow Walkthrough](#workflow-walkthrough)
9. [Data, Privacy & Security](#data-privacy--security)
10. [AI Tool Disclosure](#ai-tool-disclosure)
11. [Open-Source Libraries & Credits](#open-source-libraries--credits)
12. [Team](#team)
13. [Submission Assets](#submission-assets)
14. [License](#license)

---

## Problem Statement

**P4 — Smart e-Mutation & Registry Workflow**

> Automate and link the flow from deed registration to mutation, with automated checks, status tracking, and reduced manual intervention.

In Bangladesh, land disputes are among the most common civil cases. Many trace back to:

- Fragmented records split between the **Sub-Registry Office** (registration) and the **AC Land Office** (mutation);
- Conflicting khatians across survey generations (CS, SA, RS, BS);
- No citizen-facing way to follow a transfer file once it enters the pipeline;
- Co-ownership shares recorded in ways that are hard to verify.

LandBridge addresses this with a **single, transparent, bilingual workflow** that links the two offices behind one citizen-facing view.

---

## Solution Overview

LandBridge is a **web application** (no framework, plain PHP 8.2 + MySQL 8 + Bootstrap 5.3) that models the full land transfer lifecycle:

- **Citizens** see their own parcels, ownership shares, and transfer history; they can initiate a transfer by specifying a buyer and a **share fraction** (not a percentage — the exact `numerator/denominator` that appears on a deed).
- **Registration Officers** work a queue of cases awaiting identity verification and registration review.
- **Land Office Officers** work a queue of cases awaiting land-office review and final approval.
- **Administrators** can see and act on every queue, and perform the final **Record Update** — the atomic step that actually moves ownership shares from the seller to the buyer.

The **Record Update** step runs inside a `FOR UPDATE` database transaction: seller ownership is decremented (or deleted if it reaches 0), buyer ownership is incremented (or created), fractions are reduced with GCD, the case moves to `record_updated`, and an audit row is written — all or nothing.

---

## Key Features

- 🌐 **Fully bilingual (Bangla default + English)** — every label, button, status, error, and homepage copy string goes through a single `t()` translation function with per-page language switching. No hardcoded user-facing strings.
- 🧾 **Unified land profile** — every parcel, share, document, and correction request in one place.
- 🔁 **Transfer tracking** — a case-based workflow with a full event timeline visible to citizens and officers.
- 🧮 **Exact share fractions** — ownership shares stored and displayed as integer `numerator / denominator`; the transfer amount is always **a fraction of the seller's current share** (not the whole parcel), so co-ownership splits stay correct across many generations of transfers.
- 🛡️ **Full audit trail** — every officer action writes to `transfer_events` and `audit_logs`.
- 👥 **Four strictly isolated roles** — citizen, registration officer, land office officer, administrator — with a re-validated action matrix on the server (never trusts the browser).
- 🔐 **CSRF protection on every POST form**, PDO prepared statements throughout, session regeneration on login, local-only redirect validation, password hashing with `PASSWORD_DEFAULT`.
- 📱 **Responsive** — collapses cleanly on mobile; tables scroll horizontally.

---

## Demo Accounts

All seven accounts are seeded with demo data. **Password hashes use `PASSWORD_DEFAULT`.**

| Role | Email | Password | Land Person ID |
|---|---|---|---|
| Citizen | `rahim@example.com` | `citizen123` | LP-1001 |
| Citizen | `karim@example.com` | `citizen123` | LP-1002 |
| Citizen | `joya@example.com` | `citizen123` | LP-1003 |
| Citizen | `nasir@example.com` | `citizen123` | LP-1004 |
| Registration Officer | `reg@example.com` | `reg123` | LP-2001 |
| Land Office Officer | `land@example.com` | `land123` | LP-3001 |
| Administrator | `admin@example.com` | `admin123` | LP-9001 |

> To walk the **complete workflow** end-to-end in one sitting: log in as `rahim@example.com` → create a new transfer → log out → log in as `reg@example.com` → verify identity, then approve registration → log out → log in as `land@example.com` → approve land office review, then approve again → log out → log in as `admin@example.com` → **Update ownership record** → check the parcel's co-owners list to see the share split.

---

## Tech Stack

- **Server:** Apache (any modern LAMP stack)
- **Backend:** PHP **8.2+** (no framework, no Composer, no Node)
- **Database:** MySQL **8** (port 3309 in the reference environment)
- **Charset:** `utf8mb4` everywhere
- **Frontend:** HTML5, Bootstrap **5.3** (CDN), Bootstrap Icons, Google Fonts (Inter + Noto Sans Bengali), vanilla JS
- **Architecture:** Single-entry-point router (`index.php?page=…`), PDO prepared statements, server-rendered PHP views, session-based auth, cookie-based language preference.

No JavaScript build step. No `node_modules`. No external PHP packages.

---

## Setup Instructions

### Prerequisites

- Apache with `mod_php` (PHP 8.2 or higher, with `pdo_mysql`, `mbstring`, `json` extensions)
- MySQL 8 (this prototype was developed against MySQL 8 running on port `3309`)
- MySQL user `landbridge` with permissions on database `landbridge`

### 1. Clone the repository

```bash
git clone https://github.com/<your-org>/landbridge.git
cd landbridge
```

### 2. Place the app under the web root

The reference deployment uses:

```
/var/www/html/landbridge/
```

Copy the repository into that folder (or symlink it).

### 3. Create the database and seed data

> The application only **reads and writes** existing tables — it does not create or migrate the schema. Import the seed file (or ask the team for the schema dump) to create all eight tables and the seven demo accounts.

```bash
mysql -h 127.0.0.1 -P 3309 -u landbridge -p landbridge < schema+seed.sql
```

The eight tables are:

- `users`
- `land_parcels`
- `ownerships`
- `transfer_cases`
- `transfer_events`
- `audit_logs`
- `parcel_documents`
- `ownership_correction_requests`

### 4. Configure the database connection

Credentials are hardcoded in `config/database.php` for this hackathon build:

```php
$dsn = 'mysql:host=127.0.0.1;port=3309;dbname=landbridge;charset=utf8mb4';
$pdo = new PDO($dsn, 'landbridge', 'landbridge123', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

Update `config/database.php` if your MySQL host/port/credentials differ.

### 5. Serve the app

Point your browser to:

```
http://localhost/landbridge/index.php
```

The default language is **Bangla**; use the globe switcher in the top nav to switch to English.

### 6. Verify the install

- The homepage renders with all six sections (Hero, Problem, Features, Workflow, About, FAQ).
- All seven demo accounts log in successfully.
- Each role's dashboard shows the correct role-specific data.
- Creating a transfer and walking it through all six statuses with the officer/admin accounts produces the expected ownership update.

---

## Database Schema

Eight tables. Simplified:

```
users                           — accounts (citizen, reg officer, land officer, admin)
land_parcels                    — parcel records (mouza, dag, khatian, area)
ownerships                      — (parcel, user) → exact share fraction
transfer_cases                  — a transfer request from seller → buyer
transfer_events                 — every status change, with actor + notes
audit_logs                      — old/new JSON snapshots for every mutation
parcel_documents                — khatian, ROR, survey map, deed references
ownership_correction_requests   — citizen-submitted corrections, officer-resolved
```

**Share math** (critical): a transfer is always a fraction of the seller's **current** share, never of the whole parcel.

```
new_seller_num = s_num × t_den − t_num × s_den
new_seller_den = s_den × t_den             → reduce with GCD
new_buyer_num  = b_num × t_den + t_num × b_den   (if buyer already owns)
new_buyer_den  = b_den × t_den             → reduce with GCD
```

If `new_seller_num == 0`, the seller's ownership row is **deleted**. All five steps (seller decrement, buyer increment, case status, event insert, audit insert) run inside one transaction with `FOR UPDATE` locks on the seller and buyer ownership rows.

---

## Workflow Walkthrough

```
Citizen (New Transfer form)
      │
      ▼
  submitted ─────────────► identity_verified ─────────────► registration_review
      │                            │                                 │
      │ (request_correction)       │ (request_correction)            │ (request_correction)
      ▼                            ▼                                 ▼
 needs_correction ◄────────────────◄───────────────────────────────◄───
      │
      │ (approve_registration)     (approve_land_office)
      ▼                            ▼
 registration_review ───────────────► land_office_review ──────────► approved
                                                                        │
                                                                        │ (update_record)
                                                                        ▼
                                                                   record_updated
```

The **action matrix** in `includes/helpers.php` (`allowed_actions()`) is re-checked on the server for every POST, so manipulating the form in DevTools cannot bypass role/status restrictions.

---

## Data, Privacy & Security

In accordance with **LandTech 2026 Rule Book, Section 8**:

- ✅ **All data is synthetic.** Demo names (`Rahim`, `Karim`, `Joya`, `Nasir`), parcels (`PB-1002`, `Savar`, `Dag 451`), and case numbers (`TC-2025-0042`, etc.) are fictional and created by the team.
- ✅ **No real personal data.** No NID numbers, no real phone numbers, no real addresses, no real land records of real individuals.
- ✅ **No scraping, no government portal access.** The app only reads from a local MySQL database that the team controls.
- ✅ **No penetration testing against live systems.** All security checks and demos were performed only against this prototype.
- ✅ **No real documents.** All `parcel_documents` rows reference fictional document references.

Application-level security:

- All SQL uses **PDO prepared statements** with `ATTR_EMULATE_PREPARES = false`.
- Every POST form includes and validates a **CSRF token** using `hash_equals`.
- Sessions are **regenerated** on login.
- Return URLs are validated to be **local paths only** (`/...`), preventing open redirects.
- Passwords are hashed with **`password_hash(..., PASSWORD_DEFAULT)`** and verified with `password_verify`.

---

## AI Tool Disclosure

In accordance with **LandTech 2026 Rule Book, Section 9**, the following AI tools were used during development:

| Tool | How it was used |
|---|---|
| **Claude (Anthropic)** | Scaffolding the initial PHP file structure, generating the bilingual (Bangla + English) translation dictionary, reviewing share-fraction math, and drafting this README. |
| **[Any other tool — e.g. GitHub Copilot / ChatGPT]** | [e.g. "Assisting with individual SQL query drafts and Bootstrap component markup."] |

All AI-generated code was **reviewed, understood, and verified** by both student members. Both student members can explain any file in the repository on request, and the team is prepared to answer technical questions during the Final Round presentation.

---

## Open-Source Libraries & Credits

In accordance with **Section 7.2** of the Rule Book, all third-party resources are credited below:

| Resource | Version | License | Used for |
|---|---|---|---|
| **Bootstrap** | 5.3.2 (CDN) | MIT | Layout grid, navbar, cards, badges, accordion, forms |
| **Bootstrap Icons** | 1.11.1 (CDN) | MIT | Iconography throughout the UI |
| **Google Fonts — Inter** | via Google Fonts CDN | SIL Open Font License 1.1 | Latin UI typography |
| **Google Fonts — Noto Sans Bengali** | via Google Fonts CDN | SIL Open Font License 1.1 | Bangla typography |
| **MySQL 8** | 8.x | GPLv2 (server) | Database engine |
| **PHP** | 8.2+ | PHP License | Server-side runtime |

No other third-party PHP packages, JavaScript libraries, or CSS frameworks are used. No Composer dependencies, no `node_modules`.

If you add any library during Round 2, **add a row to this table** before final submission.

---

## Team

Per LandTech 2026 rulebook **Section 4**, each team has four members — two students and two mentors.

**Team name:** `[Your Team Name]`

| Role | Name | DIU ID / Alumni Proof | Contact |
|---|---|---|---|
| Student 1 (Team Leader) | `[Full Name]` | `[DIU Student ID / Alumni]` | `[email]` |
| Student 2 | `[Full Name]` | `[DIU Student ID / Alumni]` | `[email]` |
| Faculty Mentor | `[Full Name]` | `[Department]` | `[email]` |
| Industry Mentor | `[Full Name]` | `[Organization / Role]` | `[email]` |

---

## Submission Assets

For the **Round 1 (Online Preliminary)** submission on the LandTech Hackathon Platform by **October 16, 2026**, this repository is submitted alongside:

- 📄 **Concept note (PDF, ≤ 4 pages)** — problem statement, context, proposed solution, system architecture, tech stack, key features, countrywide implementation plan, team details.
- 🎬 **Demo video (≤ 3 minutes)** — walkthrough of the six-stage workflow with live ownership update.
- 🌐 **Live deployment link (optional but recommended)** — `[link]` *(to be filled in before submission)*.

For the **Final Round**, the README will be updated with a **"Changes since Round 1"** section in accordance with **Section 7.5**.

---

## License

`[Choose one — e.g. MIT]`

The team retains full ownership of the project (Rule Book, Section 12). An open-source license is encouraged; if you release the code under MIT, replace this section with the standard MIT text and add a `LICENSE` file at the repo root.

---

*Built for LandTech 2026 — LandTech Innovation Hackathon, Daffodil International University.*
