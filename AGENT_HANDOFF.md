# AGENT HANDOFF: TIGER FITNESS CENTER (LABS 1.3, 2 & 3 - WEB PROGRAMMING)

> **Mục đích tài liệu:** Cung cấp đầy đủ ngữ cảnh dự án, kiến trúc workspace đã chuẩn hóa, lược đồ CSDL, API AJAX, luồng nghiệp vụ T1, bảo mật và quy trình kiểm thử để bất kỳ Agent hoặc người dùng nào tiếp quản đều có thể vận hành và trình bày ngay lập tức.

---

## 1. THÔNG TIN DỰ ÁN & SINH VIÊN (METADATA)

- **Môn học:** Lập trình Web (Web Programming) - ĐH Bách Khoa TP.HCM (HCMUT)
- **Các bài Lab đã tích hợp:** Lab 1.3 (Frontend Layout) + Lab 2 (MySQL, CRUD, Auth, Session, Cookie, RBAC) + Lab 3 (AJAX, Business Logic T1, Security, SEO, Google Maps, E2E Testing)
- **Chủ đề cá nhân (Personal Topic):** **T1 — Gym & Fitness Center Website**
- **Tên thương hiệu:** **TIGER FITNESS**
- **Sinh viên thực hiện:** **Thái Khang** (`2452475` - Lớp `CC01`)
- **GitHub Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)
- **Môi trường cục bộ:** XAMPP for Windows (Apache, PHP 8.2.12, MariaDB/MySQL 10.4)
- **Đường dẫn PHP:** `C:\xampp\php\php.exe`
- **Đường dẫn MySQL:** `C:\xampp\mysql\bin\mysql.exe`

---

## 2. CẤU TRÚC WORKSPACE `C:\xampp\htdocs` ĐÃ TÁI CẤU TRÚC

```text
C:\xampp\htdocs\
├── index.php                                  # Portal điều hướng nhanh trên localhost/
├── _specs_and_docs/                           # Đề bài PDF gốc của Lab 1.3, Lab 2, Lab 3
├── _submissions_archive/                      # Kho lưu bài nộp, báo cáo LaTeX, screenshots & .zip
│   ├── Lab2_Submission/                       # Gói nộp Lab 2 hoàn chỉnh + bản sao lưu cũ
│   └── Lab3_Submission/                       # Thư mục chuẩn bị đóng gói nộp Lab 3
└── LAB1.3_CC01_2452475/                       # REPO GIT CHÍNH (Đồng bộ GitHub)
    ├── .gitignore                             # Bỏ qua zip, log, bak, scratch/
    ├── AGENT_HANDOFF.md                       # Tài liệu bàn giao xuyên suốt này
    ├── README.md                              # Tài liệu tổng quan repository trên GitHub
    ├── index.php                              # Redirect vào bookstore/public/index.php
    ├── gym_fitness_db.sql                     # SQL Schema + Seed Data (6 bảng cho Lab 2 & Lab 3)
    ├── build_netlify.php                      # Script build bản tĩnh Netlify (kèm Auth & SEO)
    ├── netlify.toml                           # Cấu hình deploy Netlify
    ├── netlify_dist/                          # Bản xuất tĩnh HTML/CSS/JS cho Netlify
    ├── docs/
    │   ├── lab2/LAB2_FINAL_REPORT.md
    │   ├── lab3/LAB3_TECHNICAL_SUMMARY_AND_TEST_REPORT.md
    │   └── screenshots/lab1_3/
    ├── tests/
    │   └── test_lab3_e2e.php                  # Bộ 27 test case E2E tự động (Lab 3 Task 7)
    └── bookstore/                             # ỨNG DỤNG WEB CHÍNH (PHP/MySQL)
        ├── DESIGN.md                          # Design Tokens (Tiger Orange Theme)
        ├── api/                               # [LAB 3] AJAX JSON Endpoints
        │   ├── search_classes.php             # Task 1: Live Search & Multi-Filter JSON
        │   ├── class_capacity.php             # Task 2: Live Class Capacity & 4-day Forecast
        │   └── booking_action.php             # Task 2 & 3: Book / Cancel / Status / Membership
        ├── auth/
        │   ├── login.php                      # Đăng nhập (Bcrypt + CSRF + Safe Redirect)
        │   ├── register.php                   # Đăng ký (Server Validation + CSRF + Bcrypt)
        │   └── logout.php                     # Hủy phiên hoàn toàn
        ├── components/
        │   ├── header.php                     # Navbar kính mờ, Logo Hổ, Theme Switcher, Links
        │   ├── footer.php                     # Footer kèm link nhanh, sitemap, attribution
        │   └── seo_meta.php                   # [LAB 3] Dynamic SEO Title/Meta/OG/JSON-LD + CSRF Meta
        ├── config/
        │   ├── database.php                   # Kết nối PDO UTF-8mb4 (ERR_MODE_EXCEPTION)
        │   ├── session.php                    # Strict Session, CSRF Helpers, e(), RBAC Guards (403)
        │   ├── preferences.php                # Cookies (apex_theme 30d, recent_class & fav_cat 7d)
        │   └── booking_service.php            # [LAB 3] Transaction T1: Membership -> Class -> Booking
        ├── database/
        │   ├── gym_fitness_db.sql             # DDL + Seed Data mới nhất
        │   └── schema.sql                     # Bản đồng bộ cấu trúc
        ├── pages/
        │   ├── classes.php                    # Danh mục + AJAX Live Search/Filter + Staff CRUD
        │   ├── item_details.php               # Chi tiết lớp + AJAX Live Capacity & Quick Booking
        │   ├── bookings.php                   # [LAB 3] Luồng T1 Membership -> Class -> Schedule -> Booking
        │   ├── dashboard.php                  # Bento Dashboard, Membership, Bookings, Cookie Inspector
        │   ├── class_form.php                 # Thêm/Sửa lớp tập (kèm max_capacity, RBAC + CSRF)
        │   ├── delete_class.php               # Xóa lớp tập (POST + RBAC + CSRF)
        │   ├── set_theme.php                  # Đổi theme cookie (kèm chống Open Redirect)
        │   └── clear_cookies.php              # Xóa cookie tùy chọn
        └── public/
            ├── index.php                      # Trang chủ + Google Maps Embed (#location) + SEO
            ├── sitemap.xml                    # XML Sitemap cho công cụ tìm kiếm
            ├── robots.txt                     # Quy tắc crawler
            ├── css/style.css                  # Tiger Orange Palette, Light/Dark Contrast, Capacity Bar
            ├── js/script.js                   # Client AJAX Engine (Search, Capacity, Booking, escapeHtml)
            └── images/                        # item1.jpg..item6.jpg, tiger-logo.svg, favicon.svg
```

