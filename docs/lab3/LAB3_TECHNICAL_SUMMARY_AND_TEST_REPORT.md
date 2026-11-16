# LAB 3 TECHNICAL SUMMARY & END-TO-END TEST REPORT

- **Course:** Web Programming (`CC01`) — Ho Chi Minh City University of Technology (HCMUT)
- **Student:** Thái Khang (`2452475`)
- **Personal Assignment Topic:** **T1 — Gym & Fitness Center Website (`TIGER FITNESS`)**
- **Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)

---

## 1. Two Meaningful AJAX Features Implemented

### 1.1 AJAX Feature 1: Live Multi-Criteria Class Search & Filtering
- **Frontend Trigger:** `bookstore/pages/classes.php` + `initAjaxClassSearch()` in `bookstore/public/js/script.js`
- **Backend Endpoint:** `bookstore/api/search_classes.php` (`GET`)
- **Communication Flow (Browser ↔ PHP ↔ MySQL):**
  1. Typing in `#search` (250 ms debounce) or changing `#category_id`, `#trainer`, `#availability`, `#filter_date`, or `#sort` dispatches a `fetch('../api/search_classes.php?...')` request without reloading the page.
  2. `search_classes.php` builds a parameterized SQL query using PDO Prepared Statements joining `fitness_classes`, `categories`, and an aggregated `class_bookings` subquery (`WHERE booking_date = :booking_date AND status = 'confirmed'`).
  3. Filtering by category also updates the `apex_fav_category` cookie (preserving Lab 2 preference behavior).
  4. The JSON payload returns `{ success, date, count, is_staff, classes[] }` including calculated `remaining_spots` and `is_full`.
  5. `renderClassesGrid()` safely renders the updated Bootstrap card grid using `escapeHtml()` and handles empty search states cleanly (`count === 0`).

### 1.2 AJAX Feature 2: Real-Time Class Capacity & Instant Reservation / Cancellation
- **Frontend Trigger:** `bookstore/pages/item_details.php`, `bookstore/pages/bookings.php` + `initLiveCapacityCheckers()` & `initBookingTableActions()` in `bookstore/public/js/script.js`
- **Backend Endpoints:** `bookstore/api/class_capacity.php` (`GET`) and `bookstore/api/booking_action.php` (`POST`)
- **Communication Flow:**
  1. Selecting any date (`#capacity_date`) or clicking the 4-day quick forecast buttons (`#capacity-date-pills`) triggers `fetch('../api/class_capacity.php?class_id=...&date=...')`.
  2. `class_capacity.php` invokes `getClassCapacitySnapshot()` and returns real-time `max_capacity`, `booked_count`, `remaining_spots`, `fill_percent`, `user_already_booked`, and a 4-day forecast array.
  3. The UI updates the capacity progress bar, spots badge, and Book button state immediately without page reload.
  4. Authenticated users can book, cancel, or (for staff) update booking statuses via `fetch('../api/booking_action.php')` with `X-CSRF-Token` validation and immediate feedback.

---

## 2. Topic T1 Mandatory Business Workflow (`Membership -> Fitness Class -> Schedule -> Booking`)

Implemented centrally in `bookstore/config/booking_service.php` and exposed via `bookstore/pages/bookings.php` (progressive form POST + AJAX) and `bookstore/api/booking_action.php`:

1. **Database Extensions (`gym_fitness_db.sql`):**
   - Added `max_capacity` column to `fitness_classes` (e.g., `Olympic Weightlifting & Explosive Power` has `max_capacity = 5` and 4 pre-seeded confirmed bookings for tomorrow to demonstrate live capacity enforcement).
   - Added `membership_plans` (`STARTER` max 2 concurrent bookings, `PRO` max 5, `VIP` max 15).
   - Added `user_memberships` tracking user subscriptions, status, `start_date`, and `end_date`.
   - Added `class_bookings` linking `users`, `fitness_classes`, and `user_memberships` with indexed `(class_id, booking_date, status)`.
