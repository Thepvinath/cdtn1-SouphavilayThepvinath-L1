
# SMART CRM – MEKONG MOBILE

## 1. Thông tin dự án

**Tên dự án:** Smart CRM – Mekong Mobile

**Luồng nghiệp vụ:** L1 – Tạo, tra cứu và gộp hồ sơ khách hàng trùng

**Chuyên ngành (Track):** Software Engineering (SE)

**Sinh viên thực hiện:** Thepvinath Souphavilay

**MSSV:** 237480201IS03

**Repository:** https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management

### 1.1. Mục tiêu

Xây dựng hệ thống quản lý hồ sơ khách hàng cho Mekong Mobile, hỗ trợ nhân viên bán hàng tạo mới, tra cứu, phát hiện và gộp các hồ sơ khách hàng bị trùng lặp.

Hệ thống hướng đến việc giảm dữ liệu trùng, cải thiện tính nhất quán của thông tin khách hàng và hỗ trợ quản lý hồ sơ hiệu quả hơn.

### 1.2. Phạm vi nghiệp vụ

Dự án tập trung vào luồng L1 – Quản lý hồ sơ khách hàng.

Các chức năng chính bao gồm:

- Tạo hồ sơ khách hàng.
- Tra cứu theo họ tên hoặc số điện thoại.
- Xem chi tiết hồ sơ khách hàng.
- Phát hiện hồ sơ nghi trùng.
- So sánh thông tin hồ sơ nghi trùng.
- Chọn hồ sơ chính (Master Record).
- Gộp hồ sơ trùng và lưu lịch sử gộp.

Các chức năng quản lý đơn hàng, thanh toán, kho hàng và chiến dịch Marketing nằm ngoài phạm vi nghiệp vụ L1.

## 2. Yêu cầu chức năng

| Mã | Chức năng | Trạng thái |
|---|---|---|
| FR1 | Tạo hồ sơ khách hàng | Đã triển khai và kiểm thử |
| FR2 | Tra cứu hồ sơ khách hàng | Đã triển khai và kiểm thử |
| FR3 | Xem chi tiết hồ sơ khách hàng | Đã triển khai và kiểm thử |
| FR4 | Phát hiện hồ sơ nghi trùng | Đã triển khai và kiểm thử |
| FR5 | Gộp hồ sơ khách hàng trùng | Đã triển khai và kiểm thử |

Ngoài ra, hệ thống có chức năng cập nhật và xóa mềm hồ sơ khách hàng để hỗ trợ kiểm chứng CRUD trong Sprint 1.

### 2.1. Authentication và Authorization

Hệ thống đã triển khai đăng nhập bằng email và mật khẩu.

Các vai trò được sử dụng:

- `SALES_STAFF`
- `STORE_MANAGER`
- `MARKETING`

Các API thay đổi dữ liệu được kiểm soát thông qua cơ chế xác thực Session, phân quyền theo vai trò và CSRF Token.

Các trường hợp kiểm thử đã thực hiện bao gồm:

- Đăng nhập hợp lệ.
- Kiểm tra thông tin người dùng hiện tại.
- Đăng xuất.
- Từ chối yêu cầu chưa đăng nhập (HTTP 401).
- Từ chối vai trò không có quyền (HTTP 403).
- Từ chối CSRF Token không hợp lệ (HTTP 403).

### 2.2. Duplicate Detection (FR4)

Hệ thống phát hiện các hồ sơ đang hoạt động có số điện thoại chuẩn hóa trùng nhau.

API:

`GET /api/customers/duplicates`

Frontend hiển thị danh sách hồ sơ nghi trùng để người dùng xem xét trước khi thực hiện gộp.

### 2.3. Merge Customer Profiles (FR5)

Người dùng được phép:

1. Xem danh sách hồ sơ nghi trùng.
2. Chọn hồ sơ chính (Master).
3. Chọn hồ sơ trùng (Source).
4. So sánh thông tin hai hồ sơ.
5. Xác nhận thao tác gộp.
6. Xem thông báo kết quả.

API:

`POST /api/customers/merge`

Dữ liệu JSON:

```json
{
  "master_customer_id": 13,
  "source_customer_id": 14
}
```

Hệ thống kiểm tra:

- ID khách hàng hợp lệ.
- Không cho phép tự gộp một hồ sơ vào chính nó.
- Hai hồ sơ phải tồn tại và còn ACTIVE.
- Hai hồ sơ phải có số điện thoại chuẩn hóa trùng nhau.
- Người thực hiện phải có quyền phù hợp.
- Yêu cầu phải có CSRF Token hợp lệ.

Khi gộp thành công:

