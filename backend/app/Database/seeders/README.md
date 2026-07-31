# `backend/app/Database/seeders/`

Seed scripts for inserting demo / fixture data (e.g. `001_demo_branches.sql`, `002_demo_admin.sql`).

**What comes next:**

- Capture any hardcoded reference data currently living inside the schema itself (branches, roles, system settings).
- Add a `tools/seed.php` runner that applies seeders in order.
- Separate **always-run** seeders (idempotent inserts) from **dev-only** demo data (only load when `APP_ENV=local`).
- Never seed test data into production — gate dev seeders behind an environment check.

**Naming convention:** `NNN_descriptive_name.sql` where `NNN` is a zero-padded sequence number.