2. **Server-Side Transaction & Business Rules (`createClassBooking()`):**
   - Validates date format (`YYYY-MM-DD`) and restricts bookings to today through +60 days.
   - Executes inside `$pdo->beginTransaction()` with `SELECT ... FOR UPDATE` on the target `fitness_classes` row to prevent race conditions.
   - **Rule 1 (Membership Gate):** Blocks users without an active unexpired row in `user_memberships` (`NO_ACTIVE_MEMBERSHIP`, HTTP 409).
   - **Rule 2 (Plan Quota):** Blocks when `active_bookings_count >= max_active_bookings` (`MEMBERSHIP_QUOTA_REACHED`, HTTP 409).
   - **Rule 3 (Duplicate Prevention):** Blocks booking the same class twice on the same date (`DUPLICATE_BOOKING`, HTTP 409).
   - **Rule 4 (Capacity Enforcement):** Counts `confirmed` bookings for `(class_id, booking_date)`; if `booked_count >= max_capacity`, rolls back and returns `CAPACITY_FULL` (HTTP 409).

---

## 3. Security Review & Improvements (Lab 3 Task 4)

| Area | Improvement Implemented | Location |
|---|---|---|
| **SQL Injection** | 100% PDO Prepared Statements (`ATTR_EMULATE_PREPARES => false`) across all pages and new AJAX endpoints. | `config/database.php`, `api/*.php`, `config/booking_service.php` |
| **CSRF Protection** | Added cryptographic per-session CSRF token (`bin2hex(random_bytes(32))`), `csrfField()`, and constant-time `hash_equals()` validation via `requireCsrfToken()` on all POST forms & JSON APIs. | `config/session.php`, `auth/*.php`, `pages/*.php`, `api/booking_action.php` |
| **XSS Prevention** | Centralized `e()` escaping helper in PHP and DOM `escapeHtml()` in `script.js` before rendering dynamic JSON data. | `config/session.php`, `public/js/script.js` |
| **Session & Cookie Security** | Enabled `session.use_strict_mode = 1`, `session.use_only_cookies = 1`, `HttpOnly`, and `SameSite=Lax` on session & preference cookies. | `config/session.php`, `config/preferences.php` |
| **RBAC & Backdoor Removal** | Removed unauthenticated test helper `auto_auth.php` from `bookstore/pages/` (archived outside web root). Enforced server-side `requireRole(['admin', 'trainer'])` on CRUD pages and `update_status` API. | `pages/class_form.php`, `pages/delete_class.php`, `api/booking_action.php` |
| **Open Redirect & Error Hiding** | Added `isSafeLocalRedirect()` validation in `set_theme.php`, `clear_cookies.php`, `login.php`, and `header.php`. Replaced raw PDO exception leak in `class_form.php` with `error_log()` + generic message. | `config/session.php`, `pages/set_theme.php`, `pages/class_form.php` |
| **HTTP Security Headers** | Emitted `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`. | `config/session.php` |

---

## 4. SEO, Usability & Google Maps Integration (Lab 3 Tasks 5 & 6)

- **SEO Component (`bookstore/components/seo_meta.php`):** Generates per-page `<title>`, `<meta name="description">`, `<meta name="keywords">`, `<meta name="robots">` (`noindex` for private dashboard/forms, `index, follow` for catalog pages), canonical URLs, Open Graph metadata, and Schema.org JSON-LD (`ExerciseGym` and `Course`).
- **Crawlability:** Added `bookstore/public/sitemap.xml` and `bookstore/public/robots.txt`.
- **Semantic HTML & Light/Dark Accessibility:** Standardized single `<h1>` per page, semantic `<main>`, `<section>`, `<article>`, descriptive `alt` text on class images, and Light Mode adaptive contrast overrides in `bookstore/public/css/style.css`.
- **Location Integration (`#location` on `bookstore/public/index.php`):** Added flagship facility address (`268 Ly Thuong Kiet, District 10, Ho Chi Minh City`), hours, contact info, external Google Maps directions button, and responsive Google Maps `<iframe>` embed.

---

## 5. Integration & End-to-End Test Report (Lab 3 Task 7)

All 27 automated end-to-end test cases in `tests/test_lab3_e2e.php` were executed against PHP 8.2.12 + MariaDB/MySQL (`gym_fitness_db`) on `http://127.0.0.1:8080/LAB1.3_CC01_2452475/bookstore` after final edits, passing **27/27 (0 failed)**.