- Hồ sơ Master tiếp tục ở trạng thái `ACTIVE`.
- Hồ sơ Source chuyển thành `MERGED`.
- Trường `deleted_at` của Source được cập nhật.
- Thao tác được ghi vào `merge_operations` và `merge_items`.

### 2.4. Transaction và Rollback (NFR4)

Chức năng Merge sử dụng MySQL Transaction.

Quá trình xử lý gồm:

1. Khóa các hồ sơ liên quan bằng `SELECT ... FOR UPDATE`.
2. Ghi lịch sử vào `merge_operations`.
3. Ghi thông tin Source vào `merge_items`.
4. Cập nhật trạng thái hồ sơ Source.
5. `COMMIT` nếu tất cả bước thành công.

Nếu phát sinh lỗi trong Transaction, hệ thống thực hiện `ROLLBACK`.

Đã kiểm thử tình huống lỗi được mô phỏng bằng MySQL Trigger trong môi trường Local:

- API trả HTTP 500.
- Master và Source giữ nguyên trạng thái ACTIVE.
- Không có lịch sử Merge không hoàn chỉnh được lưu lại.
- Test Trigger được xóa sau khi kết thúc kiểm thử.

Kết quả trên chứng minh Rollback hoạt động trong tình huống kiểm thử đã thực hiện; không đại diện cho việc đã kiểm thử tất cả tình huống lỗi có thể xảy ra.

## 3. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Frontend | React, JavaScript, Vite, CSS |
| Backend | PHP 8.x |
| Database | MySQL 8.0 |
| Database Access | PDO / pdo_mysql |
| Authentication | PHP Session |
| Authorization | Role-Based Access Control (RBAC) |
| Security | CSRF Token, Password Hashing |
| Database Management | MySQL Workbench |
| Testing | PHP Native Test Runner |
| Version Control | Git và GitHub |
| Thiết kế sơ đồ | draw.io |

Hệ thống hiện được triển khai và kiểm thử trên môi trường Local Windows với XAMPP, PHP, MySQL và Node.js.

## 4. Cấu trúc thư mục

Các thành phần chính của dự án:

```text
smartcrm-237480201is03-customer-profile-management/
|
|-- database/
|   `-- schema.sql
|
|-- docs/
|   |-- evidence/
|   |   |-- unit-tests-buoi10.txt
|   |   `-- [Ảnh minh chứng kiểm thử]
|   |-- srs.md
|   |-- api-contract.md
|   |-- ai-declaration.md
|   `-- [Sơ đồ thiết kế]
|
|-- frontend/
|   |-- src/
|   |   |-- api/
|   |   |   |-- auth.api.js
|   |   |   |-- client.js
|   |   |   |-- customers.api.js
|   |   |   `-- merge.api.js
|   |   |-- pages/
|   |   |   |-- CreateCustomerPage.jsx
|   |   |   |-- DuplicatesPage.jsx
|   |   |   `-- LoginPage.jsx
|   |   |-- App.jsx
|   |   `-- index.css
|   |-- package.json
|   `-- vite.config.js
|
|-- src/
|   |-- config/
|   |   `-- Database.php
|   |-- controllers/
|   |   |-- AuthController.php
|   |   |-- CustomerController.php
|   |   `-- MergeController.php
|   |-- repositories/
|   |   |-- AuthRepository.php
|   |   `-- CustomerRepository.php
|   |-- security/
|   |   `-- RequestGuard.php
|   |-- services/
|   |   |-- AuthService.php
|   |   |-- CustomerService.php
|   |   |-- DuplicateDetectionService.php
|   |   `-- MergeService.php
|   |-- validators/
|   |   `-- CustomerValidator.php
|   `-- public/
|       `-- index.php
|
|-- tests/
|   |-- test_db.php
|   `-- run_unit_tests.php
|
|-- .env.example
|-- .gitignore
`-- README.md
```

Thư mục `frontend/node_modules/` và các tệp `.env` chứa cấu hình riêng không được đưa lên GitHub.

## 5. Hướng dẫn cài đặt và chạy ứng dụng

### 5.1. Yêu cầu môi trường

- PHP 8.x (XAMPP)
- MySQL Server 8.0
- PHP extensions: PDO, pdo_mysql, mbstring
- Node.js và npm
- Git
- Visual Studio Code

### 5.2. Cấu hình Database

Tại thư mục gốc:

1. Sao chép `.env.example` thành `.env`.
2. Cập nhật thông tin kết nối MySQL.
3. Mở MySQL Workbench.
4. Thực thi tệp `database/schema.sql` để khởi tạo cơ sở dữ liệu nếu chưa có.

**Lưu ý:** Không chạy lại các lệnh có khả năng tạo lại hoặc thay đổi cấu trúc dữ liệu trên cơ sở dữ liệu đang chứa dữ liệu cần giữ lại nếu chưa kiểm tra và sao lưu.

Kiểm tra các bảng:

```sql
USE smartcrm;
SHOW TABLES;
```

Cơ sở dữ liệu sử dụng các bảng chính:

- `roles`
- `users`
- `customers`
- `merge_operations`
- `merge_items`

### 5.3. Cấu hình PHP Session

Trong môi trường Windows đang sử dụng, hệ thống lưu Session tại:

`%LOCALAPPDATA%\SmartCRM\sessions`

Thư mục này cần tồn tại và có quyền ghi đối với tiến trình PHP.

### 5.4. Chạy Backend

Mở PowerShell tại thư mục gốc dự án:

```powershell
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t src/public src/public/index.php
```

Backend API:

`http://127.0.0.1:8000`

