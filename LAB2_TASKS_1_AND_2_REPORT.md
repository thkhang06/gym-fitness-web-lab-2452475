# BÁO CÁO TIẾN ĐỘ LAB 2 - TASKS 1 & 2
## MÔN HỌC: LẬP TRÌNH WEB (WEB PROGRAMMING)
**Chủ đề đề tài (Personal Assignment Topic):** T1 — Gym & Fitness Center Website  
**Tên dự án:** Apex Fitness & Performance Center  
**Mã số sinh viên:** 2452475  
**Thư mục làm việc:** `C:\xampp\htdocs\LAB1.3_CC01_2452475`  
**GitHub Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)

---

## 1. TỔNG QUAN THỰC HIỆN

### PHASE 0: Khởi tạo Git & Đẩy nền tảng Lab 1.3 lên GitHub
- **Kiểm tra thư mục:** Rà soát cấu trúc mã nguồn Lab 1.3 và ảnh chụp minh chứng (`screenshots/`).
- **File `.gitignore`:** Tạo file `.gitignore` tiêu chuẩn cho dự án PHP/XAMPP (loại bỏ `.DS_Store`, `Thumbs.db`, `.vscode/`, `.idea/`, logs, và thư mục tạm).
- **Khởi tạo Git & Commit 1:**
  - Lệnh: `git init` & `git branch -M main`
  - Commit Message: `chore: initial commit from Lab 1.3 frontend foundation` (Commit ID: `66e1bb5`)
- **Tạo Repo GitHub & Push:**
  - Cài đặt & cấu hình GitHub CLI (`gh`).
  - Tạo Public Repository `gym-fitness-web-lab-2452475` trên tài khoản `thkhang06`.
  - Push nhánh `main` thành công lên `origin/main`.

### PHASE 1: Nâng cấp Lab 2 (Task 1 & Task 2)
- **Task 1: Cấu trúc thư mục & Thiết kế CSDL (Project Structure & Database Setup)**
  - Mở rộng cấu trúc module: bổ sung `config/database.php`, `database/schema.sql`, và thư mục placeholder `auth/`.
  - Khởi tạo cơ sở dữ liệu `gym_fitness_db` (bảng mã `utf8mb4_unicode_ci`) trên MySQL XAMPP.
  - Thiết kế 3 bảng quan hệ chặt chẽ: `users`, `categories`, `fitness_classes`.
  - Nạp dữ liệu mẫu (Seed Data): 4 categories, 3 tài khoản người dùng mẫu mã hóa bcrypt (`Password123!`), 6 lớp tập thể hình & fitness chuyên nghiệp.
  - Thực thi thành công script SQL vào máy chủ MariaDB/MySQL cục bộ của XAMPP.
- **Task 2: Tích hợp PHP & MySQL hiển thị động (PHP & MySQL Integration)**
  - Thiết lập kết nối PDO an toàn trong `config/database.php` (bật Exception mode, Fetch Assoc, log lỗi hệ thống, giao diện thông báo thân thiện).
  - Cập nhật trang chủ `public/index.php`: Truy vấn động `fitness_classes` JOIN `categories`, render 6 thẻ bài tập Gym phong cách hiện đại kèm badge phân loại, thông tin huấn luyện viên, lịch tập, đơn giá và link chi tiết.
  - Cập nhật trang chi tiết `pages/item_details.php`: Nhận tham số `$_GET['id']`, kiểm tra hợp lệ, sử dụng **PDO Prepared Statement** truy vấn an toàn, có khối giao diện xử lý trường hợp ID không tồn tại hoặc không hợp lệ.
  - Tối ưu mỹ thuật: Thay thế ảnh sách mẫu của Lab 1.3 bằng 6 ảnh tập gym/fitness độ phân giải cao và cập nhật giao diện `style.css` chuẩn phòng tập chuyên nghiệp.
