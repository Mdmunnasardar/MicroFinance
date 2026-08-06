# Tests

Automated test suite for the backend.

**What comes next:**

- Start with PHPUnit (or a minimal hand-rolled runner) — pick one and stick with it.
- Mirror the directory structure of `backend/app/Controllers/JsonApi/` for unit tests.
- Add an integration test that connects to a dedicated test database (`MicroFinance_test`) and runs migrations + seeders before each test class.
- Cover the business-logic-critical paths first: loan calculation, savings balance, payment allocation, installment schedule.
- Add a CI step that runs the suite on every push (GitHub Actions / GitLab CI / etc.).

**Naming convention:** `*Test.php` for class-based tests, `test_*.php` for functional tests.
