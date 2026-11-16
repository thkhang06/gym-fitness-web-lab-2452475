# TIGER FITNESS Center — Web Programming Semester Project (Labs 1.3, 2 & 3)

- **Student:** Thái Khang (`2452475`) — Class `CC01` (HCMUT)
- **Personal Topic:** **T1 — Gym & Fitness Center Website**
- **Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)

## Key Features Implemented

### Lab 1.3 — Frontend & Responsive Layout
- Semantic HTML5 layout (`header`, `nav`, `main`, `section`, `article`, `footer`)
- Responsive Bootstrap 5 grid (`col-12 col-md-6 col-lg-4`) and custom Tiger Orange design system (`bookstore/public/css/style.css`, `bookstore/DESIGN.md`)
- Reusable header/footer components (`bookstore/components/`) and SVG mascot identity (`tiger-logo.svg`, `favicon.svg`)

### Lab 2 — Database-Driven Web App, CRUD, Auth, Sessions, Cookies & RBAC
- **MySQL + PDO Prepared Statements** (`bookstore/config/database.php`, `gym_fitness_db.sql`)
- **Full CRUD** for `fitness_classes` (`pages/classes.php`, `pages/item_details.php`, `pages/class_form.php`, `pages/delete_class.php`)
- **Authentication & Sessions** (`auth/register.php`, `auth/login.php`, `auth/logout.php`) with Bcrypt password hashing & session regeneration
- **Client Preference Cookies** (`config/preferences.php`): `apex_theme` (30 days), `apex_recent_class` & `apex_fav_category` (7 days)
- **Server-Side RBAC** (`admin`, `trainer`, `member`) returning HTTP `403 Forbidden` on unauthorized access

### Lab 3 — AJAX, Business Workflow (T1), Security, SEO & Location Integration
- **AJAX Feature 1 — Live Search & Multi-Filter (`api/search_classes.php`):** Real-time filtering by keyword, discipline category, coach, availability, date, and sort order without full page reload.
- **AJAX Feature 2 — Live Class Capacity & Quick Booking (`api/class_capacity.php`, `api/booking_action.php`):** Live spot count, 4-day availability forecast, capacity progress bar, and instant reservation/cancellation.
- **T1 Mandatory Business Workflow (`pages/bookings.php` + `config/booking_service.php`):**
  `Membership -> Fitness Class -> Schedule -> Booking`
  Enforces active membership tier (`STARTER`, `PRO`, `VIP`), concurrency booking quotas, duplicate prevention, and transactional row-locked (`FOR UPDATE`) capacity limits (`booked_count < max_capacity`).
- **Security Hardening (`config/session.php`):** Strict session mode, `HttpOnly` + `SameSite=Lax` cookies, per-session CSRF token validation across all POST forms and AJAX endpoints, XSS output escaping (`e()`, `escapeHtml()`), open-redirect protection, and removal of unauthenticated test helpers.
- **SEO & Location Integration (`components/seo_meta.php`, `public/sitemap.xml`, `public/robots.txt`):** Dynamic page titles, meta descriptions, Open Graph tags, Schema.org JSON-LD (`ExerciseGym` & `Course`), and embedded Google Maps location widget (`#location`).

## Project Structure

```text
LAB1.3_CC01_2452475/
├── AGENT_HANDOFF.md         # Full context handoff for Labs 1.3, 2 & 3
├── README.md
├── build_netlify.php        # Static bundle exporter for Netlify
├── netlify.toml             # Netlify deployment configuration
├── netlify_dist/            # Exported static HTML/CSS/JS build
├── gym_fitness_db.sql       # Complete MySQL schema & seed data (6 tables)
├── docs/                    # Technical reports & Lab 1.3 screenshots
├── tests/                   # End-to-End automated verification suite
└── bookstore/               # Main PHP/MySQL Web Application
    ├── api/                 # AJAX JSON endpoints
    ├── auth/                # Register, Login, Logout
    ├── components/          # Header, Footer, SEO Meta
    ├── config/              # Database, Session/Security, Preferences, Booking Service
    ├── database/            # SQL schema backups
    ├── pages/               # Classes, Details, Bookings, Dashboard, Class CRUD
    └── public/              # Web entry (index.php), CSS, JS, Images, Sitemap, Robots
```

## Quick Setup (XAMPP)
1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Import `gym_fitness_db.sql` into MySQL / phpMyAdmin (`gym_fitness_db`).
3. Visit `http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php`.
4. Run automated E2E verification tests:
   ```powershell
   C:\xampp\php\php.exe tests/test_lab3_e2e.php
   ```

## Demo Accounts (Password: `Password123!`)
| Role | Email | Privileges |
|---|---|---|
| **Admin** | `admin@gymfitness.local` | Full class CRUD, staff booking status management, dashboard |
| **Trainer** | `trainer@gymfitness.local` | Create/Edit/Delete classes, staff booking status management |
| **Member** | `member@gymfitness.local` | Activate membership, AJAX capacity check, book/cancel classes |