Ví dụ:

`http://127.0.0.1:8000/api/auth/csrf`

Giữ Terminal Backend đang chạy.

### 5.5. Chạy Frontend

Mở Terminal mới tại thư mục gốc:

```powershell
cd frontend
npm install
npm run dev
```

Frontend chạy tại:

`http://127.0.0.1:5173`

Frontend sử dụng cấu hình Vite Proxy để gửi các yêu cầu `/api` đến PHP Backend tại cổng 8000.

Cần chạy cả Backend và Frontend để sử dụng hệ thống đầy đủ.

### 5.6. Đăng nhập

Truy cập Frontend và đăng nhập bằng tài khoản hợp lệ đã được tạo trong bảng `users`.

Mật khẩu không được lưu trực tiếp dưới dạng văn bản thuần trong cơ sở dữ liệu hoặc tài liệu công khai.

## 6. Các API chính

| Method | Endpoint | Mô tả |
|---|---|---|
| GET | `/api/auth/csrf` | Lấy CSRF Token |
| POST | `/api/auth/login` | Đăng nhập |
| GET | `/api/auth/me` | Thông tin người dùng hiện tại |
| POST | `/api/auth/logout` | Đăng xuất |
| GET | `/api/customers` | Danh sách và tra cứu khách hàng |
| POST | `/api/customers` | Tạo hồ sơ khách hàng |
| GET | `/api/customers/{id}` | Xem chi tiết hồ sơ |
| PUT | `/api/customers/{id}` | Cập nhật hồ sơ |
| DELETE | `/api/customers/{id}` | Xóa mềm hồ sơ |
| GET | `/api/customers/duplicates` | Phát hiện hồ sơ nghi trùng |
| POST | `/api/customers/merge` | Gộp hồ sơ khách hàng trùng |

Các API thay đổi dữ liệu yêu cầu xác thực, phân quyền phù hợp và CSRF Token theo cơ chế bảo vệ hiện tại của Backend.

Chi tiết yêu cầu nghiệp vụ và thiết kế API được tham chiếu tại `docs/srs.md` và `docs/api-contract.md`.

## 7. Kiểm thử

### 7.1. Kiểm tra kết nối MySQL

Chạy tại thư mục gốc dự án:

```powershell
& "C:\xampp\php\php.exe" tests/test_db.php
```

Tệp này kiểm tra khả năng kết nối đến MySQL và đọc danh sách bảng.

### 7.2. Unit Tests – Buổi 10

Hệ thống sử dụng PHP Native Test Runner, không yêu cầu Composer hoặc PHPUnit.

Chạy:

```powershell
& "C:\xampp\php\php.exe" tests/run_unit_tests.php
```

Kết quả kiểm thử ngày 24/10/2026:

```text
Results: 15 passed, 0 failed, 15 total
```

Phân loại kiểm thử:

| Nhóm | Số lượng | Kết quả |
|---|---:|---|
| Customer Create Validation | 8 | PASS |
| Customer Update Validation | 3 | PASS |
| Merge Input Validation | 4 | PASS |
| Tổng cộng | 15 | PASS |

Các Unit Tests tập trung vào kiểm tra Validation và các điều kiện đầu vào của Merge. Kiểm thử này không thay thế Integration Test hoặc End-to-End Test.

**Test Evidence:**

`docs/evidence/unit-tests-buoi10.txt`

### 7.3. Integration và API Tests

Các tình huống đã thực hiện trong quá trình phát triển:

- Tạo khách hàng thành công.
- Trả lỗi khi thiếu dữ liệu bắt buộc.
- Tra cứu và xem chi tiết khách hàng.
- Cập nhật và xóa mềm khách hàng.
- Phát hiện hồ sơ nghi trùng.
- Từ chối yêu cầu chưa đăng nhập (401).
- Từ chối người dùng không đủ quyền (403).
- Từ chối CSRF Token không hợp lệ (403).
- Từ chối Self-Merge (409).
- Merge thành công và lưu lịch sử.
- Rollback khi xảy ra lỗi được mô phỏng trong Transaction.

