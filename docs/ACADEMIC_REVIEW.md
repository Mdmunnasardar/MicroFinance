# MicroFinance — Academic Review (Learning Perspective)

**Repository:** MicroFinance (PHP + MySQL, procedural PHP, vanilla JS, Tailwind)
**Reviewer:** Senior Engineering Audit · Academic Mode
**Date:** 2026-07-21

---

## 1. Executive Summary

| Academic Score  | 61 / 100                                                    |
| --------------- | ----------------------------------------------------------- |
| Best Use Case   | Learning project / tutorial demonstrator                    |
| Overall Verdict | Acceptable as a learning artifact; **not** enterprise grade |

> The codebase shows clear intent to build a working product and demonstrates many real features (sessions, password hashing, prepared statements in some files, AJAX endpoints). The biggest learning signal is the **inconsistency**: prepared statements are used in `login.php` and `api/search.php`, but raw string concatenation is used in many CRUD pages (`members/add.php`, `loans/add.php`, `installments/payment.php`, etc.). Studying **this very inconsistency** is one of the most educational things about the project.

---

## 2. Academic Scoring Table

| Area                          | Score (/10) | Comment                                                                                                                                                  |
| ----------------------------- | ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Code Clarity (readability)    | 6           | Mixed: short pages are clear, but `loans/index.php` (980 LOC) and `includes/topbar.php` (1112 LOC) are unwieldy.                                         |
| Learning-by-Doing Signals     | 8           | Multiple manual implementations of pagination, filters, charts, and forms — ideal for a beginner to step through.                                        |
| Use of PHP Fundamentals       | 7           | Sessions, `mysqli`, prepared statements, `password_verify`, file uploads, AJAX — all present, but inconsistently applied.                                |
| Use of Design Patterns        | 4           | A handful of small “render\*-component” helpers in `includes/components/`; no formal MVC, no repository layer, no service / repository split.            |
| Separation of Concerns        | 4           | PHP, SQL, and HTML are mixed inside the same file (`members/add.php`, `loans/index.php`). Helpful for learning, problematic for projects above ~5 KLOC.  |
| SQL Skill Demonstration       | 5           | Aggregate queries (`SUM`, `COUNT`, `CASE`, `GROUP BY`, subqueries) are all used — good. But injection-prone concatenation defeats the educational value. |
| Frontend / UX Demonstration   | 6           | Several polished UIs (login, dashboard, profile) with CSS animations, gradient backgrounds, chart.js integration.                                        |
| Security Awareness (baseline) | 3           | Prepared statements and `htmlspecialchars` are _partially_ applied. CSRF, escaping discipline, file-upload validation, role enforcement are absent.      |
| Documentation / Comments      | 4           | Inline comments exist on a few important sections; no README, no architecture notes.                                                                     |
| Beginner-Friendliness         | 8           | Project structure is intuitive (folder = feature), PHP files are short, easy to navigate.                                                                |

**Composite Academic Score:** **61/100** – “Good learning material; needs discipline to reach professional quality.”

---

## 3. Learning Strengths (with Examples)

### 3.1 Clear modular folder layout

Folders like `loans/`, `members/`, `Committees/`, `savings/`, `installments/`, `due_system/` map directly to the domain model. Beginners can locate the loan code by intuition alone.

```text
loans/
  ├─ index.php   (list)
  ├─ add.php     (create)
  ├─ edit.php    (update)
  ├─ delete.php  (delete)
  └─ view.php    (detail)
```

This is exactly the CRUD orientation that first-year web-development labs teach.

### 3.2 Reasonable authentication baseline

`login.php` demonstrates the canonical pattern: prepared statements + `password_verify()` + session variables.

```php
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id'] = $user['user_id'];
    ...
}
```

Good teaching seed: discuss how to add password hashing on `register`, password reset via email tokens, 2FA, account lockout, and session regeneration on privilege change.

### 3.3 Real, working analytics

`dashboard.php` builds monthly line chart data with a 6-month loop over `loan_payments` and `loans` tables.
That teaches beginners how to:

- assemble chart data on the server,
- pass it to JS with `json_encode`,
- render it client-side with Chart.js.

### 3.4 Reusable component functions

`includes/components/stat-card.php` and friends encapsulate small UI widgets:

```php
function renderStatCard($icon, $color, $value, $label, ...) { ... }
```

This is a deliberately tiny take on the **DRY principle** and partial application of the **template method** pattern. It’s a great teachable example of how small helper functions rescue copy-pasted HTML.

### 3.5 Notifications / partial global state

`api/notifications.php` builds a role-aware notification list using SQL `COUNT()` queries. It teaches:

- how to branch logic on `$_SESSION['role']`,
- how to encode role-based responses as JSON,
- the importance of graceful `try/catch` when tables don’t yet exist.

