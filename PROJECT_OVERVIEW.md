# MicroFinance Management System — PROJECT OVERVIEW

> A comprehensive, developer-facing overview of the **MicroFinance** web application.
> Audience: new developers, code reviewers, and AI coding assistants.

---

## Table of Contents

1. [Project Summary](#1-project-summary)
2. [Business Workflow](#2-business-workflow)
3. [User Roles](#3-user-roles)
4. [Core Modules](#4-core-modules)
5. [System Architecture](#5-system-architecture)
6. [Database Overview](#6-database-overview)
7. [Main Business Rules](#7-main-business-rules)
8. [Complete Feature List](#8-complete-feature-list)
9. [Folder Structure](#9-folder-structure)
10. [Technologies Used](#10-technologies-used)
11. [External Dependencies](#11-external-dependencies)
12. [Security Features](#12-security-features)
13. [Current Project Status](#13-current-project-status)
14. [Future Improvements](#14-future-improvements)
15. [Development Roadmap](#15-development-roadmap)
16. [Glossary](#16-glossary)

---

## 1. Project Summary

### What this project is
**MicroFinance** is a server-rendered PHP web application that manages the end-to-end operations of a microfinance institution. It is built on a classic LAMP-style stack (XAMPP / Apache / MySQL / PHP) with templated server pages, prepared SQL for safety, and light JavaScript for interactive widgets (search, notifications, tables).

It is designed to digitize the day-to-day workflow of small-loan cooperatives: members organized into committees, branches collecting savings, disbursing loans, and tracking periodic installments.

### The problem it solves
Microfinance institutions typically operate in low-connectivity, paper-heavy environments. Manual ledgers, hand-collected installments, and ad-hoc spreadsheets make it difficult to:

- Track who has paid what.
- Identify overdue loans quickly.
- Maintain an audit trail of all collections.
- Generate reports for branch managers and the central admin.
- Manage multi-branch operations with field officers.

This system provides a single source of truth for members, committees, loans, savings, and collections.

### Target users
- **Admin** — full system access, manages branches, users, and global reports.
- **Branch Manager** — manages all field officers and members within their branch.
- **Field Officer** — works with a fixed set of committees; collects daily installments; submits savings and loan data.
- **Member** (implied through the data model) — the borrower/saver whose records are stored in the system.

### Main objectives
1. Manage a complete member lifecycle: registration, savings, loan cycle, completion.
2. Centralize committee and branch information.
3. Provide real-time dashboards for staff.
4. Track every transaction with a clear audit trail.
5. Produce quick CSV reports and printable loan statements.
6. Provide a global search across members, committees, and officers.

---

## 2. Business Workflow

The intended end-to-end flow of a member inside the microfinance:

```
1. Branch Setup (Admin)
        ↓
2. Field Officer Assigned to Branch
        ↓
3. Committee Created (linked to a branch + field officer)
        ↓
4. Members Registered & Assigned to a Committee
        ↓
5. Members Deposit Savings (opening account)
        ↓
6. Loan Application (Member or Field Officer initiates)
        ↓
7. Loan Approval (Admin / Branch Manager)
        ↓
8. Loan Disbursement (creates loans row + installments)
        ↓
9. Daily / Weekly / Monthly Collection by Field Officer
        ↓
10. Installments Auto-Marked as Paid (per payment)
        ↓
11. Penalty / Fine (when overdue — tracked via due_system module)
        ↓
12. Loan Completion (total_paid >= total_payable)
        ↓
13. Reports & Dashboard Analytics
```

### Step-by-step detail

| # | Step | Module | Description |
|---|------|--------|-------------|
| 1 | Branch Setup | `users`, `branches` | Admin creates branch rows like "Dhaka Main", "Chittagong". |
| 2 | Officer Assignment | `users` | Users with `role = 'field_officer'` are tagged to a `branch_id`. |
| 3 | Committee Created | `Committees/add.php` | Each committee has a name, meeting day, meeting time, and a single field officer. |
| 4 | Member Registration | `members/add.php` | Captures personal info, guarantor, branch, and committee. |
| 5 | Savings Account | `savings/add.php`, `savings/deposit.php` | A savings row is created per member; deposits/withdrawals update `balance` and write to `savings_transactions`. |
| 6 | Loan Application | `loans/add.php` | Captures principal, interest rate, interest type (flat / reducing), term, installment type (monthly / weekly), disbursement date, and purpose. |
| 7 | Loan Approval | `loans/` (status field) | Status moves from `pending` → `active` (currently implicit; loan is inserted directly as `active`). |
| 8 | Disbursement | `loans/add.php` | Sets `disbursement_date`, `first_installment_date`, `maturity_date`, `total_payable`, `installment_amount`. |
| 9 | Daily Collection | `installments/payment.php` | Field officer selects a loan, enters amount, date, and note. |
| 10 | Installments Auto-Paid | `installments/payment.php` | The next pending installment in `loan_installments` is marked `paid` with the payment date. |
| 11 | Penalty / Fine | `due_system/`, `due_system/overdue.php` | When `principal_amount - total_paid > 0` past `maturity_date`, the loan is flagged. |
| 12 | Loan Completion | `loans/` | Implicit: when `total_paid >= total_payable`, status would be `closed`. |
| 13 | Reports | `dashboard.php`, `due_system/report.php`, `members/export_csv.php` | Dashboards, CSV exports, and printable loan statements. |

> **Note:** Some workflow steps (e.g., explicit approval screens, formal penalty calculation) are *partially* implemented. Where the code does not yet cover a step, this is marked in the **Current Project Status** section.

---

## 3. User Roles

The system distinguishes four roles stored in the `users.role` column:

| Role | Value Used in Code | Typical Permissions |
|------|--------------------|---------------------|
| Admin | `admin` | Full access — manage users, branches, all loans, reports. |
| Branch Manager | `branch_manager` | Oversees users & loans in their branch; can edit officer accounts. |
| Field Officer | `field_officer` | Manages assigned committees, collects installments, views members. |
| Member | `member` | Stored in the data model; no self-service portal yet. |

### Detailed responsibilities

#### `admin`
- **Responsibilities:** Global configuration, user CRUD, branch management, viewing all reports.
- **Permissions:** Can view/edit/delete any user's profile; can deactivate users; can change roles.
- **Limitations:** None beyond the application logic.
- **Modules accessible:** All modules including `profile.php?id=...` for any user.

#### `branch_manager`
- **Responsibilities:** Oversees field officers within their branch and approves/view loans.
- **Permissions:** Can view field officer profiles; can edit officer account; can view that officer's committees.
- **Limitations:** Cannot edit other branch managers or admins through the profile page (logic only checks `role == 'field_officer'`).
- **Modules accessible:** Dashboard, Members, Committees, Loans, Savings, Due System, Profile (own + officers).

#### `field_officer`
- **Responsibilities:** Collects daily installments; manages their assigned committees; submits savings deposits.
- **Permissions:** Reads only their own committees/members; can collect payments.
- **Limitations:** No user-management rights; cannot view other branches.
- **Modules accessible:** Dashboard, Members, Committees, Loans, Installments, Savings, Due System, Profile (own only).

#### `member`
- **Current state:** No frontend self-service portal. Members are first-class rows in the `members` table but do not log in.
- **Expected future permissions:** View own loans, savings, and payment history.

### Role-based enforcement points
- `profile.php` blocks viewing other users unless the viewer is `admin` or `branch_manager`.
- `topbar.php` shows different dropdown links for `field_officer` vs `admin` / `branch_manager`.
- `api/notifications.php` returns role-specific notifications (e.g., overdue count for admins, today's collection count for officers).

---

## 4. Core Modules

### 4.1 Dashboard
- **File:** `dashboard.php`
- **Purpose:** Central overview of total members, total loans, total paid, total due, total savings, overdue count, top borrower, and a 6-month loan/collection trend chart.
- **Main features:**
  - Stat cards (members, savings, loans, collection, due, overdue).
  - Loan health percentage (paid / total).
  - Top borrower card.
  - Line chart of last 6 months (loans vs collections).
  - Recent transactions list.
  - Recent members list.
- **User interactions:** Quick action buttons (Add Member, Add Loan, Add Savings) — visually present but currently link to `#`.
- **Expected outputs:** Visual KPI cards, chart, and lists.

### 4.2 Member Management
- **Folder:** `members/`
- **Files:** `index.php`, `add.php`, `edit.php`, `view.php`, `delete.php`, `export_csv.php`, `loan_chart.php`, `quick_view.php`, `components/*`.
- **Purpose:** CRUD for member records.
- **Main features:**
  - List + filter (search by name/code/phone/NID, branch, status).
  - Add member with personal info, guarantor info, branch, committee.
  - Edit, view, delete, and CSV export.
  - Per-member summary cards (total loans, paid, due, savings).
  - Loans table, savings table, payments table embedded in `view.php`.
- **Expected outputs:** Member records persisted in `members`; member appears in dashboard recent list.

### 4.3 Committee Management
- **Folder:** `Committees/`
- **Files:** `index.php`, `add.php`, `edit.php`, `view.php`, `assign-member.php`, `members.php`, `remove-member.php`, `toggle-status.php`, `delete.php`.
- **Purpose:** Group members into committees led by a field officer.
- **Main features:**
  - Add committee with branch, field officer, meeting day, meeting time, formation date.
  - Filter by branch, status, meeting day.
  - Card-grid view of committees with member count.
  - Activate/deactivate toggle.
  - Assign/unassign members (single or bulk).
- **Expected outputs:** Committee rows in `committees`; `members.committee_id` updated on assignment.

### 4.4 Loan Management
- **Folder:** `loans/`
- **Files:** `index.php`, `add.php`, `edit.php`, `view.php`, `delete.php`, `payment.php`.
- **Purpose:** Create loans, manage status, view printable loan details, and record payments.
- **Main features:**
  - Add loan with principal, interest rate, interest type (flat / reducing), term, installment type (monthly / weekly), disbursement date, first installment date, purpose.
  - Auto-calculate `total_payable`, `installment_amount`, `maturity_date`.
  - Stats: total portfolio, active loans, overdue, collection rate.
  - Print-friendly view (`view.php`).
- **Expected outputs:** Rows in `loans`; auto-computed fields populated.

### 4.5 Installments / Daily Collection
- **Folder:** `installments/`
- **Files:** `index.php`, `payment.php`, `payment_list.php`, `edit.php`, `delete.php`.
- **Purpose:** Record daily installment payments and track which installments are paid.
- **Main features:**
  - Quick recent-payments list.
  - Collect new payment: select loan, amount, date, note.
  - On submit: inserts into `loan_payments`, recomputes `loans.total_paid`, and marks the next pending installment as `paid`.
- **Expected outputs:** Payment log + updated loan balance.

### 4.6 Savings Management
- **Folder:** `savings/`
- **Files:** `index.php`, `add.php`, `deposit.php`, `withdraw.php`, `transactions.php`, `report.php`, `member.php`, `edit.php`, `delete.php`.
- **Purpose:** Manage per-member savings accounts and transaction history.
- **Main features:**
  - Add account, deposit, withdraw (with insufficient-balance check).
  - Transaction history with running balance.
  - Members link via `savings.member_id`.
- **Expected outputs:** Updated `savings.balance` and a `savings_transactions` audit row per move.

### 4.7 Due System
- **Folder:** `due_system/`
- **Files:** `index.php`, `overdue.php`, `report.php`.
- **Purpose:** Identify overdue loans and produce due reports.
- **Main features:**
  - Due list (remaining = principal - paid).
  - Overdue list (active loans with remaining > 0).
  - Report view.
- **Expected outputs:** Sorted tables of due/overdue loans.

### 4.8 User Profile
- **File:** `profile.php` + `profile/` folder (`edit.php`, `change-password.php`, `upload-avatar.php`).
- **Purpose:** Self-service profile management with role-aware side panels.
- **Main features:**
  - Avatar upload (with file storage to `uploads/avatars/`).
  - Edit name, phone, branch.
  - Change password (requires current password).
  - For admins: reset/force-logout/change-role/activate-deactivate of other users.
  - For field officers: a "My Work" panel linking to their committees/members/collections.
- **Expected outputs:** Updated `users` row; new avatar file on disk.

### 4.9 Authentication
- **Files:** `login.php`, `auth.php`, `logout.php`, `index.php`, `hash.php` (utility).
- **Purpose:** Login, logout, session bootstrap.
- **Main features:**
  - Password hashing via `password_hash()` / `password_verify()`.
  - Session creation with `user_id`, `role`, `name`.
  - Animated login page.
  - Auto-redirect: logged-in users → `dashboard.php`; otherwise → `login.php`.
- **Expected outputs:** A populated `$_SESSION` and a redirect.

### 4.10 Global Search & Notifications
- **File:** `includes/topbar.php` + `api/search.php` + `api/notifications.php`.
- **Purpose:** Cross-entity search and alert feed.
- **Main features:**
  - Live search (members, committees, officers) with debounce.
  - Notifications dropdown: overdue loans, pending approvals, today's collections, new members, low savings.
- **Expected outputs:** JSON API responses consumed by the topbar widgets.

---

## 5. System Architecture

### High-level

```
┌──────────────────────────────────────────────────────────┐
│  Browser (HTML + Bootstrap/Tailwind + Vanilla JS)        │
└──────────────────────────────┬───────────────────────────┘
                               │ HTTP (GET / POST)
┌──────────────────────────────▼───────────────────────────┐
│  Apache (XAMPP)                                           │
│  ┌────────────────────────────────────────────────────┐  │
│  │  PHP 8.x (server-rendered pages)                   │  │
│  │  - Pages: members/, loans/, savings/, ...          │  │
│  │  - Includes: includes/header.php, sidebar, topbar  │  │
│  │  - JSON API: api/*.php                             │  │
│  └────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────┐  │
│  │  MySQL 8 / MariaDB (database: MicroFinance)        │  │
│  │  - Connected via mysqli (config/db.php)            │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

### Frontend
- **Stack:** HTML5, Bootstrap 5 (CDN), TailwindCSS 4 (built via `npm run dev`), Font Awesome 6, Google Fonts (Inter).
- **Layout:** Sidebar + topbar + main content shell (from `includes/`).
- **Theming:** Glassmorphism / dark-blue gradient on the dashboard, lighter Bootstrap-style pages on submodules.
- **JavaScript:** Vanilla JS for sidebar toggle, global search, notification polling, profile menus.

### Backend
- **Pattern:** Server-rendered PHP pages (each module is a folder of `.php` files).
- **DB layer:** `mysqli` with prepared statements.
- **Sessions:** `$_SESSION` populated in `auth.php` and `login.php`; checked at the top of every protected page.
- **Validation:** Mostly HTML5 (`required`, `type="email"`, etc.); server-side validation is minimal.

### Database
- **Engine:** MySQL (or MariaDB compatible).
- **Connection:** `config/db.php` opens `mysqli` to `127.0.0.1:3306` with database `MicroFinance`.
- **Timezone:** `Asia/Dhaka` set at connection.

### Authentication
- **Mechanism:** Username + password → `password_verify()` → `$_SESSION['user_id']`, `['role']`, `['name']`.
- **Logout:** `session_destroy()` then redirect to `index.php`.

### API Communication
- **Style:** Project-internal JSON endpoints (not a public REST API).
- **Endpoints:** `api/search.php`, `api/notifications.php`, `api/dashboard-stats.php`, `api/members.php`, `api/committees.php`.
- **Auth:** They check `$_SESSION['user_id']` and return `401` JSON if missing.
- **Called from:** Topbar's live search and notification dropdown.

### File Uploads
- **Avatar:** `profile/upload-avatar.php` accepts image files; saves to `uploads/avatars/`.
- **No other file uploads** detected in the codebase.

### Session Handling
- PHP native sessions started at the top of every page (`session_start()`).
- Session regenerates per login (no explicit `session_regenerate_id`).
- Logout fully destroys the session.

### HTTPS / Cookies
- **Status:** No `session.cookie_secure` or `session.cookie_httponly` flags set in code (rely on defaults). See **Security Features** for recommendations.

---

## 6. Database Overview

> The DDL is **not** present in the repo (only PHP queries referencing tables). The following schema is *inferred* from every query in the codebase.

### Entities

#### `users`
Stores system users (admins, branch managers, field officers, and the optional `member` role).

| Column | Type | Notes |
|--------|------|-------|
| `user_id` | INT PK | Auto-increment id. |
| `username` | VARCHAR | Unique, used at login. |
| `password_hash` | VARCHAR | Bcrypt via `password_hash()`. |
| `full_name` | VARCHAR | Display name. |
| `phone` | VARCHAR | Optional. |
| `role` | VARCHAR | `admin`, `branch_manager`, `field_officer`, `member`. |
| `branch_id` | INT FK | → `branches.branch_id`. |
| `avatar` | VARCHAR | Filename in `uploads/avatars/`. |
| `is_active` | TINYINT | 1 active, 0 inactive. |
| `created_at` | DATETIME | Set on insert. |

#### `branches`
| Column | Type | Notes |
|--------|------|-------|
| `branch_id` | INT PK | |
| `branch_name` | VARCHAR | Display name (e.g., "Dhaka Main"). |

#### `committees`
| Column | Type | Notes |
|--------|------|-------|
| `committee_id` | INT PK | |
| `committee_name` | VARCHAR | |
| `branch_id` | INT FK | → `branches`. |
| `field_officer_id` | INT FK | → `users`. |
| `meeting_day` | VARCHAR | `Sun`, `Mon`, ... |
| `meeting_time` | TIME | |
| `formed_date` | DATE | |
| `is_active` | TINYINT | |

#### `members`
| Column | Type | Notes |
|--------|------|-------|
| `member_id` | INT PK | |
| `member_code` | VARCHAR | Human-friendly identifier. |
| `full_name` | VARCHAR | |
| `phone` | VARCHAR | |
| `dob` | DATE | |
| `address` | TEXT | |
| `national_id` | VARCHAR | |
| `guarantor_name` | VARCHAR | |
| `guarantor_phone` | VARCHAR | |
| `committee_id` | INT FK | → `committees`. |
| `branch_id` | INT FK | → `branches`. |
| `join_date` | DATE | |
| `is_active` | TINYINT | |
| `created_at` | DATETIME | Server-generated. |

#### `loans`
| Column | Type | Notes |
|--------|------|-------|
| `loan_id` | INT PK | |
| `loan_code` | VARCHAR | |
| `member_id` | INT FK | → `members`. |
| `branch_id` | INT FK | → `branches`. |
| `principal_amount` | DECIMAL | |
| `interest_rate` | DECIMAL | |
| `interest_type` | VARCHAR | `flat` or `reducing_balance`. |
| `loan_term_months` | INT | |
| `installment_type` | VARCHAR | `monthly` or `weekly`. |
| `installment_amount` | DECIMAL | |
| `total_payable` | DECIMAL | |
| `total_paid` | DECIMAL | Updated on every payment. |
| `disbursement_date` | DATE | |
| `first_installment_date` | DATE | |
| `maturity_date` | DATE | |
| `status` | VARCHAR | `active`, `closed`, `overdue`, `written_off`, `pending`. |
| `purpose` | TEXT | |
| `next_due_date` | DATE | Referenced by `api/notifications.php`. |
| `created_at` | DATETIME | |

#### `loan_payments`
| Column | Type | Notes |
|--------|------|-------|
| `payment_id` | INT PK | |
| `loan_id` | INT FK | → `loans`. |
| `member_id` | INT FK | → `members` (denormalized). |
| `amount` | DECIMAL | |
| `payment_date` | DATE | |
| `note` | TEXT | |
| `collected_by` | INT FK | → `users` (used by `notifications.php`). |

#### `loan_installments`
| Column | Type | Notes |
|--------|------|-------|
| `installment_id` | INT PK | |
| `loan_id` | INT FK | → `loans`. |
| `installment_no` | INT | Sequence. |
| `amount` | DECIMAL | |
| `due_date` | DATE | |
| `status` | VARCHAR | `pending`, `paid`. |
| `paid_date` | DATE | Set on payment. |

#### `savings`
| Column | Type | Notes |
|--------|------|-------|
| `saving_id` | INT PK | |
| `member_id` | INT FK | → `members`. |
| `saving_type` | VARCHAR | E.g., `regular`, `fixed`. |
| `balance` | DECIMAL | Running total. |
| `last_transaction_date` | DATE | |

#### `savings_transactions`
| Column | Type | Notes |
|--------|------|-------|
| `txn_id` | INT PK | |
| `saving_id` | INT FK | → `savings`. |
| `type` | VARCHAR | `deposit`, `withdrawal`. |
| `amount` | DECIMAL | |
| `balance_after` | DECIMAL | Snapshot after the txn. |
| `txn_date` | DATETIME | |
| `processed_by` | INT FK | → `users`. |
| `notes` | TEXT | |

### Relationships

```
branches 1 ──── * users          (branch_id)
branches 1 ──── * committees     (branch_id)
branches 1 ──── * members        (branch_id)
branches 1 ──── * loans          (branch_id)

users    1 ──── * committees     (field_officer_id)
users    1 ──── * loan_payments  (collected_by)

committees 1 ─── * members       (committee_id)
members  1 ──── * loans          (member_id)
members  1 ──── * savings        (member_id)

loans 1 ──── * loan_payments     (loan_id)
loans 1 ──── * loan_installments (loan_id)

savings 1 ─── * savings_transactions (saving_id)
```

---

## 7. Main Business Rules

These rules are extracted from the code. *"Needs clarification"* is used where the code does not yet enforce a rule.

| # | Rule | Enforcement |
|---|------|-------------|
| 1 | A member can have multiple loans. | Implicit (no unique constraint on `loans.member_id`). |
| 2 | A loan is always tied to a member and a branch. | Enforced by `loans/add.php` (branch copied from `members`). |
| 3 | Loan amount & terms are validated on the form (HTML5). | Enforced at the form level; server side has minimal validation. |
| 4 | Loan status starts as `active` when created. | Hard-coded in `loans/add.php`. |
| 5 | `total_payable` is calculated from principal + interest. | Calculated in `loans/add.php` (flat or simple reducing-balance approach). |
| 6 | `maturity_date` = `disbursement_date` + term months. | Calculated in `loans/add.php`. |
| 7 | `installment_amount` = `total_payable` / `loan_term_months`. | Calculated in `loans/add.php`. |
| 8 | A loan payment inserts into `loan_payments`, recomputes `total_paid`, and marks the next pending installment as `paid`. | Implemented in `installments/payment.php`. |
| 9 | Savings deposits/withdrawals update `savings.balance` and append to `savings_transactions`. | Implemented in `savings/deposit.php` and `savings/withdraw.php`. |
| 10 | Withdrawals cannot exceed the current balance. | Enforced in `savings/withdraw.php` (returns "Insufficient Balance!"). |
| 11 | A committee can be active or inactive. | Enforced via `committees.is_active`. |
| 12 | Members can be assigned to a committee only if active. | Enforced in `Committees/assign-member.php`. |
| 13 | Each committee has exactly one field officer. | Schema-level design. |
| 14 | A user can view other profiles only if admin or branch manager (and only if branch manager → field officer). | Enforced in `profile.php`. |
| 15 | A user can only edit their own profile unless admin/branch_manager. | Enforced in `profile/edit.php`. |
| 16 | Password change requires the current password. | Enforced in `profile/change-password.php`. |
| 17 | New password must be at least 6 characters and must match confirmation. | Enforced in `profile/change-password.php`. |
| 18 | All protected pages check `$_SESSION['user_id']` and redirect to `index.php` otherwise. | Implemented in every page. |
| 19 | Login fails if username not found or password does not verify. | Enforced in `login.php`. |
| 20 | Overdue loans are loans whose `maturity_date` has passed with `status = 'active'` (dashboard) **or** loans whose `next_due_date < CURDATE()` (notifications). | Two different definitions exist — *Needs clarification* for unified business rule. |
| 21 | A loan becomes a "Due" candidate when `remaining > 0`; UI tiers: `<= 0` → Paid, `< 5000` → Near Close, else → Due. | Hard-coded threshold in `due_system/index.php`. |
| 22 | The "Low Savings" alert triggers when `balance < 100`. | Hard-coded in `api/notifications.php`. |
| 23 | Notifications are role-aware: admins see overdue/pending approvals; field officers see today's collections; everyone sees new-member/low-savings summaries. | Enforced in `api/notifications.php`. |

---

## 8. Complete Feature List

### Authentication & User Management
- ✅ Login page with animated background
- ✅ Logout
- ✅ Session-based auth on every page
- ✅ Password hashing with bcrypt
- ✅ Profile view (own + others, with role-aware actions)
- ✅ Profile edit (name, phone, branch)
- ✅ Change password
- ✅ Avatar upload
- ✅ Role-aware admin actions (reset password, force logout, change role, activate/deactivate) — UI exists; backend endpoints marked in `profile.php` (e.g., `profile/delete.php`, `profile/reset-password.php`, `profile/force-logout.php`, `profile/change-role.php`) are **not yet implemented** (Needs clarification)

### Dashboard
- ✅ Stat cards (members, savings, loans, collection, due, overdue)
- ✅ Loan health percentage
- ✅ Top borrower card
- ✅ 6-month chart (loans vs collection)
- ✅ Recent transactions table
- ✅ Recent members list
- ✅ Quick action buttons (visual; navigation links to `#`)

### Member Management
- ✅ List members with pagination-free scrolling
- ✅ Filter by branch, status, search by name/code/phone/NID
- ✅ Add member
- ✅ Edit member
- ✅ Delete member
- ✅ View member profile (with loans / savings / payments tabs)
- ✅ CSV export of members
- ✅ Member loan chart (per-member)
- ✅ Quick view modal

### Committee Management
- ✅ List committees (card grid)
- ✅ Add committee
- ✅ Edit committee
- ✅ Toggle active/inactive
- ✅ Delete committee
- ✅ Filter by branch, status, meeting day
- ✅ Assign single member
- ✅ Bulk assign multiple members
- ✅ Remove member from committee
- ✅ View committee details

### Loan Management
- ✅ List loans with stats
- ✅ Add loan (auto-calculate total/installment/maturity)
- ✅ Edit loan
- ✅ Delete loan
- ✅ View loan details (printable)
- ✅ Status badges (active, closed, overdue, written_off)
- ✅ Print loan details
- ✅ Loan payment recording (inside `loans/payment.php` — present)

### Installments / Daily Collection
- ✅ Recent payments quick view
- ✅ Collect payment (insert payment + update total_paid + mark installment paid)
- ✅ Edit payment
- ✅ Delete payment
- ✅ Full payment list

### Savings
- ✅ List savings accounts
- ✅ Add savings account
- ✅ Deposit
- ✅ Withdraw (with balance check)
- ✅ Transaction history
- ✅ Member-level savings view

### Due System
- ✅ Due list with status tiers (Paid / Near Close / Due)
- ✅ Overdue list
- ✅ Report view

### Profile
- ✅ Avatar upload
- ✅ Edit personal info
- ✅ Change password
- ✅ Role-based action panel (admin/branch_manager)
- ✅ Field officer "My Work" links

### Global Search & Notifications
- ✅ Live search (members / committees / officers)
- ✅ Notification dropdown (overdue, pending approvals, today's collections, new members, low savings)
- ✅ Mark all notifications as read
- ✅ Per-role notifications

### Reporting
- ✅ Dashboard KPIs
- ✅ CSV export (members)
- ✅ Printable loan details
- ⚠️ Full accounting / ledger report (Not implemented)

### Audit & Security
- ✅ Session-based auth
- ✅ `password_hash()` / `password_verify()`
- ✅ Restricted profile access by role
- ⚠️ Explicit admin/manager backend actions listed in UI but endpoints not all implemented (delete, reset, force-logout, change-role handlers missing)
- ❌ Audit log table (Not implemented; needs clarification)

### Missing Features (Not yet implemented)
- ❌ Two-factor authentication
- ❌ Email/SMS notifications
- ❌ Member self-service portal
- ❌ Mobile responsive design beyond basic CSS
- ❌ Full transaction ledger (accounting)
- ❌ Multi-language support (UI is English-only)
- ❌ Automated penalty calculation engine
- ❌ Role management UI (roles are seeded in DB)

---

## 9. Folder Structure

```
MicroFinance/
├── api/                          # JSON endpoints (search, notifications, dashboard stats, etc.)
│   ├── search.php
│   ├── notifications.php
│   ├── dashboard-stats.php
│   ├── members.php
│   └── committees.php
├── assets/
│   ├── css/                      # Compiled CSS + custom CSS per module
│   │   ├── app.css               # Tailwind output
│   │   ├── committees.css
│   │   ├── dashboard.css
│   │   ├── loans.css
│   │   ├── members.css
│   │   ├── profile.css
│   │   ├── sidebar.css
│   │   ├── topbar.css
│   │   ├── responsive.css
│   │   ├── icons/
│   │   ├── images/
│   │   └── js/                   # Frontend JS
│   │       ├── app.js
│   │       ├── committees.js
│   │       ├── dashboard.js
│   │       ├── members.js
│   │       ├── profile.js
│   │       └── charts.js
├── config/
│   └── db.php                    # MySQLi connection (host, user, pass, db)
├── includes/                     # Shared layout pieces
│   ├── header.php                # Auth guard + <head> + Bootstrap/Tailwind links
│   ├── sidebar.php               # Navigation menu
│   ├── topbar.php                # Search + notifications + user menu
│   ├── footer.php                # Closing body + script include
│   └── components/               # Reusable dashboard widgets
│       ├── stat-card.php
│       ├── chart-card.php
│       ├── health-card.php
│       ├── top-borrower.php
│       ├── overdue-alert.php
│       ├── recent-members.php
│       ├── recent-transactions.php
│       └── member/               # Member-page subcomponents
│           ├── filters.php
│           ├── modal.php
│           ├── page-header.php
│           ├── stats.php
│           └── table.php
├── members/
│   ├── index.php  add.php  edit.php  view.php  delete.php
│   ├── export_csv.php  loan_chart.php  quick_view.php
├── Committees/
│   ├── index.php  add.php  edit.php  view.php  delete.php
│   ├── members.php  assign-member.php  remove-member.php
│   ├── toggle-status.php
├── loans/
│   ├── index.php  add.php  edit.php  view.php  delete.php
│   └── payment.php
├── installments/
│   ├── index.php  payment.php  payment_list.php
│   ├── edit.php  delete.php
├── savings/
│   ├── index.php  add.php  deposit.php  withdraw.php
│   ├── transactions.php  report.php  member.php
│   ├── edit.php  delete.php
├── due_system/
│   ├── index.php  overdue.php  report.php
├── profile.php                   # Profile view
├── profile/
│   ├── edit.php
│   ├── change-password.php
│   └── upload-avatar.php
├── src/
│   └── input.css                 # Tailwind source
├── uploads/
│   └── avatars/                  # User avatars
├── config/db.php                 # DB connection
├── login.php  auth.php  logout.php  index.php
├── dashboard.php
├── hash.php                      # Utility: prints hash of "123456"
├── package.json                  # Tailwind build scripts
├── package-lock.json
└── .git/  .vscode/  node_modules/
```

### Notable conventions
- Each module folder mirrors the entity it manages (members, loans, etc.).
- Each module exposes `index.php`, `add.php`, `edit.php`, `view.php`, `delete.php` where applicable.
- AJAX is **not** used to render pages; `.php` files render full HTML.
- The `api/` folder is reserved for JSON-only endpoints consumed by the topbar and dashboard.

---

## 10. Technologies Used

| Layer | Technology | Purpose |
|-------|------------|---------|
| Web server | Apache (XAMPP) | Hosts PHP pages. |
| Language | PHP 8.x | Server-side rendering, DB queries, sessions. |
| Database | MySQL / MariaDB | Persistent storage. |
| DB driver | MySQLi (procedural/OO) | Direct DB access (`config/db.php`). |
| HTML templating | Raw PHP in `.php` files | Page composition. |
| CSS framework | Bootstrap 5 (CDN) + TailwindCSS 4 (built) | Layout, typography, components. |
| Icons | Font Awesome 6 (CDN) | UI icons. |
| Fonts | Google Fonts (Inter) | Typography. |
| Charting | Chart.js (CDN) | Dashboard line chart. |
| Build tool | Tailwind CLI (`npm run dev` / `build`) | Compiles `src/input.css` → `assets/css/app.css`. |
| Frontend JS | Vanilla JS | Search, notifications, profile menus, animations. |
| Version control | Git | Local repo with remote on GitHub. |

### Why these technologies?
- **PHP + XAMPP:** Zero-friction local development on Windows; works out of the box for the author.
- **mysqli (not PDO):** Closest to the procedural style used in much of the codebase; prepared statements still supported.
- **Bootstrap 5 + Tailwind:** Mixed use — Bootstrap for most module pages, Tailwind for select newer components.
- **Chart.js:** Lightweight, no build step needed.
- **No framework:** The codebase is intentionally lightweight and dependency-free outside CDN libraries.

---

## 11. External Dependencies

### PHP / Runtime
- PHP 8.x with `mysqli` and `password_hash` extensions.
- MySQL 8 / MariaDB 10+.

### CSS / JS (via CDN)
- Bootstrap 5.3 — `https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/`
- Bootstrap 5.3 JS bundle
- Font Awesome 6.7.2 — `cdnjs.cloudflare.com`
- Google Fonts (Inter) — `fonts.googleapis.com`
- Chart.js — `cdn.jsdelivr.net/npm/chart.js`

### NPM (dev dependency)
- `@tailwindcss/cli` (^4.3.1)
- `tailwindcss` (^4.3.1)

### Directories
- `node_modules/` — Tailwind toolchain.
- `uploads/avatars/` — User-uploaded files.

### External APIs
- **None.** The system is fully self-hosted; no third-party API calls.

### External Services
- **None.** No email, SMS, or payment gateway integration is present.

---

## 12. Security Features

### Authentication
- Bcrypt password hashing via `password_hash()` / `password_verify()`.
- Session-based login (`$_SESSION['user_id']`, `['role']`, `['name']`).
- `logout.php` calls `session_destroy()`.

### Authorization
- Every protected page checks `!isset($_SESSION['user_id'])` and redirects to `index.php`.
- `profile.php` blocks viewing other users unless viewer is `admin` or `branch_manager`.
- `profile/edit.php` and `profile/change-password.php` enforce the same role rules.
- `api/notifications.php` and `api/search.php` return 401 JSON if no session.

### Password Security
- Stored as bcrypt hashes; never stored in plaintext.
- Change-password requires old password and 6+ chars for the new one.

### Session Security
- Native PHP sessions.
- **Needs improvement:** No explicit `session_regenerate_id()` on login; no `cookie_secure`/`cookie_httponly` flags set in code.

### SQL Injection Prevention
- Most modern code uses prepared statements (`$stmt->bind_param(...)`).
- **Risk:** Several legacy files build SQL with string concatenation (e.g., `members/index.php`, `committee s/index.php`, `loans/add.php`, `loans/edit.php`, `savings/deposit.php`, `savings/withdraw.php`, `due_system/index.php`, `due_system/overdue.php`, `due_system/report.php`, `installments/payment.php`). Inputs are interpolated directly into SQL. See **Future Improvements**.

### CSRF Protection
- **Not implemented.** No anti-CSRF tokens present. Forms rely solely on session cookies.

### XSS Protection
- ✅ Uses `htmlspecialchars()` on most outputs.
- ⚠️ Some queries are echoed without escaping (e.g., `dashboard.php` recent transactions, `members/view.php` member name, `loans/view.php` purpose). Should be audited.

### Input Validation
- HTML5 `required`, `type="number"`, `minlength`.
- Server-side validation is minimal — most placeholders for `int` casts rely on PHP's loose typing.

### File Upload Security
- `profile/upload-avatar.php` accepts image files. Detailed validation (MIME, size, filename sanitization) **Needs clarification** by reading the implementation.

### Recommendations
1. Add CSRF tokens to all state-changing forms.
2. Convert all string-concatenated SQL to prepared statements.
3. Add `session_regenerate_id(true)` on login.
4. Set `cookie_secure` / `cookie_httponly` / `cookie_samesite` flags.
5. Add a centralized audit log table for all writes.
6. Standardize output escaping with a helper function.

---

## 13. Current Project Status

### Completed Modules
- ✅ Authentication (login, logout, sessions)
- ✅ Dashboard (KPI cards, chart, recent transactions, recent members)
- ✅ Member Management (full CRUD, filters, CSV export, view profile)
- ✅ Committee Management (full CRUD, member assignment, bulk assign, toggle status)
- ✅ Loan Management (full CRUD, calculations, printable view)
- ✅ Installments (payment recording, mark installment paid)
- ✅ Savings (account, deposit, withdraw, transactions, balance check)
- ✅ Due System (due list, overdue list, report)
- ✅ Profile + avatar upload + change password
- ✅ Global search (members, committees, officers)
- ✅ Notifications (per-role alert panel)
- ✅ Sidebar / Topbar / Responsive layout shell
- ✅ Tailwind build pipeline

### Partially Completed Modules
- ⚠️ **Loan approval workflow** — recommended in workflow but no explicit approval screen; loans are inserted as `active` directly.
- ⚠️ **Admin actions on user profiles** — UI buttons exist in `profile.php` for `delete`, `reset-password`, `force-logout`, `change-role`, but the corresponding backend files (`profile/delete.php`, `profile/reset-password.php`, `profile/force-logout.php`, `profile/change-role.php`) are **not present** in the repository — *Needs clarification / implementation*.
- ⚠️ **Field officer subpages** — `topbar.php` and `profile.php` link to `field-officer/dashboard.php`, `field-officer/members.php`, `field-officer/committees.php`, `field-officer/collections.php`; these folders are **not present** in the repo.
- ⚠️ **Committees/officers subfolder** — referenced in `profile.php` (`Committees/officers/view.php`, `Committees/officers/toggle-status.php`, `Committees/officers/index.php`) — not present.
- ⚠️ **Committee CRUD** — `add.php` and `edit.php` use string-concatenated SQL; needs hardening.
- ⚠️ **Reports** — only basic CSV and dashboard; no formal accounting reports.

### Missing Modules
- ❌ Member self-service portal
- ❌ Email/SMS notifications
- ❌ Two-factor authentication
- ❌ Multi-language support
- ❌ API rate limiting / brute-force protection
- ❌ Audit log table

### Known Issues
1. **SQL injection risk** in many legacy files (string concatenation).
2. **No CSRF tokens** in any form.
3. **Hardcoded background thresholds** (`< 5000` for "Near Close", `< 100` for low savings) — should be configurable.
4. **Two definitions of "overdue"** (maturity date vs next_due_date).
5. **Some referenced pages/links** (e.g., `field-officer/*`, `Committees/officers/*`) are missing.
6. **Quick action buttons on dashboard** link to `#` instead of actual forms.
7. **Mixed CSS frameworks** (Bootstrap + Tailwind) — increases tooling complexity.
8. **Untracked SQL migrations** — no migration tool; schema is implicit.

---

## 14. Future Improvements

### Scalability
- **Database:** Add indexes on `members.committee_id`, `loans.member_id`, `loans.branch_id`, `loan_payments.loan_id`, `loan_payments.collected_by`, `savings.member_id`.
- **Architecture:** Move from monolithic PHP to a thin API + frontend split (e.g., Laravel API + Vue/React frontend) when the team grows.
- **Caching:** Redis for sessions and notification lists.
- **Background jobs:** Queue system for penalty calculation, overdue alerts, and bulk emails.

### Maintainability
- Centralize DB access in a model class (e.g., `Member::find()`, `Loan::create()`).
- Replace legacy raw SQL with prepared statements everywhere.
- Introduce a router so all pages go through a single `index.php` entry point.
- Adopt a PSR-4 autoloader and Composer.
- Use a templating engine (Twig / Blade) for cleaner UI code.
- Introduce a database migration tool (Phinx / Laravel migrations).

### Performance
- Pagination on `members`, `loans`, `payments`, `transactions` tables.
- Lazy-load avatars and charts.
- Compress and cache CSS/JS bundles.

### User Experience
- Member-facing portal for self-service.
- Mobile-first redesign (current responsive CSS is partial).
- Real-time notifications via WebSockets or SSE.
- Dark/light theme toggle.
- Inline-edit for members on the list page.
- Better error messages and toast notifications.

### Security
- CSRF tokens on every form.
- Rate limiting on login and API endpoints.
- Force password reset on first login.
- Audit log table for all sensitive operations.
- 2FA via TOTP.

### Reporting & Analytics
- Full accounting ledger (double-entry).
- Branch-wise and officer-wise performance reports.
- PDF export for loan statements.
- Charts for savings growth, overdue trend, committee performance.

### Integration
- SMS gateway (e.g., Twilio, SSL Wireless) for payment reminders.
- Email via SMTP.
- Bank reconciliation API.
- Mobile app via REST API.

---

## 15. Development Roadmap

### Phase 1 — Harden & Stabilize (Highest Priority)
- [ ] Convert all SQL to prepared statements.
- [ ] Add CSRF tokens to all forms.
- [ ] Add server-side input validation.
- [ ] Implement missing admin endpoints (`profile/delete.php`, `profile/reset-password.php`, `profile/force-logout.php`, `profile/change-role.php`).
- [ ] Add DB indexes.
- [ ] Reduce mixed Bootstrap/Tailwind usage (pick one).
- [ ] Make hardcoded thresholds configurable (`due_tiers`, `low_savings_threshold`).

### Phase 2 — Fix Missing Pages & Roles (High Priority)
- [ ] Build `field-officer/` module (dashboard, members, committees, collections).
- [ ] Build `Committees/officers/` module (list, view, toggle status).
- [ ] Implement loan approval workflow (status `pending` → `active` UI).
- [ ] Add `Reports` module (daily collection, overdue, branch-wise summary).
- [ ] Wire up dashboard quick action buttons.

### Phase 3 — UX & Reporting (Medium Priority)
- [ ] Add pagination across all list pages.
- [ ] Add global date-range filter for dashboards.
- [ ] Add PDF export for loan statements.
- [ ] Add a printable member passbook.
- [ ] Improve mobile responsiveness.
- [ ] Add toast notifications instead of plain redirects.

### Phase 4 — Member Portal & Integrations (Low Priority)
- [ ] Build a member-facing portal (login + statement view).
- [ ] SMS notifications via gateway.
- [ ] Email notifications via SMTP.
- [ ] Integrate a payment gateway.

### Phase 5 — Long-Term (Future)
- [ ] Migrate to a modern framework (Laravel or Symfony).
- [ ] Split into REST API + frontend SPA.
- [ ] Multi-currency / multi-language support.
- [ ] Mobile app (Flutter / React Native).
- [ ] Advanced analytics with BI dashboards.

---

## 16. Glossary

| Term | Definition |
|------|------------|
| **Member** | A borrower/saver registered in the `members` table. Identified by `member_code`. |
| **Branch** | A physical location of the microfinance institution. The top-level organizational unit. |
| **Committee** | A group of members who meet regularly (usually weekly) to deposit savings and pay installments. Headed by a field officer. |
| **Field Officer** | An employee of the institution who collects installments from one or more committees. |
| **Loan** | A principal amount disbursed to a member, expected to be repaid with interest in installments. |
| **Principal** | The original loan amount borrowed. |
| **Interest Rate** | The percentage charged on the principal per year (or per month). |
| **Interest Type** | `flat` — interest calculated on principal; `reducing_balance` — interest calculated on outstanding balance. |
| **Total Payable** | Principal + interest over the loan term. |
| **Installment** | A periodic payment (monthly or weekly) made by the member toward the loan. |
| **Installment Amount** | `total_payable / loan_term_months`. |
| **Maturity Date** | The date when the loan term ends. |
| **Disbursement Date** | The date when the loan is paid out to the member. |
| **First Installment Date** | The date the first installment is due. |
| **Collection** | The act of field officers collecting installment payments during committee meetings. |
| **Savings** | Money deposited by a member into a savings account at the institution. |
| **Savings Account** | A row in the `savings` table tracked by `member_id` and `balance`. |
| **Deposit** | A `savings_transactions` row of type `deposit` that increases `balance`. |
| **Withdrawal** | A `savings_transactions` row of type `withdrawal` that decreases `balance`. |
| **Overdue** | A loan whose remaining balance is positive past its due/maturity date. |
| **Due** | A loan whose remaining balance is positive (regardless of date). |
| **Pending Loan** | A loan application awaiting approval (status `pending`). |
| **Closed Loan** | A loan fully repaid (status `closed`). |
| **Written-off Loan** | A loan declared unrecoverable (status `written_off`). |
| **Guarantor** | A person who vouches for a member and agrees to cover the loan if the member defaults. |
| **Ledger** | A complete accounting record of all transactions. (Not yet implemented — *Needs clarification* on the planned structure.) |
| **Branch Manager** | An employee who oversees all field officers in a branch. |
| **Admin** | A user with unrestricted access to the system. |
| **Notification** | A real-time alert shown in the topbar dropdown (overdue, pending approvals, new members, etc.). |
| **Audit Trail** | A historical record of all changes. (Not yet implemented.) |
| **Tailwind** | A utility-first CSS framework used to build `app.css`. |
| **Bootstrap** | A UI component framework used on most module pages. |
| **Chart.js** | A JavaScript charting library used for the dashboard trend chart. |
| **MySQLi** | The PHP extension used to talk to MySQL. |
| **Branch Code / Member Code / Loan Code** | Human-readable identifiers surfaced in the UI. |

---

*Last updated: based on a static analysis of the MicroFinance repository at the current commit. Where information is missing, items are explicitly marked as **Needs clarification** rather than guessed.*