### 7.4. End-to-End Test – FR5

Đã thực hiện kiểm thử thao tác Merge qua giao diện React.

Dữ liệu kiểm thử:

- Master: KH13
- Source: KH14

Kết quả:

- Frontend hiển thị thông báo Merge thành công.
- MySQL ghi nhận thao tác Merge.
- KH13 tiếp tục ở trạng thái ACTIVE.
- KH14 chuyển sang MERGED.
- Danh sách hồ sơ nghi trùng được cập nhật.

Dữ liệu KH13/KH14 được sử dụng trong môi trường Local nhằm kiểm chứng chức năng.

### 7.5. Kiểm thử Rollback – NFR4

Đã sử dụng MySQL Trigger tạm thời để mô phỏng lỗi khi ghi `merge_items`.

Kết quả:

- HTTP 500 trong tình huống mô phỏng.
- Không để lại lịch sử Merge chưa hoàn chỉnh.
- Trạng thái Master và Source không thay đổi.
- Trigger tạm thời đã được xóa sau khi kiểm thử.

## 8. Tài liệu dự án

| Tài liệu | Đường dẫn |
|---|---|
| Software Requirements Specification | `docs/srs.md` |
| API Contract | `docs/api-contract.md` |
| AI Declaration | `docs/ai-declaration.md` |
| SQL Database Schema | `database/schema.sql` |
| Unit Test Evidence | `docs/evidence/unit-tests-buoi10.txt` |
| Các sơ đồ kiến trúc và dữ liệu | `docs/` |
| Minh chứng kiểm thử | `docs/evidence/` |

## 9. Tiến độ thực hiện

### BT1 – Phân tích và Thiết kế

- [x] Xây dựng tài liệu SRS.
- [x] Phân tích Use Case.
- [x] Thiết kế kiến trúc hệ thống.
- [x] Thiết kế ERD và SQL DDL.
- [x] Thiết kế Wireframe.
- [x] Khai báo sử dụng AI.

### Sprint 1 – Customer CRUD

- [x] Xây dựng Controller – Service – Repository.
- [x] Kết nối PHP với MySQL.
- [x] Tạo hồ sơ khách hàng.
- [x] Tra cứu và xem chi tiết hồ sơ.
- [x] Cập nhật hồ sơ.
- [x] Xóa mềm hồ sơ.
- [x] Kiểm thử các tình huống HTTP 400, 404 và 409.

### Sprint 2 – Authentication, Duplicate Detection và Merge

- [x] Authentication bằng PHP Session.
- [x] Role-Based Authorization.
- [x] CSRF Protection.
- [x] Phát hiện hồ sơ nghi trùng (FR4).
- [x] Merge Backend và lưu lịch sử (FR5).
- [x] Giao diện React để so sánh và gộp hồ sơ.
- [x] Kiểm thử Merge End-to-End.
- [x] Kiểm thử Transaction Rollback (NFR4).
- [x] Chạy 15 Unit Tests thành công.
- [x] Lưu kết quả Unit Tests vào `docs/evidence/`.

### Công việc hoàn thiện

- [ ] Rà soát tài liệu và minh chứng kiểm thử.
- [ ] Kiểm tra các tệp trước khi Commit.
- [ ] Commit và Push mã nguồn lên GitHub.
- [ ] Kiểm tra Repository sau khi Push.

## 10. Giới hạn và lưu ý

Hệ thống hiện được xây dựng và thử nghiệm trong môi trường Local phục vụ mục đích học tập.

Các giới hạn cần tiếp tục xem xét:

- Chưa xác nhận mức độ bao phủ Unit Tests cho toàn bộ chức năng.
- Cần bổ sung kiểm thử bảo mật và các tình huống cạnh tranh dữ liệu.
- Quy tắc phát hiện hồ sơ trùng hiện tập trung vào số điện thoại chuẩn hóa.
- Cần tiếp tục đánh giá hiệu năng và các NFR khác theo SRS.
- Không sử dụng dữ liệu hoặc tài khoản kiểm thử làm dữ liệu Production.

## 11. Khai báo sử dụng AI

ChatGPT (OpenAI) được sử dụng để hỗ trợ:

- Giải thích yêu cầu bài tập.
- Phân tích SRS, User Stories và Use Cases.
- Tham khảo thiết kế kiến trúc, ERD và SQL DDL.
- Hỗ trợ phát triển và kiểm tra mã nguồn.
- Hỗ trợ xây dựng kịch bản kiểm thử.
- Chuẩn bị tài liệu và tổng hợp kết quả.

Người thực hiện chịu trách nhiệm kiểm tra mã nguồn, chạy thử hệ thống và xác nhận kết quả thực tế.

Chi tiết được trình bày trong `docs/ai-declaration.md`.