---

## 4. Learning Gaps (with Educational Explanations)

### 4.1 SQL injection everywhere

Files like `members/add.php`, `members/edit.php`, `members/delete.php`, `loans/add.php`, `loans/edit.php`, `installments/payment.php`, `installments/edit.php`, `installments/delete.php`, `savings/add.php`, `savings/deposit.php`, `savings/withdraw.php`, and `members/loan_chart.php` interpolate `$_POST` / `$_GET` directly into SQL:

```php
// members/edit.php
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM members WHERE member_id=$id");
```

> **Lesson to extract:** every one of these is a textbook example for a class on **prepared statements**. Have students refactor each one. The fact that `login.php` _does_ use a prepared statement while its sibling CRUD pages do not is itself a teachable moment about inconsistent mental models.

### 4.2 No CSRF protection

No file in the project issues, stores, or validates a CSRF token. Every state-changing form (`installments/payment.php`, `savings/withdraw.php`, `members/delete.php`, `Committees/toggle-status.php`) can therefore be triggered by a hostile site.

> **Lesson:** introduce the _synchronizer token pattern_, `hash_equals()`, and the `SameSite=Lax` cookie attribute.

### 4.3 No authorization beyond login

Any authenticated user can delete members, change roles, deactivate field officers, or withdraw from any savings account. Two files in `Committees/` and `profile/change-password.php` show a small role check; the rest don’t.

> **Lesson:** teach _authentication != authorization_. Walk through implementing a `requireRole(['admin','branch_manager'])` helper and applying it to every endpoint.

### 4.4 Escape discipline is uneven

Some places use `htmlspecialchars()` (good) and others print raw DB values:

```php
// due_system/index.php
<td><?php echo $row['full_name']; ?></td>
```

> **Lesson:** HTML escaping is a _cross-cutting concern_; route every dynamic value through one helper, similar to how React escapes by default. The drift in this codebase is a perfect illustration of why a templating system is useful.

### 4.5 Session handling

`logout.php` calls `session_destroy()` but doesn’t unset the cookie. There is no `session_regenerate_id()` after login (session fixation risk).

> **Lesson:** regenerate the session id on privilege escalation, destroy the cookie, set `SameSite`, and consider `HttpOnly` + `Secure` flags via `php.ini` or `session_set_cookie_params()`.

### 4.6 Raw mysqli everywhere