- **Kiểm tra & Commit 2:**
  - Đã kiểm tra cú pháp toàn bộ file PHP bằng `php -l` (0 lỗi syntax).
  - Đã kiểm tra phản hồi web cục bộ qua Apache (HTTP 200 OK).
  - Commit Message: `feat(db): setup MySQL schema and dynamic database integration (Tasks 1 & 2)` (Commit ID: `8ce938a`).
  - Đã push đầy đủ lên GitHub.

---

## 2. CẤU TRÚC THƯ MỤC DỰ ÁN (PROJECT STRUCTURE)

```
LAB1.3_CC01_2452475/
├── .gitignore                      # Cấu hình bỏ qua file rác OS, editor, logs
├── index.php                       # Điều hướng tự động về bookstore/public/index.php
├── LAB2_TASKS_1_AND_2_REPORT.md    # Báo cáo chi tiết nghiệm thu Lab 2 (Tasks 1 & 2)
├── auth/                           # Thư mục chuẩn bị cho Tasks 4-6 (Auth/RBAC)
│   └── README.md
├── config/                         # Cấu hình kết nối cơ sở dữ liệu
│   └── database.php
├── database/                       # Kịch bản DDL và DML CSDL
│   └── schema.sql
├── screenshots/                    # Ảnh minh chứng nộp bài từ Lab 1.3
│   ├── 1_folder_structure.png
│   ├── 2_homepage_desktop.png
│   ├── 3_homepage_mobile.png
│   ├── 4_item_details.png
│   └── 5_javascript_interaction.png
└── bookstore/                      # Không gian ứng dụng chính kế thừa từ Lab 1.3
    ├── auth/
    │   └── README.md
    ├── components/
    │   ├── header.php              # Header & Thanh điều hướng Apex Fitness
    │   └── footer.php              # Footer bản quyền Apex Fitness
    ├── config/
    │   └── database.php            # Kết nối PDO MySQL an toàn
    ├── database/
    │   └── schema.sql              # Schema tạo bảng & dữ liệu mẫu
    ├── pages/
    │   └── item_details.php        # Trang chi tiết lớp tập động (Prepared Statement)
    └── public/
        ├── index.php               # Trang chủ hiển thị danh sách lớp tập động
        ├── css/
        │   └── style.css           # Bảng màu, typography và hiệu ứng hover gym
        ├── js/
        │   └── script.js           # Xử lý tương tác đặt lịch lớp tập
        └── images/
            ├── item1.jpg           # Lớp Power Barbell Mastery
            ├── item2.jpg           # Lớp HIIT Metabolic Inferno
            ├── item3.jpg           # Lớp Olympic Weightlifting
            ├── item4.jpg           # Lớp Athletic Stamina & Endurance
            ├── item5.jpg           # Lớp Dynamic Yoga & Core Flow
            └── item6.jpg           # Lớp Boxing Fundamentals & Conditioning
```

---

## 3. THIẾT KẾ CƠ SỞ DỮ LIỆU (`gym_fitness_db`)

### 3.1. Bảng `categories` (Bảng phân loại bổ trợ)
| Tên cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | PRIMARY KEY, AUTO_INCREMENT | Mã định danh danh mục |
| `name` | `VARCHAR(100)` | NOT NULL | Tên danh mục tập luyện |
| `description` | `TEXT` | NULL | Mô tả chi tiết định hướng tập luyện |

### 3.2. Bảng `users` (Bảng tài khoản & Phân quyền)
| Tên cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | PRIMARY KEY, AUTO_INCREMENT | Mã định danh người dùng |
| `full_name` | `VARCHAR(100)` | NOT NULL | Họ và tên đầy đủ |
| `email` | `VARCHAR(150)` | NOT NULL, UNIQUE | Địa chỉ email đăng nhập |
| `password_hash` | `VARCHAR(255)` | NOT NULL | Mật khẩu băm chuẩn bcrypt |
| `role` | `ENUM('admin','trainer','member')` | NOT NULL, DEFAULT 'member' | Vai trò người dùng |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Thời điểm tạo tài khoản |