| Test ID | Area | Expected Result | Actual Observed Result | Status |
|---|---|---|---|---|
| `T1.1` | Public Homepage & SEO | HTTP 200 with meta description & JSON-LD `ExerciseGym` | HTTP 200, SEO meta & JSON-LD verified | **PASS** |
| `T1.2` | Google Maps Integration | Embed `<iframe>` and directions link present | Both present in `#location` | **PASS** |
| `T1.3` | Sitemap & Robots | `sitemap.xml` & `robots.txt` return HTTP 200 | Both returned HTTP 200 | **PASS** |
| `T2.1` | AJAX Search All | Returns 6 seeded classes in JSON | `Count=6` | **PASS** |
| `T2.2` | AJAX Keyword Search | Searching `"Olympic"` matches Class 3 | Matched `Olympic Weightlifting & Explosive Power` | **PASS** |
| `T2.3` | AJAX Category Filter + Cookie | Filtering `category_id=2` returns Cardio classes & sets cookie | `Count=2` & `apex_fav_category` header set | **PASS** |
| `T2.4` | AJAX Empty State | Non-matching query returns `success=true, count=0` | `Count=0` | **PASS** |
| `T2.5` | SQL Injection Probe | `' OR '1'='1` safely parameterized with 0 matches | `Count=0`, HTTP 200 | **PASS** |
| `T3.1` | AJAX Live Capacity | Class 3 tomorrow shows `4/5` booked, `1` remaining | `Booked=4/5` | **PASS** |
| `T4.1` | User Registration | Valid user created with Bcrypt hash | Registered new user & redirected with flash success | **PASS** |
| `T4.2` | Login Flow | Newly registered user logs into dashboard | Dashboard rendered `E2E Athlete` | **PASS** |
| `T5.1` | T1 Membership Gate | Booking without active membership rejected (HTTP 409) | `NO_ACTIVE_MEMBERSHIP` returned | **PASS** |
| `T5.2` | T1 Membership Activation | Activating Starter plan succeeds | Active subscription issued for 14 days | **PASS** |
| `T6.1` | T1 Book Final Spot | Member 3 books 5th spot on Class 3 (`remaining=0`) | Booking `#11` confirmed, `Remaining=0` | **PASS** |
| `T6.2` | T1 Duplicate Prevention | Booking same class & date twice rejected (HTTP 409) | `DUPLICATE_BOOKING` returned | **PASS** |
| `T6.3` | T1 Capacity Enforcement | Member 4 booking full Class 3 blocked (HTTP 409) | `CAPACITY_FULL` (`5/5 spots filled`) | **PASS** |
| `T6.4` | XSS Output Escaping | `<script>alert("xss")</script>` rendered escaped | Encoded as `&lt;script&gt;...` | **PASS** |
| `T6.5` | AJAX Cancel Booking | Canceling booking restores Class 3 to `4/5` (`remaining=1`) | `Remaining=1` | **PASS** |
| `T7.1` | RBAC Page Protection | Member accessing `class_form.php` gets HTTP 403 | HTTP 403 Forbidden | **PASS** |
| `T7.2` | RBAC API Protection | Member calling `update_status` gets HTTP 403 | HTTP 403 JSON rejected | **PASS** |
| `T7.3` | CSRF Protection | POST without valid CSRF token rejected (HTTP 403) | HTTP 403 `INVALID_CSRF` | **PASS** |
| `T7.4` | Open Redirect Guard | External `return=https://evil.example.com` rejected | Did not redirect externally | **PASS** |
| `T7.5` | Backdoor Removal | Removed `auto_auth.php` returns HTTP 404 | HTTP 404 | **PASS** |
| `T8.1` | Admin CRUD Create | Admin creates new class with CSRF | Created Class ID=7 | **PASS** |
| `T8.2` | Admin CRUD Update | Admin updates Class ID=7 | Updated title visible | **PASS** |
| `T8.3` | Admin CRUD Delete | Admin deletes Class ID=7 via POST + CSRF | Deleted with success flash | **PASS** |
| `T8.4` | Logout Session Invalidation | After logout, `dashboard.php` redirects to login | HTTP 302 -> `login.php` | **PASS** |

### Remaining Limitations
- Automated browser UI driving via Chrome DevTools could not connect in this session because Google Chrome remote debugging (`chrome://inspect/#remote-debugging`) was not active; interactive client DOM updates and responsive viewports should be visually inspected in a local browser.
- On the static Netlify mirror (`netlify_dist/`), server-side PHP/MySQL transactions (`booking_action.php`, `class_capacity.php`) are represented as static previews, whereas full live transactional behavior runs on XAMPP Apache/PHP/MySQL.
