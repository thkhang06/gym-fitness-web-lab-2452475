# BÁO CÁO NGHIỆM THU LAB 2 - TASK 4 & TASK 5.1
## MÔN HỌC: LẬP TRÌNH WEB (WEB PROGRAMMING)
**Chủ đề cá nhân (Personal Assignment Topic):** T1 — Gym & Fitness Center Website  
**Tên dự án:** Apex Fitness & Performance Center  
**Mã số sinh viên:** 2452475  
**Thư mục làm việc:** `C:\xampp\htdocs\LAB1.3_CC01_2452475`  
**GitHub Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)

---

## 1. TỔNG QUAN THỰC HIỆN PHASE 2

Trong Phase 2, dự án đã được nâng cấp toàn diện các tính năng xác thực người dùng và quản lý phiên làm việc theo đặc tả yêu cầu:
1. **Dọn dẹp cấu trúc thư mục:** Đã xóa các thư mục trùng lặp ở ngoài thư mục gốc (`auth/`, `config/`, `database/`), quy hoạch toàn bộ mã nguồn ứng dụng chạy chuẩn hóa bên trong `bookstore/`.
2. **Quản lý Session tập trung (`bookstore/config/session.php`):** Khởi tạo `session_start()` an toàn, cung cấp các hàm helper `isLoggedIn()`, `requireLogin()`, `getCurrentUser()`, và cơ chế Flash message.
3. **Đăng ký tài khoản (`bookstore/auth/register.php` - Task 4.1):** Thẩm định dữ liệu chặt chẽ ở phía máy chủ, ngăn chặn trùng lặp email, kiểm tra độ mạnh mật khẩu và băm mật khẩu bằng thuật toán chuẩn `password_hash($password, PASSWORD_DEFAULT)` (bcrypt).
4. **Đăng nhập (`bookstore/auth/login.php` - Task 4.2):** Kiểm tra thông tin đăng nhập với cơ sở dữ liệu qua Prepared Statement, đối soát băm bằng `password_verify()`, phòng chống tấn công Session Fixation bằng `session_regenerate_id(true)`, và tích hợp sẵn hộp gợi ý tài khoản mẫu tiện lợi cho việc chấm điểm.
5. **Đăng xuất (`bookstore/auth/logout.php` - Task 4.3):** Xóa toàn bộ biến session, xóa cookie phiên trên trình duyệt, hủy session trên server (`session_destroy()`), và thiết lập tiêu đề HTTP chống bộ nhớ đệm (Cache-Control) để vô hiệu hóa nút Back của trình duyệt.
6. **Bảng điều khiển được bảo vệ (`bookstore/pages/dashboard.php` - Task 4.4 & Task 5.1):** Kiểm soát truy cập nghiêm ngặt bằng `requireLogin()`, xuất trình Session ID thực tế làm minh chứng phiên làm việc, hiển thị vai trò (Role badge) và thông tin cá nhân.
7. **Thanh điều hướng thông minh (`bookstore/components/header.php`):** Tự động chuyển đổi giao diện giữa trạng thái khách vãng lai (nút Login/Register) và trạng thái thành viên đã đăng nhập (lời chào, badge chức vụ, link Dashboard, nút Log Out).

---

## 2. CẤU TRÚC THƯ MỤC CHUẨN HÓA

```
LAB1.3_CC01_2452475/
├── .gitignore                          # Cấu hình bỏ qua tệp tạm, OS, logs
├── index.php                           # Tệp điều hướng gốc đến bookstore/public/index.php
├── LAB2_TASKS_1_AND_2_REPORT.md        # Báo cáo tiến độ Tasks 1 & 2
├── LAB2_TASKS_4_AND_5_1_REPORT.md      # Báo cáo nghiệm thu Tasks 4 & 5.1
├── screenshots/                        # Ảnh chụp minh chứng từ Lab 1.3
└── bookstore/                          # ỨNG DỤNG WEB CHÍNH
    ├── auth/
    │   ├── login.php                   # Form đăng nhập & xác thực phiên
    │   ├── logout.php                  # Hủy phiên & xóa session cookie
    │   ├── register.php                # Form đăng ký & băm mật khẩu
    │   └── README.md                   # Tài liệu mô tả module Auth
    ├── components/
    │   ├── header.php                  # Thanh điều hướng nhận biết trạng thái đăng nhập
    │   └── footer.php                  # Chân trang Apex Fitness
    ├── config/
    │   ├── database.php                # Cấu hình kết nối MySQL PDO
    │   └── session.php                 # Trình quản lý session & auth helpers
    ├── database/
    │   └── schema.sql                  # CSDL gym_fitness_db & seed data
    ├── pages/
    │   ├── dashboard.php               # Bảng điều khiển bảo vệ (Task 4.4 & 5.1)
    │   └── item_details.php            # Chi tiết lớp tập động
    └── public/
        ├── index.php                   # Trang chủ hiển thị danh mục động
        ├── css/style.css               # Phong cách giao diện thể hình
        ├── js/script.js                # Tương tác JavaScript
        └── images/                     # 6 hình ảnh lớp tập chất lượng cao
```