### 3.3. Bảng `fitness_classes` (Thực thể nghiệp vụ chính)
| Tên cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | PRIMARY KEY, AUTO_INCREMENT | Mã lớp học |
| `category_id` | `INT` | NOT NULL, FK -> `categories(id)` | Khóa ngoại tham chiếu danh mục |
| `title` | `VARCHAR(150)` | NOT NULL | Tên lớp huấn luyện |
| `description` | `TEXT` | NOT NULL | Mô tả nội dung bài tập |
| `trainer_name` | `VARCHAR(100)` | NOT NULL | Tên huấn luyện viên phụ trách |
| `schedule_info` | `VARCHAR(100)` | NOT NULL | Lịch học trong tuần và khung giờ |
| `price` | `DECIMAL(10,2)` | NOT NULL, DEFAULT 0.00 | Giá vé một buổi (USD) |
| `image_url` | `VARCHAR(255)` | NULL | Đường dẫn file ảnh đại diện |
| `created_at` | `TIMESTAMP` | DEFAULT CURRENT_TIMESTAMP | Thời điểm tạo bản ghi |

*Ràng buộc toàn vẹn:* `CONSTRAINT fk_class_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE ON UPDATE CASCADE`.

### 3.4. Dữ liệu mẫu (Seed Data)
- **Danh mục:**
  1. *Strength & Hypertrophy*
  2. *Cardio & Conditioning*
  3. *Mobility & Core*
  4. *Combat & Martial Arts*
- **Tài khoản mẫu (Mật khẩu đăng nhập mặc định: `Password123!`):**
  - **Admin:** `admin@gymfitness.local` (Marcus Vance - Head Trainer & Admin)
  - **Trainer:** `trainer@gymfitness.local` (Elena Rostova - Senior Coach)
  - **Member:** `member@gymfitness.local` (Alex Nguyen - Club Member)
- **Lớp học (6 lớp chính):**
  1. *Power Barbell Mastery* ($35.00 / session)
  2. *HIIT Metabolic Inferno* ($25.00 / session)
  3. *Olympic Weightlifting & Explosive Power* ($40.00 / session)
  4. *Athletic Stamina & Endurance Circuit* ($28.00 / session)
  5. *Dynamic Yoga & Core Flow* ($22.00 / session)
  6. *Boxing Fundamentals & Conditioning* ($30.00 / session)

---

## 4. CHI TIẾT TÍCH HỢP MÃ NGUỒN PHP & MYSQL

### 4.1. File `config/database.php`
- Sử dụng chuẩn `PDO` với DSN: `mysql:host=localhost;dbname=gym_fitness_db;charset=utf8mb4`.
- Bật cấu hình bảo mật và xử lý lỗi:
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`: Bắt ngoại lệ khi có lỗi truy vấn.
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`: Trả về mảng kết hợp theo tên cột.
  - `PDO::ATTR_EMULATE_PREPARES => false`: Đảm bảo prepared statements được thực thi native trên DB engine.
- Bọc toàn bộ trong khối `try...catch (PDOException $e)`:
  - Ghi chi tiết lỗi nội bộ vào log server qua hàm `error_log()`.
  - Phản hồi mã lỗi `HTTP 500` và hiển thị trang cảnh báo thân thiện, tuyệt đối không in chuỗi kết nối, tên đăng nhập hay mật khẩu ra trình duyệt.

### 4.2. Trang chủ `public/index.php`
- Gọi cấu hình kết nối: `require_once __DIR__ . '/../config/database.php'`.
- Thực hiện câu truy vấn tổng hợp:
  ```sql
  SELECT 
      fc.id, fc.title, fc.description, fc.trainer_name, 
      fc.schedule_info, fc.price, fc.image_url, 
      c.name AS category_name
  FROM fitness_classes fc
  INNER JOIN categories c ON fc.category_id = c.id
  ORDER BY fc.id ASC
  ```
