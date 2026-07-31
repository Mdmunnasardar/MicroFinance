# `backend/app/Config/`

Application configuration (currently only `db.php` lives here).

**What comes next:**

- Split `db.php` into separate config files per concern (e.g. `app.php`, `database.php`, `cache.php`, `auth.php`).
- Support `.env` style overrides loaded from environment variables instead of hardcoded values.
- Add typed config classes that return immutable value objects (e.g. `Config::db()->host()`).
- Move deployment-specific values (timezone, error reporting level) out of `db.php` into a dedicated `app.php`.

**Naming convention:** Use `snake_case.php` for config file names.