---

## 3. CHI TIẾT KỸ THUẬT CÁC TÍNH NĂNG ĐÃ TRIỂN KHAI

### 3.1. Centralized Session Manager (`config/session.php`)
- Kiểm tra `session_status() === PHP_SESSION_NONE` trước khi gọi `session_start()`.
- Cung cấp các hàm utility:
  - `isLoggedIn(): bool`: Kiểm tra xem người dùng hiện tại có `$_SESSION['user_id']` hợp lệ hay không.
  - `getCurrentUser(): ?array`: Trả về mảng dữ liệu người dùng (`id`, `full_name`, `email`, `role`).
  - `requireLogin(string $message): void`: Bảo vệ trang riêng tư; nếu chưa đăng nhập, lưu thông báo lỗi và chuyển hướng tức thì về `auth/login.php` kèm URL gốc để hỗ trợ redirect sau khi đăng nhập.
  - `setFlash() / getFlash()`: Cơ chế truyền thông báo một lần giữa các trang web qua Session.

### 3.2. User Registration (`auth/register.php` - Task 4.1)
- **Giao diện:** Tương thích Bootstrap 5, mang màu sắc thể thao hiện đại của Apex Fitness.
- **Thẩm định dữ liệu máy chủ (Server-side Validation):**
  - Bắt buộc điền đầy đủ các trường: Full Name, Email, Password, Confirm Password.
  - Kiểm tra định dạng email bằng `filter_var($email, FILTER_VALIDATE_EMAIL)`.
  - Quy tắc độ mạnh mật khẩu: Tối thiểu 8 ký tự, phải bao gồm cả chữ cái và chữ số (`preg_match('/[A-Za-z]/')` và `preg_match('/\d/')`).
  - Kiểm tra khớp giữa `Password` và `Confirm Password`.
  - Chống trùng lặp tài khoản: Dùng Prepared Statement truy vấn bảng `users` (`SELECT id FROM users WHERE email = :email`).
- **Bảo mật mật khẩu:** Băm mật khẩu bằng hàm chuẩn `password_hash($password, PASSWORD_DEFAULT)` sinh mã băm Bcrypt mạnh mẽ, tuyệt đối không lưu văn bản thuần (plain text).
- Cho phép chọn vai trò `member` hoặc `trainer` để phục vụ công tác kiểm thử và chấm điểm của giảng viên.

### 3.3. User Authentication (`auth/login.php` - Task 4.2)
- Tiếp nhận thông tin `email` và `password`.
- Truy vấn tài khoản bằng Prepared Statement: `SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1`.
- Kiểm tra tính hợp lệ của mật khẩu bằng `password_verify($password, $user['password_hash'])`.
- **Chống Session Fixation:** Sau khi xác thực thành công, thực thi hàm `session_regenerate_id(true)` để đổi ID phiên mới, chống lại kỹ thuật đánh cắp phiên.
- Thiết lập thông tin vào `$_SESSION`: `user_id`, `full_name`, `email`, `role`, `logged_in_time`.
- Chống thu thập tài khoản (Account Enumeration): Thông báo lỗi chung `"Invalid email or password."` khi sai email hoặc mật khẩu.
- **Tiện ích chấm điểm (Demo Helper):** Tích hợp sẵn thanh công cụ 1-Click điền nhanh thông tin của 3 tài khoản mẫu (Admin, Trainer, Member) với mật khẩu `Password123!`.

### 3.4. User Logout (`auth/logout.php` - Task 4.3)
- Hủy toàn bộ biến trong `$_SESSION = []`.
- Xóa cookie phiên trình duyệt thông qua hàm `setcookie(session_name(), '', time() - 42000, ...)`.
- Hủy phiên làm việc trên máy chủ: `session_destroy()`.
- Gửi tiêu đề HTTP ngăn chặn cache trình duyệt:
  ```http
  Cache-Control: no-cache, no-store, must-revalidate
  Pragma: no-cache
  Expires: 0
  ```
- Chuyển hướng người dùng về `auth/login.php?logged_out=1`. Khi người dùng bấm nút "Back" trên trình duyệt, trình duyệt buộc phải gửi yêu cầu mới lên máy chủ và bị chuyển hướng về login, không thể truy cập lại dữ liệu bảo mật.

### 3.5. Protected Member Dashboard (`pages/dashboard.php` - Task 4.4 & Task 5.1)
- Gọi `requireLogin()` ngay dòng đầu tiên.
- Trình bày thông tin phiên:
  - Tên đầy đủ, Email, ID tài khoản.
  - Badge chức vụ hiển thị trực quan (Admin, Trainer, Member).
  - **Khối kỹ thuật Session ID:** Xuất giá trị `session_id()` thực tế từ hàm PHP để làm minh chứng xác thực cơ chế duy trì trạng thái của Session theo yêu cầu Task 5.1.