- Duyệt vòng lặp `foreach ($classes as $class)` để xuất thẻ bài tập:
  - Hiển thị badge danh mục lấy từ bảng `categories`.
  - Hiển thị tên huấn luyện viên, lịch học và đơn giá đã định dạng tiền tệ.
  - Nút "View Details" chứa URL động: `../pages/item_details.php?id=<?= $class['id'] ?>`.
  - Mọi dữ liệu in ra màn hình đều qua hàm `htmlspecialchars()` chống tấn công XSS.

### 4.3. Trang chi tiết `pages/item_details.php`
- Lấy tham số ID: `$classId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT)`.
- Thực hiện Prepared Statement chống SQL Injection:
  ```php
  $stmt = $pdo->prepare("
      SELECT fc.*, c.name AS category_name, c.description AS category_description
      FROM fitness_classes fc
      INNER JOIN categories c ON fc.category_id = c.id
      WHERE fc.id = :id
      LIMIT 1
  ");
  $stmt->execute(['id' => $classId]);
  $fitnessClass = $stmt->fetch();
  ```
- Kiểm tra trạng thái: Nếu `id` không hợp lệ hoặc lớp không tồn tại, hiển thị thông báo "Class Not Available" kèm nút quay về danh sách trang chủ.

---

## 5. KẾT QUẢ KIỂM THỬ (TESTING & VERIFICATION)

### 5.1. Kiểm tra cú pháp PHP (PHP Lint)
```powershell
php -l bookstore/config/database.php   # No syntax errors detected
php -l bookstore/components/header.php # No syntax errors detected
php -l bookstore/components/footer.php # No syntax errors detected
php -l bookstore/public/index.php      # No syntax errors detected
php -l bookstore/pages/item_details.php# No syntax errors detected
```
**Kết quả:** 100% file không có lỗi cú pháp.

### 5.2. Kiểm tra phản hồi HTTP qua Apache
- `GET http://localhost/LAB1.3_CC01_2452475/` -> `HTTP 302 Found` (Chuyển hướng đến `bookstore/public/index.php`).
- `GET http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php` -> `HTTP 200 OK`.
- `GET http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=1` -> `HTTP 200 OK`.
- `GET http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=999` -> `HTTP 200 OK` (Hiển thị giao diện báo lớp không tồn tại hợp lệ).

### 5.3. Lịch sử Git Commits
```
* 8ce938a (HEAD -> main, origin/main) feat(db): setup MySQL schema and dynamic database integration (Tasks 1 & 2)
* 66e1bb5 chore: initial commit from Lab 1.3 frontend foundation
```

---

## 6. ĐƯỜNG DẪN TRUY CẬP KIỂM TRA (LOCAL URLS)

Để kiểm tra trực tiếp trên trình duyệt, hãy đảm bảo Apache và MySQL trong XAMPP Control Panel đang ở trạng thái **Running**:

1. **Trang chủ danh sách lớp học (Homepage - Dynamic Catalog):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php](http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php)  
   *(Hoặc truy cập trực tiếp link gốc tự động chuyển hướng: [http://localhost/LAB1.3_CC01_2452475/](http://localhost/LAB1.3_CC01_2452475/))*

2. **Trang xem chi tiết một lớp tập (Item Details - ID = 1: Power Barbell Mastery):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=1](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=1)

3. **Trang xem chi tiết lớp tập khác (ID = 2: HIIT Metabolic Inferno):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=2](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=2)

4. **Kiểm tra xử lý lỗi khi nhập ID không tồn tại (ID = 999):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=999](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=999)

5. **Xem cơ sở dữ liệu trên phpMyAdmin:**  
   [http://localhost/phpmyadmin/index.php?route=/database/structure&db=gym_fitness_db](http://localhost/phpmyadmin/index.php?route=/database/structure&db=gym_fitness_db)