---

## 3. CƠ SỞ DỮ LIỆU & TÀI KHOẢN MẪU (`gym_fitness_db`)

### 3.1. 6 Bảng CSDL
1. `categories` — 4 danh mục bộ môn tập luyện.
2. `users` — Tài khoản xác thực (`admin`, `trainer`, `member`).
3. `fitness_classes` — Các lớp tập (đã bổ sung `max_capacity`). Lớp #3 (*Olympic Weightlifting*) có `max_capacity = 5`.
4. `membership_plans` — 3 gói hội viên (`STARTER` max 2 booking, `PRO` max 5 booking, `VIP` max 15 booking).
5. `user_memberships` — Theo dõi gói hội viên active/expired/cancelled của từng user.
6. `class_bookings` — Lưu lịch đặt lớp (`confirmed`, `completed`, `cancelled`), có chỉ mục `(class_id, booking_date, status)`. Seed sẵn **4/5 lượt đặt** cho Lớp #3 vào ngày mai (`CURDATE() + 1 DAY`) để kiểm thử quy tắc sức chứa.

### 3.2. Tài khoản kiểm thử sẵn có (Mật khẩu chung: `Password123!`)

| Role | Email | Password | Quyền hạn |
|---|---|---|---|
| **Admin** | `admin@gymfitness.local` | `Password123!` | Toàn quyền Class CRUD + Quản lý toàn bộ Bookings |
| **Trainer** | `trainer@gymfitness.local` | `Password123!` | Quản lý Class CRUD + Cập nhật trạng thái Bookings |
| **Member** | `member@gymfitness.local` | `Password123!` | Gói `PRO`: Xem lớp, kích hoạt gói, đặt/hủy lớp (bị chặn HTTP 403 ở CRUD) |
| **Extra Members** | `member2@..`, `member3@..`, `member4@gymfitness.local` | `Password123!` | Dùng kiểm thử chạm trần sức chứa (5/5 spots) |

---

## 4. TỔNG HỢP TÍNH NĂNG ĐÃ TRIỂN KHAI (LAB 1.3 + LAB 2 + LAB 3)

1. **AJAX Search & Filter (Lab 3 Task 1):** `pages/classes.php` gọi `api/search_classes.php` khi gõ từ khóa hoặc đổi danh mục/HLV/tình trạng còn chỗ/ngày/sắp xếp, cập nhật DOM ngay không tải lại trang.
2. **AJAX Live Capacity & Booking Action (Lab 3 Task 2):** `pages/item_details.php` và `pages/bookings.php` gọi `api/class_capacity.php` xem số chỗ trống thực tế theo ngày & dự báo 4 ngày tới, đồng thời gọi `api/booking_action.php` đặt/hủy/cập nhật trạng thái tức thì.
3. **Luồng nghiệp vụ T1 (Lab 3 Task 3):** `Membership -> Fitness Class -> Schedule -> Booking` trong `config/booking_service.php` dùng MySQL Transaction (`FOR UPDATE`) kiểm tra: (a) Active Membership, (b) Quota gói tập, (c) Chống đặt trùng ngày, (d) Chống vượt quá `max_capacity`.
4. **Nâng cấp bảo mật (Lab 3 Task 4):** Cookie `HttpOnly` + `SameSite=Lax`, `session.use_strict_mode=1`, CSRF Token trên tất cả form POST & API AJAX, Escape XSS (`e()` & `escapeHtml()`), chống Open Redirect, ẩn lỗi PDO nội bộ, và đã gỡ bỏ `auto_auth.php` khỏi web root.
5. **SEO & Google Maps (Lab 3 Tasks 5 & 6):** Component `seo_meta.php`, `sitemap.xml`, `robots.txt`, JSON-LD `ExerciseGym` & `Course`, chỉnh sửa độ tương phản Light/Dark Mode, và nhúng bản đồ Google Maps (`#location`) trên trang chủ.

---

## 5. RUNBOOK KIỂM THỬ & VẬN HÀNH

```powershell
# 1. Import lại CSDL sạch (khi cần reset dữ liệu)
Get-Content C:\xampp\htdocs\LAB1.3_CC01_2452475\gym_fitness_db.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root

# 2. Kiểm tra cú pháp toàn bộ file PHP
Get-ChildItem -Recurse -Filter *.php C:\xampp\htdocs\LAB1.3_CC01_2452475 | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }

# 3. Chạy bộ 27 bài kiểm thử E2E tự động (Lab 3 Task 7)
C:\xampp\php\php.exe tests/test_lab3_e2e.php --base-url=http://localhost/LAB1.3_CC01_2452475/bookstore

# 4. Build lại bản tĩnh Netlify (mặc định http://localhost/...)
C:\xampp\php\php.exe build_netlify.php
```