- Hiển thị bảng tổng hợp các chương trình tập luyện và liên kết nhanh đến danh mục chi tiết.

### 3.6. Dynamic Navigation Bar (`components/header.php`)
- Khi **chưa đăng nhập**: Xuất hiện 2 nút hành động `Log In` và `Register`.
- Khi **đã đăng nhập**: Hiển thị nhãn chào `Hi, [Họ tên]`, badge vai trò `[ROLE]`, liên kết điều hướng vào trang `Dashboard`, và nút `Log Out`.
- Toàn bộ đường dẫn tương đối hoạt động chuẩn xác từ mọi phân cấp thư mục.

---

## 4. KẾT QUẢ KIỂM THỬ THỰC TẾ

| Kịch bản kiểm thử | Hành vi kỳ vọng | Kết quả thực tế | Trạng thái |
| :--- | :--- | :--- | :--- |
| **1. Cú pháp PHP** | Kiểm tra `php -l` cho toàn bộ 11 file PHP trong dự án | Không phát hiện bất kỳ lỗi cú pháp nào | **PASS** |
| **2. Truy cập trái phép Dashboard** | Gọi trực tiếp `GET /pages/dashboard.php` khi chưa đăng nhập | Trả về `HTTP 302 Found`, chuyển hướng về `../auth/login.php` | **PASS** |
| **3. Kiểm tra dữ liệu đăng ký** | Gửi mật khẩu ngắn (< 8 ký tự) hoặc xác nhận mật khẩu không khớp | Hiển thị thông báo lỗi thẩm định tương ứng | **PASS** |
| **4. Ngăn chặn trùng Email** | Đăng ký tài khoản với email đã tồn tại trong database | Báo lỗi: *"An account with this email address already exists."* | **PASS** |
| **5. Đăng ký thành công** | Đăng ký tài khoản mới `daniel.craig@example.com` | Bản ghi được ghi nhận vào bảng `users`, mã hóa Bcrypt, chuyển hướng sang `login.php` kèm flash thông báo | **PASS** |
| **6. Đăng nhập sai mật khẩu** | Nhập mật khẩu không chính xác | Báo lỗi tổng quát: *"Invalid email or password."* | **PASS** |
| **7. Đăng nhập thành công** | Nhập đúng email & mật khẩu | `session_regenerate_id(true)` kích hoạt, trả về mã cookie mới và chuyển hướng vào `pages/dashboard.php` | **PASS** |
| **8. Bền vững Session (Task 5.1)** | Chuyển qua lại giữa `public/index.php` và `pages/dashboard.php` | Phiên làm việc duy trì liên tục, Header hiển thị tên và badge của tài khoản đang đăng nhập | **PASS** |
| **9. Đăng xuất hoàn toàn** | Thực hiện đăng xuất qua `/auth/logout.php` | Session bị hủy, cookie bị xóa, truy cập lại `dashboard.php` bị chặn hoàn toàn | **PASS** |

---

## 5. DANH SÁCH TÀI KHOẢN MẪU DÙNG THỬ (DEMO ACCOUNTS)

| Vai trò (Role) | Họ và tên | Email đăng nhập | Mật khẩu mặc định |
| :--- | :--- | :--- | :--- |
| **Administrator** | Marcus Vance | `admin@gymfitness.local` | `Password123!` |
| **Trainer / Coach** | Elena Rostova | `trainer@gymfitness.local` | `Password123!` |
| **Club Member** | Alex Nguyen | `member@gymfitness.local` | `Password123!` |
| **Newly Created Member** | Daniel Craig | `daniel.craig@example.com` | `SecurePass2026!` |

---

## 6. ĐƯỜNG DẪN TRUY CẬP TRỰC TIẾP TRÊN LOCALHOST (XAMPP)

1. **Trang chủ Website:**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php](http://localhost/LAB1.3_CC01_2452475/bookstore/public/index.php)  
   *(Hoặc truy cập URL gốc tự động chuyển hướng: [http://localhost/LAB1.3_CC01_2452475/](http://localhost/LAB1.3_CC01_2452475/))*

2. **Trang Đăng nhập (Auth Login):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/auth/login.php](http://localhost/LAB1.3_CC01_2452475/bookstore/auth/login.php)

3. **Trang Đăng ký thành viên mới (Auth Register):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/auth/register.php](http://localhost/LAB1.3_CC01_2452475/bookstore/auth/register.php)

4. **Trang Bảng điều khiển bảo vệ (Dashboard - Task 4.4 & Task 5.1):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php)  
   *(Thử mở link này trong tab ẩn danh để quan sát tính năng tự động chặn và chuyển hướng về trang đăng nhập)*

5. **Trang Đăng xuất:**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/auth/logout.php](http://localhost/LAB1.3_CC01_2452475/bookstore/auth/logout.php)