This is great for _learning fundamentals_, but real projects generally use a thin DB layer (PDO, Eloquent, Doctrine). For your **next iteration**, students could refactor this to PDO with named parameters:

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
$stmt->execute([':u' => $username]);
```

This is a much stronger pedagogical upgrade.

### 4.7 Output buffering / view bootstrapping

Every page repeats the “session + auth + include header + include sidebar + include topbar + echo HTML” dance. That’s 25+ duplicate snippets, a textbook use case for a small front controller / bootstrap.

### 4.8 Configuration

`config/db.php` hard-codes a local MySQL on port 3306 with `root`/`""`. There is no `.env`, no fallback for environment variables.

```php
$host = "127.0.0.1";
$user = "root";
$pass = "";
```

> **Lesson:** teach `getenv()`, `vlucas/phpdotenv`, and the 12-factor principle.

### 4.9 Hardcoded credentials in `hash.php`

```php
echo password_hash("123456", PASSWORD_DEFAULT);
```

Great for a tutorial, dangerous to leave in a repo. Useful talking point on **secrets management**.

### 4.10 Magic numbers

`profile/upload-avatar.php`: `'5 * 1024 * 1024'` for max upload size is fine for teaching, but a constant `MAX_AVATAR_BYTES` reads better.

---

## 5. Code Quality Metrics

| Metric                              | Value (approx.)                                                                     | Notes                                                                                                              |
| ----------------------------------- | ----------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| Total source files (PHP + JS + CSS) | ~88 (excluding node_modules)                                                        | +1 settings file, 1 launch.json, 1 image.                                                                          |
| Lines of code                       | ~17,000 (all languages)                                                             | Including ~6,200 LOC of CSS/JS.                                                                                    |
| Largest files                       | `loans/index.php` 980 LOC, `includes/topbar.php` 1112 LOC, `loans/view.php` 917 LOC | All mix HTML, PHP, SQL, and JS.                                                                                    |
| Average file size                   | ~190 LOC                                                                            | Skewed by a few over-large views.                                                                                  |
| Functions defined                   | < 15                                                                                | Mostly inside `includes/components/*.php`.                                                                         |
| Use of classes / OOP                | None                                                                                | Pure procedural code.                                                                                              |
| Duplicated logic                    | ~25 % (estimate)                                                                    | Session/auth header blocks, pagination, dashboard stat blocks, topbar/sidebar includes, AJAX endpoint boilerplate. |
| Hardcoded constants                 | Many                                                                                | Currency, percentages, magic numbers, gradient colors per page.                                                    |
| Inline `<style>` blocks             | Yes (10+ files)                                                                     | Login, profile, dashboard, loans, etc.                                                                             |
| Comment ratio                       | Low (~3 %)                                                                          | Mostly section banners, no PHPDoc.                                                                                 |

---

## 6. Beginner-Friendly Observations

- **Folder = feature** is easy to teach, but a true beginner can’t see the implicit contract (every page does `session_start()` + redirect + include header + render HTML). This is worth a one-page documentation page.
- **The dashboard hero** is an excellent teaching artifact because it shows the entire read-only stack: queries → aggregates → PHP arrays → `json_encode` → Chart.js. A lab assignment could be: “add a third dataset for savings deposits.”
- **`api/notifications.php`** is a fine intro to role-based JSON APIs. Teach students to add an HTTP `401` for guests (already done), but also `403` for insufficient role (not yet done).
- **`index.php`, `logout.php`, `auth.php`** are very small and good first reads.
- **Database schema is implicit** — there is no `.sql` file. Each page assumes tables exist (`users`, `members`, `loans`, `loan_payments`, `loan_installments`, `savings`, `savings_transactions`, `branches`, `committees`). For learning, an `INSTALL.sql` is essential.

---

## 7. Pattern Implementation Analysis

| Pattern                 | Present? | Where                   | How to introduce                                                                                    |
| ----------------------- | -------- | ----------------------- | --------------------------------------------------------------------------------------------------- |
| Front Controller        | ❌       | –                       | Move bootstrap into `public/index.php` + router.                                                    |
| MVC                     | ❌       | –                       | Folder-per-feature is _almost_ MVC; split into `Controllers`, `Models`, `Views`.                    |
| Repository / DAO        | ❌       | –                       | Wrap each table in a class so SQL is reused.                                                        |
| Service layer           | ❌       | –                       | Transactional logic (deposit, withdraw, payment) should live in a service to guarantee atomicity.   |
| Template Method (light) | ✅       | `includes/components/*` | Discuss how to promote it to a real Twig/Blade view.                                                |
| Strategy / State        | ❌       | –                       | Loan statuses (`active`, `closed`, `overdue`, `pending`) are perfect candidates for state machines. |
| Observer / Event Bus    | ❌       | –                       | `savings_transactions` is a perfect “audit trail” pattern teachable here.                           |
| Dependency Injection    | ❌       | –                       | Currently globals (`$_SESSION`, `$conn`); talk about constructor injection.                         |

---

## 8. Recommendations for Learning

### 8.1 Refactor Sprint Ideas (each becomes a graded assignment)

1. **Migration to prepared statements**
   - Add `(int)$_GET['id']` casts first.
   - Replace each concatenation with `prepare()/bind_param()/execute()`.
   - Verify behavior with the same tests as step 4.

2. **Centralized bootstrap**
   - Create `includes/bootstrap.php` that calls `session_start`, requires auth, loads `config/db.php`, and exposes helpers (`h()`, `csrf_token()`, `verify_csrf()`).
   - Move the auth-redirect check into a single line `require auth();`.

3. **Form helper**
   - Write a `form_open($action, $method)` and `csrf_field()` helper.
   - Migrate five forms to use it.

4. **First PHPUnit suite**
   - Add `tests/` and a `phpunit.xml`.
   - Test the savings withdraw flow using an in-memory SQLite or a transactional MySQL fixture.
   - Test notification filtering by role.

5. **Add `.env` configuration**
   - Introduce `vlucas/phpdotenv` via Composer.
   - Move DB credentials out of `config/db.php`.

6. **HTML escape helper**
   - Add `function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }`.
   - Replace every dynamic echo.

### 8.2 Suggested Reading List to Pair With This Codebase

- _PHP: The Right Way_ (security & best practices chapters).
- _OWASP Top 10_ (SQLi, XSS, CSRF, IDOR, broken access control).
- _12-Factor App_ (config, envs, disposability).
- _Clean Code in PHP_ (function length, naming, SRP).

---

## 9. Final Academic Assessment

| Rating               | Explanation                                                                                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| 61/100               | Strong teaching artifact but inconsistent. The same junior team could push this to ~80/100 in a single structured sprint. |
| Suitable for         | Capstone projects, classroom demos, internship on-ramps.                                                                  |
| Not yet suitable for | Production finance/loan platforms without significant remediation.                                                        |

**One-line takeaway:**

> Treat this codebase as a _living textbook_: the bugs are the homework, the refactors are the exam, and the eventual cleanup is the graduation project.
