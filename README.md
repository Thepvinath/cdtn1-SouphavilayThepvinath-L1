# SMART CRM – MEKONG MOBILE

## 1. Thông tin dự án

**Tên dự án:** Smart CRM – Mekong Mobile

**Luồng nghiệp vụ:** L1 – Tạo, tra cứu và gộp hồ sơ khách hàng trùng

**Chuyên ngành (Track):** Software Engineering (SE)

**Sinh viên thực hiện:** Thepvinath Souphavilay

**MSSV:** 237480201IS03

**Công nghệ dự kiến:** PHP, MySQL, Bootstrap, JavaScript

**Repository:** https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management

### Mục tiêu

Xây dựng hệ thống quản lý hồ sơ khách hàng nhằm hỗ trợ nhân viên bán hàng tại Mekong Mobile trong việc tạo mới, tra cứu và xử lý các hồ sơ khách hàng bị trùng lặp.

Hệ thống hướng đến việc giảm dữ liệu trùng, cải thiện tính nhất quán của thông tin và hỗ trợ quản lý hồ sơ khách hàng hiệu quả hơn.

## 2. Phạm vi và chức năng

### 2.1. Phạm vi nghiệp vụ

Dự án tập trung vào luồng nghiệp vụ L1 – Quản lý hồ sơ khách hàng, bao gồm:

- Tạo hồ sơ khách hàng mới.
- Tra cứu khách hàng theo họ tên hoặc số điện thoại.
- Xem thông tin chi tiết hồ sơ khách hàng.
- Phát hiện các hồ sơ có khả năng bị trùng.
- So sánh thông tin giữa các hồ sơ nghi trùng.
- Lựa chọn hồ sơ chính (Master Record).
- Gộp các hồ sơ trùng và cập nhật thông tin sau khi gộp.

### 2.2. Các yêu cầu chức năng chính

| Mã | Chức năng |
|---|---|
| FR1 | Tạo hồ sơ khách hàng |
| FR2 | Tra cứu hồ sơ khách hàng |
| FR3 | Xem chi tiết hồ sơ khách hàng |
| FR4 | Phát hiện hồ sơ có khả năng bị trùng |
| FR5 | Gộp hồ sơ khách hàng trùng |

### 2.3. Ngoài phạm vi

Dự án không triển khai các chức năng quản lý đơn hàng, thanh toán, kho hàng hoặc chiến dịch Marketing trong phạm vi BT1.

## 3. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Frontend | HTML, Bootstrap, JavaScript |
| Backend | PHP |
| Database | MySQL 8.0 |
| Database Management | MySQL Workbench |
| Version Control | Git và GitHub |
| Thiết kế sơ đồ | draw.io |

## 4. Cấu trúc thư mục

```text
smartcrm-237480201is03-customer-profile-management/
├── docs/
│   ├── srs.md
│   ├── api-contract.md
│   ├── ai-declaration.md
│   └── [Các sơ đồ và hình ảnh thiết kế]
├── database/
│   └── schema.sql
├── .gitignore
├── .env.example
└── README.md
```

**Ghi chú:** Danh sách trên mô tả các thành phần chính của dự án. Các tệp sơ đồ gốc và tài liệu bổ sung sẽ được hoàn thiện theo yêu cầu của bài tập.

## 5. Tiến độ thực hiện

### Bài tập 1 – Phân tích và Thiết kế

- Xây dựng tài liệu đặc tả yêu cầu phần mềm (SRS).
- Phân tích Use Case và xây dựng Use Case Diagram.
- Thiết kế kiến trúc hệ thống.
- Thiết kế mô hình dữ liệu ERD và SQL DDL.
- Thiết kế Wireframe cho ba màn hình chính.
- Khai báo phạm vi sử dụng công cụ AI.
- Kiểm tra bước đầu cấu trúc cơ sở dữ liệu trên MySQL Workbench.

**Kết quả kiểm tra cơ sở dữ liệu:** Đã chạy thành công SQL DDL, xác nhận 5 bảng và 5 quan hệ khóa ngoại, đồng thời thử nghiệm INSERT và SELECT trên bảng `customers`.

