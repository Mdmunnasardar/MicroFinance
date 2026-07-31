# `backend/app/Database/migrations/`

Versioned SQL migration files for evolving the database schema.

**What comes next:**

- Capture the current schema as the baseline (`0001_baseline.sql`).
- Adopt a migration runner (a small PHP script in `tools/` or a library) that tracks applied migrations in a `_migrations` table.
- Number files like `0002_add_phone_to_members.sql`, `0003_drop_legacy_column.sql`, etc.
- Make every migration idempotent where possible (`CREATE TABLE IF NOT EXISTS`).
- Keep migration files append-only — never edit a migration that has already been applied to production.

**Naming convention:** `NNNN_descriptive_name.sql` where `NNNN` is a zero-padded sequence number.
