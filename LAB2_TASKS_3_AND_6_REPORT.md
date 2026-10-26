# BÁO CÁO NGHIỆM THU LAB 2 - TASK 3 & TASK 6
## MÔN HỌC: LẬP TRÌNH WEB (WEB PROGRAMMING)
**Chủ đề cá nhân (Personal Assignment Topic):** T1 — Gym & Fitness Center Website  
**Tên dự án:** Apex Fitness & Performance Center  
**Mã số sinh viên:** 2452475  
**Thư mục làm việc:** `C:\xampp\htdocs\LAB1.3_CC01_2452475`  
**GitHub Repository:** [https://github.com/thkhang06/gym-fitness-web-lab-2452475](https://github.com/thkhang06/gym-fitness-web-lab-2452475)

---

## 1. TỔNG QUAN THỰC HIỆN PHASE 3

Trong Phase 3, dự án đã triển khai đầy đủ các thao tác CRUD quản lý thực thể nghiệp vụ chính (`fitness_classes`) và hệ thống phân quyền theo vai trò (Role-Based Access Control - RBAC) ở phía máy chủ:

1. **Phân quyền Server-side RBAC ([`bookstore/config/session.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/config/session.php) - Task 6):**
   - Bổ sung hàm kiểm tra vai trò `hasRole(array|string $roles): bool`.
   - Bổ sung hàm chặn quyền nghiêm ngặt `requireRole(array|string $roles): void`. Nếu người dùng không đủ quyền hạn, Server lập tức trả về mã trạng thái **HTTP 403 Forbidden** và xuất trình trang giao diện **Access Denied** có giải thích rõ quyền hạn tài khoản, ngăn chặn triệt để việc thực thi logic bên dưới.
2. **Trang Danh mục & Quản lý lớp tập ([`bookstore/pages/classes.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/classes.php) - Read & Delete, Task 3):**
   - Hỗ trợ công cụ tìm kiếm theo từ khóa (tên lớp, huấn luyện viên, mô tả) và lọc động theo danh mục bộ môn (`category_id`).
   - Mọi người dùng (kể cả khách vãng lai và `member`) đều được quyền xem danh mục và bấm `View Details`.
   - **Chỉ hiển thị nút "+ Add New Class", "Edit" và "Delete" đối với tài khoản `admin` và `trainer`**.
3. **Thao tác Xóa an toàn ([`bookstore/pages/delete_class.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/delete_class.php) - Delete, Task 3):**
   - Bắt buộc kiểm tra quyền hạn `requireRole(['admin', 'trainer'])`.
   - Chỉ chấp nhận phương thức `POST` và có hộp thoại JavaScript xác nhận chủ đích (`confirm()`).
   - Xóa dữ liệu an toàn bằng **PDO Prepared Statement** (`DELETE FROM fitness_classes WHERE id = :id`) và thông báo qua Flash message.
4. **Form Thêm mới & Cập nhật ([`bookstore/pages/class_form.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/class_form.php) - Create & Update, Task 3):**
   - Bảo vệ tuyệt đối bằng `requireRole(['admin', 'trainer'])` ngay dòng đầu file.
   - Form Bootstrap 5 đầy đủ các trường: `title`, `category_id`, `trainer_name`, `schedule_info`, `price`, `image_url` (kèm bộ xem trước ảnh Banner trực quan), `description`.
   - Kiểm tra thẩm định dữ liệu phía máy chủ (Server-side validation), duy trì dữ liệu khi có lỗi nhập liệu (Sticky Form).
   - Thực thi `INSERT` khi tạo mới và `UPDATE` khi chỉnh sửa qua Prepared Statements.
5. **Đồng bộ hóa giao diện đa tầng:**
   - **Header:** Bổ sung liên kết `Training Classes` và nút tắt `+ Add Class` cho nhân sự (`admin`/`trainer`).
   - **Trang Chi tiết ([`item_details.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/item_details.php)):** Tự động hiển thị thêm cụm quản trị viên `Edit Class` và `Delete Class` cho Staff.
   - **Dashboard ([`dashboard.php`](file:///C:/xampp/htdocs/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php)):** Tách biệt giao diện rõ rệt theo Role; tài khoản `member` được trang bị nút tiện ích 1-Click *"Test RBAC Security"* để giảng viên kiểm chứng khả năng chặn HTTP 403 của hệ thống.

---

## 2. MA TRẬN PHÂN QUYỀN HỆ THỐNG (RBAC PERMISSION MATRIX)

| Hành động / Tài nguyên | Khách (Guest) | Hội viên (Member) | Huấn luyện viên (Trainer) | Quản trị viên (Admin) |
| :--- | :---: | :---: | :---: | :---: |
| **Xem trang chủ & danh mục lớp tập (`Read`)** | Cho phép | Cho phép | Cho phép | Cho phép |
| **Xem chi tiết lớp tập (`Read Details`)** | Cho phép | Cho phép | Cho phép | Cho phép |
| **Đăng ký / Đăng nhập / Đăng xuất** | Cho phép | Cho phép | Cho phép | Cho phép |
| **Xem Dashboard cá nhân** | Chặn (-> Login) | Cho phép | Cho phép | Cho phép |
| **Tạo lớp tập mới (`Create` - `class_form.php`)** | Chặn (-> Login) | **Chặn (HTTP 403)** | Cho phép | Cho phép |
| **Chỉnh sửa lớp tập (`Update` - `class_form.php?id=`)** | Chặn (-> Login) | **Chặn (HTTP 403)** | Cho phép | Cho phép |
| **Xóa lớp tập (`Delete` - `delete_class.php`)** | Chặn (-> Login) | **Chặn (HTTP 403)** | Cho phép | Cho phép |

---

## 3. THIẾT KẾ & XỬ LÝ CÁC THAO TÁC CRUD (TASK 3)

### 3.1. Thao tác Create (Thêm lớp tập mới)
- **Tệp xử lý:** `bookstore/pages/class_form.php`
- **Quy trình:**
  1. Kiểm tra xác thực & vai trò: `requireRole(['admin', 'trainer'])`.
  2. Gợi ý sẵn tên huấn luyện viên đang đăng nhập vào trường `trainer_name`.
  3. Kiểm tra dữ liệu: tiêu đề tối thiểu 3 ký tự, danh mục hợp lệ trong CSDL, đơn giá `>= 0`, mô tả tối thiểu 10 ký tự.
  4. Thực thi Prepared Statement:
     ```sql
     INSERT INTO fitness_classes 
         (category_id, title, description, trainer_name, schedule_info, price, image_url, created_at)
     VALUES 
         (:category_id, :title, :description, :trainer_name, :schedule_info, :price, :image_url, NOW())
     ```
  5. Chuyển hướng về `classes.php` kèm Flash message thành công.

### 3.2. Thao tác Read (Xem danh sách & Lọc dữ liệu)
- **Tệp xử lý:** `bookstore/pages/classes.php` & `bookstore/pages/item_details.php`
- **Quy trình:**
  1. Đọc tham số `?search=...` và `?category_id=...`.
  2. Sử dụng câu lệnh có điều kiện an toàn:
     ```sql
     SELECT fc.*, c.name AS category_name 
     FROM fitness_classes fc 
     INNER JOIN categories c ON fc.category_id = c.id 
     WHERE 1=1 
       AND (fc.title LIKE :search OR fc.description LIKE :search OR fc.trainer_name LIKE :search)
       AND fc.category_id = :category_id
     ORDER BY fc.id DESC
     ```
  3. Kết xuất thẻ bài tập responsive, hiển thị badge danh mục, giá vé, lịch tập, và các nút thao tác theo quyền của người xem.

### 3.3. Thao tác Update (Cập nhật thông tin lớp tập)
- **Tệp xử lý:** `bookstore/pages/class_form.php?id=...`
- **Quy trình:**
  1. Kiểm tra quyền hạn `requireRole(['admin', 'trainer'])`.
  2. Truy vấn dữ liệu hiện tại của lớp tập theo ID và nạp vào form (Sticky Form).
  3. Khi gửi form (POST), tiến hành thẩm định lại toàn bộ dữ liệu.
  4. Cập nhật vào CSDL qua Prepared Statement:
     ```sql
     UPDATE fitness_classes SET
         category_id = :category_id, title = :title, description = :description,
         trainer_name = :trainer_name, schedule_info = :schedule_info,
         price = :price, image_url = :image_url
     WHERE id = :id
     ```
  5. Thiết lập thông báo thành công và chuyển hướng về danh sách lớp học.

### 3.4. Thao tác Delete (Xóa lớp tập)
- **Tệp xử lý:** `bookstore/pages/delete_class.php`
- **Quy trình:**
  1. Kiểm tra nghiêm ngặt: Phương thức gửi lên bắt buộc là `POST` và người gọi phải có vai trò `admin` hoặc `trainer`.
  2. Kiểm tra bản ghi tồn tại trong CSDL.
  3. Thực thi Prepared Statement:
     ```sql
     DELETE FROM fitness_classes WHERE id = :id
     ```
  4. Trả về thông báo thành công và chuyển hướng an toàn về `classes.php`.

---

## 4. KẾT QUẢ KIỂM THỬ THỰC TẾ (TEST VERIFICATION)

| Kịch bản kiểm thử | Hành vi kỳ vọng | Kết quả thực tế | Trạng thái |
| :--- | :--- | :--- | :--- |
| **1. Cú pháp PHP** | Kiểm tra cú pháp `php -l` cho toàn bộ 14 file PHP | 100% không có lỗi cú pháp (`No syntax errors detected`) | **PASS** |
| **2. Khách vãng lai xem danh mục** | Truy cập `classes.php` không đăng nhập | Xem được toàn bộ danh sách, không có nút Add/Edit/Delete | **PASS** |
| **3. RBAC Chặn Member vào Form** | Member truy cập thẳng `GET /pages/class_form.php` | Server trả về mã **HTTP 403 Forbidden**, hiển thị màn hình Access Denied có thông tin vai trò | **PASS** |
| **4. RBAC Chặn Member Xóa bài** | Member gửi `POST /pages/delete_class.php` | Server trả về mã **HTTP 403 Forbidden**, bản ghi trong CSDL được bảo vệ 100% không bị xóa | **PASS** |
| **5. Thao tác CREATE (Staff)** | Trainer gửi `POST class_form.php` tạo lớp `Kettlebell Power Conditioning` | Tạo thành công lớp ID #7 trong MySQL, chuyển hướng về `classes.php` với Flash message | **PASS** |
| **6. Thao tác READ & Search** | Tìm kiếm từ khóa `Kettlebell` và lọc theo danh mục | Lọc chính xác lớp học vừa tạo | **PASS** |
| **7. Thao tác UPDATE (Staff)** | Trainer cập nhật giá lên `$32.00` và đổi tên lớp | Dữ liệu cập nhật chính xác trong CSDL và phản ánh ngay lên giao diện | **PASS** |
| **8. Thao tác DELETE (Staff)** | Trainer gửi `POST delete_class.php` xóa lớp ID #7 | Bản ghi bị xóa vĩnh viễn khỏi MySQL, hiển thị Flash message thông báo xóa thành công | **PASS** |

---

## 5. ĐƯỜNG DẪN TRUY CẬP KIỂM TRA TRỰC TIẾP TRÊN LOCALHOST

1. **Danh mục toàn bộ các lớp tập (Read & Filter):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/classes.php](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/classes.php)

2. **Trang Thêm mới lớp tập (Create - Yêu cầu role Admin/Trainer):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/class_form.php](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/class_form.php)  
   *(Khi đăng nhập bằng tài khoản Member `member@gymfitness.local`, mở link này sẽ chứng kiến lỗi **HTTP 403 Forbidden - Access Denied**)*

3. **Trang Bảng điều khiển (Dashboard - Phân quyền giao diện theo Role):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/dashboard.php)  
   *(Có sẵn nút thử nghiệm vi phạm bảo mật RBAC cho Member)*

4. **Trang Chi tiết lớp tập mẫu (ID = 1 - Có nút Edit/Delete khi là Staff):**  
   [http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=1](http://localhost/LAB1.3_CC01_2452475/bookstore/pages/item_details.php?id=1)