**Lưu ý:** Các chức năng nghiệp vụ và giao diện web chưa được xác nhận là đã triển khai và kiểm thử hoàn chỉnh.

## 6. Tài liệu và hướng dẫn kiểm tra

### 6.1. Tài liệu thiết kế

- `docs/srs.md`: Đặc tả yêu cầu phần mềm.
- `docs/api-contract.md`: Đặc tả API dự kiến.
- `docs/ai-declaration.md`: Khai báo sử dụng AI.
- `database/schema.sql`: Cấu trúc cơ sở dữ liệu MySQL.
- Các sơ đồ trong thư mục `docs/`: Use Case, Architecture, ERD và Wireframe.

### 6.2. Hướng dẫn kiểm tra cơ sở dữ liệu

**Yêu cầu:** MySQL Server 8.0 và MySQL Workbench.

1. Mở MySQL Workbench và kết nối đến MySQL Server.
2. Mở tệp `database/schema.sql`.
3. Thực thi các câu lệnh SQL để tạo cơ sở dữ liệu `smartcrm`.
4. Kiểm tra danh sách bảng trong schema `smartcrm`.
5. Kiểm tra các khóa ngoại và thực hiện thử nghiệm INSERT/SELECT.

Lệnh kiểm tra danh sách bảng:

```sql
USE smartcrm;
SHOW TABLES;
```

Lệnh kiểm tra khóa ngoại:

```sql
SELECT TABLE_NAME, COLUMN_NAME,
       REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'smartcrm'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### 6.3. Công cụ AI

ChatGPT (OpenAI) được sử dụng để hỗ trợ phân tích yêu cầu, tham khảo thiết kế hệ thống, xây dựng tài liệu và SQL DDL.

Chi tiết được khai báo tại `docs/ai-declaration.md`.

## Yêu cầu môi trường – Buổi 8

- PHP 8.0.30 (XAMPP)
- MySQL Server 8.0
- PDO và pdo_mysql
- Visual Studio Code
- PowerShell
- Git và GitHub

### Cấu hình

1. Sao chép `.env.example` thành `.env`.
2. Cập nhật thông tin kết nối MySQL trong `.env`.
3. Tạo cơ sở dữ liệu bằng `database/schema.sql`.
4. Không đưa file `.env` lên GitHub.

### Chạy ứng dụng

Mở Terminal tại thư mục gốc và chạy:

```powershell
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t src/public src/public/index.php
```

API chạy tại:

`http://127.0.0.1:8000/api/customers`

## Trạng thái hiện tại – Buổi 8 (Sprint 1)

- [x] Tiếp nhận và xử lý phản hồi BT1.
- [x] Tạo nhật ký thay đổi thiết kế.
- [x] Xây dựng cấu trúc Controller – Service – Repository.
- [x] Kết nối PHP với MySQL.
- [x] CREATE: Tạo hồ sơ khách hàng.
- [x] READ: Tra cứu và xem chi tiết hồ sơ.
- [x] UPDATE: Cập nhật thông tin hồ sơ.
- [x] DELETE: Xóa mềm hồ sơ khách hàng.
- [x] Kiểm tra số điện thoại trùng – HTTP 409.
- [x] Kiểm tra dữ liệu không hợp lệ – HTTP 400.
- [x] Kiểm tra GET sau Soft Delete – HTTP 404.
- [ ] Hoàn thiện Authentication và Authorization.
- [ ] Hiện thực phát hiện và gộp hồ sơ trùng.
- [ ] Bổ sung kiểm thử tự động.

### Giới hạn hiện tại

Module CRUD là phiên bản thử nghiệm trong môi trường
local. Chưa triển khai xác thực và phân quyền cho các
API thay đổi dữ liệu.

Các chức năng UPDATE và DELETE được xây dựng để
kiểm chứng CRUD trong Sprint 1. Phạm vi nghiệp vụ L1
chính thức vẫn tuân theo tài liệu SRS và cần được
đối chiếu trước các Sprint tiếp theo.
