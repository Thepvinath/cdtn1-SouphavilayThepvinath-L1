# SMART CRM – MEKONG MOBILE

**Bài tập 2 (BT2) — Chuyên đề tốt nghiệp 1 | Track Software Engineering (SE)**

- **Luồng nghiệp vụ:** L1 – Tạo, tra cứu, gộp hồ sơ khách hàng trùng
- **Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03
- **Repository:** https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management
- **Nhánh phát triển:** `dev` → `main` · **Tag nộp BT2:** `v0.2` (đối chiếu tag với commit cuối của `main` trước khi nộp)

> **Mục đích của README:** Người chấm có thể clone mã nguồn vào một thư mục mới, cấu hình MySQL, tạo dữ liệu demo, chạy API + giao diện và chạy bộ kiểm thử theo đúng từng bước mà không cần dữ liệu có sẵn trên máy của sinh viên.
>
> Các lệnh dưới đây dành cho **PowerShell trên Windows + XAMPP + MySQL 8**. Phần cấu hình trên hệ điều hành khác chưa được kiểm chứng.

## 1. Giới thiệu và phạm vi

Smart CRM – Mekong Mobile là ứng dụng quản lý hồ sơ khách hàng, hỗ trợ:

1. **FR1 – Tạo hồ sơ:** nhập họ tên, số điện thoại, email và địa chỉ; kiểm tra dữ liệu đầu vào.
2. **FR2 – Tra cứu:** tìm theo họ tên hoặc số điện thoại.
3. **FR3 – Xem chi tiết:** đọc dữ liệu một khách hàng theo mã.
4. **FR4 – Phát hiện trùng:** tìm các hồ sơ `ACTIVE` có `phone_normalized` trùng nhau.
5. **FR5 – Gộp hồ sơ:** chọn Master và Source, kiểm tra điều kiện, chuyển Source sang `MERGED` và ghi lịch sử trong MySQL Transaction.

Hệ thống có **hai thực thể CRUD thực tế**: `customers` (tạo, xem, sửa, xóa mềm) và `users` (tạo, xem, sửa, **vô hiệu hóa mềm** qua DELETE). Quản lý nhân viên chỉ dành cho tài khoản `STORE_MANAGER` còn hoạt động. `roles` là dữ liệu tham chiếu, không tính là thực thể CRUD thứ ba. Ngoài phạm vi: đặt hàng, thanh toán, quản lý kho và chiến dịch marketing.

**Kiến trúc đã hiện thực:** React/Vite → PHP Router/Controller → Service/Validator → Repository/PDO → MySQL; riêng `MergeService` dùng PDO transaction trực tiếp để gộp hồ sơ và ghi lịch sử. Authentication dùng PHP Session; backend bảo vệ dữ liệu bằng RBAC, CSRF và kiểm tra trạng thái tài khoản hiện thời. Xem [sơ đồ hiện thực BT2](docs/architecture-implemented-bt2.png) và [nguồn Draw.io chỉnh sửa được](docs/architecture-implemented-bt2.drawio).

## 2. Yêu cầu môi trường

| Thành phần | Yêu cầu cho quy trình bên dưới |
|---|---|
| Hệ điều hành | Windows, PowerShell |
| Backend | PHP 8.x (XAMPP), extensions `PDO`, `pdo_mysql`, `mbstring`, `curl` (cần cho HTTP integration) |
| Database | MySQL Server 8.0; MySQL Workbench để tạo database |
| Frontend | Node.js phiên bản phù hợp với Vite 8 (Node >=20.19 hoặc >=22.12), npm |
| Công cụ | Git, trình duyệt |
| Cổng | API `127.0.0.1:8000`, Vite `127.0.0.1:5173`, MySQL thường `3306` |

**Kiểm tra phiên bản** tại PowerShell:

```powershell
& "C:\xampp\php\php.exe" --version
```

```powershell
& "C:\xampp\php\php.exe" -m
```

```powershell
node --version
```

```powershell
npm --version
```

```powershell
git --version
```

Nếu PHP hoặc MySQL được cài ở đường dẫn/cổng khác, sửa lệnh hoặc `.env` tương ứng. **Đảm bảo MySQL Server đang chạy** trước khi thực hiện migration.

## 3. Dựng lại hệ thống từ thư mục trống (Clean Clone)

> **Lưu ý an toàn:** Quy trình sử dụng **database riêng `smartcrm_bt2_clean_clone`**, không tác động vào database gốc `smartcrm`. Không chạy `database/schema.sql` hoặc `database/seed.sql` của bản cũ trên database cần bảo toàn dữ liệu. `database/seed.sql` hiện có dòng `USE smartcrm;`; đối với Clean Clone BT2 hãy sử dụng `database/seed_bt2.php` theo bước 3.5.

### 3.1. Clone mã nguồn

Mở PowerShell tại thư mục muốn chứa dự án. Khi đang chuẩn bị bản nộp cuối, dùng nhánh `dev` để kiểm tra:

```powershell
cd $HOME
```

```powershell
git clone --branch dev --single-branch https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management.git smartcrm-bt2-clean-check
```

```powershell
cd smartcrm-bt2-clean-check
```

**Khi đã phát hành `v0.2`**, giảng viên thay lệnh clone ở trên bằng:

```powershell
git clone --branch v0.2 --single-branch https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management.git smartcrm-bt2-grading
```

Sau khi clone bằng tag, chuyển vào `smartcrm-bt2-grading` thay vì `smartcrm-bt2-clean-check`. **Tag `v0.2` phải tồn tại trên GitHub trước khi dùng lệnh này.**

### 3.2. Tạo database MySQL trống

Mở MySQL Workbench và chạy đoạn SQL dưới đây **chỉ một lần để tạo database**:

```sql
CREATE DATABASE IF NOT EXISTS smartcrm_bt2_clean_clone
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Database này phải trống trước lần migration đầu tiên (không có bảng). Không dùng cơ sở dữ liệu `smartcrm` của bài làm ban đầu.

### 3.3. Tạo và cấu hình `.env`

Tại thư mục gốc dự án:

```powershell
Copy-Item .env.example .env
```

```powershell
notepad .env
```

Điền các biến sau theo **MySQL trên máy đang cài** (ví dụ tên biến, không phải tài khoản thật):

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_NAME=smartcrm_bt2_clean_clone
DB_USER=YOUR_MYSQL_USERNAME
DB_PASSWORD=YOUR_MYSQL_PASSWORD
APP_ENV=development
APP_PORT=8000
```

Đổi `YOUR_MYSQL_USERNAME`, `YOUR_MYSQL_PASSWORD` thành thông tin MySQL **của máy đang chạy**. Tệp `.env` được `.gitignore` loại trừ: **không commit hoặc gửi mật khẩu thật**. `APP_PORT` là giá trị cấu hình tham khảo; cổng thực tế của PHP server được chọn bởi tham số `-S` ở bước 3.7.

### 3.4. Chạy migration (an toàn khi chạy lại)

```powershell
& "C:\xampp\php\php.exe" database/migrate.php smartcrm_bt2_clean_clone
```

Lần đầu mong đợi:

```text
Connected to: smartcrm_bt2_clean_clone
SUCCESS: 001_initial applied.
Tables created: 5
```

Chạy lại **chính lệnh trên** để kiểm tra tính idempotent; mong đợi:

```text
SKIP: 001_initial already applied.
```

Migration tạo 5 bảng nghiệp vụ (`roles`, `users`, `customers`, `merge_operations`, `merge_items`) và 1 bảng theo dõi `schema_migrations`, tổng cộng **6 bảng**.

### 3.5. Nạp dữ liệu và tạo tài khoản demo cục bộ

**Dùng `database/seed_bt2.php` đã được commit trong Repository BT2.** Seeder chỉ chấp nhận database `smartcrm_bt2_*`, kiểm tra `.env` đúng database, không thay đổi dữ liệu nguồn nếu chạy lại.

```powershell
& "C:\xampp\php\php.exe" database/seed_bt2.php smartcrm_bt2_clean_clone
```

Chạy lần đầu sẽ thêm vai trò, 3 hồ sơ khách hàng mẫu (gồm một cặp nghi trùng), và tài khoản `demo.sales@smartcrm.local` thuộc vai trò `SALES_STAFF`. **Mật khẩu demo được tạo ngẫu nhiên và hiển thị đúng một lần trong Terminal**. Ghi lại mật khẩu vào nơi riêng tư **bên ngoài Repository**; không đưa vào ảnh minh chứng hoặc tài liệu công khai.

Chạy lại lệnh trên để kiểm tra không nhân đôi dữ liệu. Nếu tài khoản demo đã tồn tại, Seeder sẽ không đổi mật khẩu. Các bản ghi Customer đã chuyển `MERGED` không được chuyển ngược về `ACTIVE` khi chạy lại Seeder.

> Nếu vừa hoàn thành Merge và muốn demo lại với một cặp trùng chưa gộp, hãy sử dụng **database thử nghiệm mới** theo cùng quy trình thay vì xóa/sửa dữ liệu cũ tùy tiện. Không chạy Seeder này trên dữ liệu Production.

### 3.6. Kiểm tra kết nối và chạy unit test

```powershell
& "C:\xampp\php\php.exe" tests/test_db.php
```

Kỳ vọng: `MySQL connection: SUCCESS`, tên database `smartcrm_bt2_clean_clone`, `Tables (6)`.

```powershell
& "C:\xampp\php\php.exe" tests/run_unit_tests.php
```

Kết quả đã quan sát trên máy phát triển sau khi bổ sung User Management: `Results: 34 passed, 0 failed, 34 total` (15 test Customer/Merge + 19 test User). Kiểm chứng thêm bộ HTTP integration:

```powershell
& "C:\xampp\php\php.exe" tests/run_user_integration.php
```

Kết quả lịch sử: `Integration: 58 passed, 0 failed`. Bộ integration **tạo/xóa database tạm `smartcrm_bt2_users_*`**, dùng các thông tin kết nối trong `.env` (không đổi `DB_NAME` của repository); tài khoản MySQL cần quyền `CREATE DATABASE` và `DROP DATABASE`. Cần PHP extensions `curl`, `pdo_mysql` và khả năng mở server thử nghiệm. Sau khi clone ở máy mới, phải chạy lại để xác nhận, không xem log trước đó là bằng chứng cho máy mới.

Kiểm thử Frontend từ thư mục gốc dự án:

```powershell
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
```

Các lệnh đã từng chạy PASS trên môi trường phát triển; Browser E2E tự động, thử tải đồng thời và NFR tìm kiếm trên 65.000 bản ghi chưa được kiểm chứng.

### 3.7. Chạy Backend API

Ứng dụng cấu hình PHP Session tại `%LOCALAPPDATA%\SmartCRM\sessions`. Tạo thư mục có quyền ghi:

```powershell
New-Item -ItemType Directory -Force -Path "$env:LOCALAPPDATA\SmartCRM\sessions" | Out-Null
```

Trong **Terminal 1**, từ thư mục gốc dự án:

```powershell
& "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t src/public src/public/index.php
```

Giữ Terminal này mở. Kiểm tra trong trình duyệt:

`http://127.0.0.1:8000/api/auth/csrf`

Kỳ vọng: JSON chứa `data.csrf_token`. **Không cần mở cổng 8001** trong cấu hình hiện tại: `frontend/vite.config.js` đang proxy `/api` tới `127.0.0.1:8000`.

### 3.8. Cài Dependencies và chạy Frontend

Mở **Terminal 2** tại thư mục gốc dự án:

```powershell
cd frontend
```

```powershell
npm ci
```

```powershell
npm run dev
```

Mở: `http://127.0.0.1:5173`

Tài khoản demo do Seeder tạo, mật khẩu ngẫu nhiên hiển thị **một lần duy nhất** khi tạo tài khoản:

- `demo.sales@smartcrm.local` (`SALES_STAFF`): thử Customer CRUD, Search, Duplicate, Merge.
- `demo.manager@smartcrm.local` (`STORE_MANAGER`): thử thêm User CRUD qua trang **Nhân viên**.

Không dùng mật khẩu GitHub/MySQL để đăng nhập. Nếu quên mật khẩu, Seeder chạy lại **không đặt lại** mật khẩu tài khoản đã tồn tại; có thể thử bằng database BT2 mới, tuyệt đối không sửa database `smartcrm` gốc.

## 4. Các màn hình và luồng demo

**Ba màn hình nghiệp vụ L1** sử dụng API thật:

1. **Danh sách và tra cứu khách hàng:** xem các khách hàng `ACTIVE`, nhập từ khóa theo tên hoặc số điện thoại, thử từ khóa không có kết quả.
2. **Tạo hồ sơ khách hàng:** nhập họ tên và số điện thoại hợp lệ, kiểm tra danh sách đã cập nhật; thử email sai hoặc thiếu trường bắt buộc để quan sát Validation.
3. **Hồ sơ khách hàng nghi trùng:** dữ liệu mẫu gồm `BT2 DEMO MASTER` và `BT2 DEMO SOURCE`. Chọn một hồ sơ làm Master, hồ sơ còn lại làm Source, so sánh rồi bấm **Xác nhận gộp hồ sơ**.

Sau Merge thành công, Master giữ `ACTIVE`, Source chuyển `MERGED`, thông tin Merge được ghi trong `merge_operations` và `merge_items`; cặp trùng không còn xuất hiện trong danh sách hồ sơ `ACTIVE` nghi trùng.

**Màn hình bổ sung BT2 — Quản lý nhân viên:** chỉ `STORE_MANAGER` được xem, tạo, sửa và vô hiệu hóa tài khoản; lưu `password_hash`, từ chối tự vô hiệu hóa và bảo vệ tài khoản Manager hoạt động cuối cùng. DELETE của `users` là **soft disable** (`is_active=0`), không xóa dòng vật lý.

**Ba trạng thái giao diện đã ghi nhận ảnh minh chứng từ thử nghiệm thủ công:**

- **Đang tải (Loading):** màn hình Danh sách khách hàng hiển thị `Đang tải dữ liệu...` khi mạng được giới hạn 3G; xem `bt2-20-ui-loading-state.png`.
- **Rỗng (Empty):** tìm từ khóa không tồn tại; ảnh ở `bt2-08-search-no-results.png` (bổ sung `bt2-21-ui-empty-state.png`).
- **Lỗi (Error):** khi yêu cầu API thất bại, màn hình Danh sách khách hàng hiển thị thông báo đỏ và nút **Thử lại**; xem `bt2-19-ui-error-state.png`. **Hạn chế UI hiện tại:** thông báo còn lộ lỗi kỹ thuật `Unexpected end of JSON input`; cần chuyển thành thông báo tiếng Việt thân thiện và kiểm tra lại. Đây là lỗi UX còn tồn tại, không khẳng định đã sửa.

### API chính

| Phương thức | Endpoint | Mục đích |
|---|---|---|
| GET | `/api/auth/csrf` | Nhận CSRF Token |
| POST | `/api/auth/login` | Đăng nhập |
| GET | `/api/auth/me` | Xem phiên đăng nhập |
| POST | `/api/auth/logout` | Đăng xuất |
| GET / POST | `/api/customers` | Danh sách, tìm kiếm / tạo khách hàng |
| GET / PUT / DELETE | `/api/customers/{id}` | Xem / cập nhật / xóa mềm khách hàng |
| GET | `/api/customers/duplicates` | Phát hiện hồ sơ nghi trùng |
| POST | `/api/customers/merge` | Gộp hai hồ sơ |
| GET / POST | `/api/users` | Danh sách / tạo nhân viên (STORE_MANAGER) |
| GET / PUT / DELETE | `/api/users/{id}` | Chi tiết / cập nhật / vô hiệu hóa mềm nhân viên (STORE_MANAGER) |

Đọc thêm: [`docs/api-contract.md`](docs/api-contract.md), [`docs/srs.md`](docs/srs.md).

## 5. Kiểm thử và minh chứng

Bộ PHP native unit tests `tests/run_unit_tests.php` gồm **34 trường hợp**: 15 Customer/Merge và 19 User Validation/Manager Guard. Kết quả đã quan sát: **34 passed, 0 failed**.

Bộ HTTP integration tự động `tests/run_user_integration.php` đã quan sát **58 passed, 0 failed**, bao gồm đăng nhập, RBAC/CSRF, User CRUD, khóa quyền Manager, Seeder an toàn, hồi quy Customer CRUD / Duplicate / Merge. Đây là **HTTP integration**, không phải Browser E2E. ESLint và Vite production build đã chạy thành công. Thử tải đồng thời và đo hiệu năng NFR1 với 65.000 hồ sơ chưa được thực hiện.

Các tình huống API/E2E đã được ghi nhận trong quá trình làm bài: Create, Search, Duplicate Detection, Merge, xác thực/phân quyền/CSRF, MySQL Transaction Rollback. Để kiểm chứng lại, chạy các bước trong Mục 3–4 trên database thử nghiệm mới.

**Ảnh minh chứng BT2** trong [`docs/evidence/`](docs/evidence/):

| Mã | Minh chứng |
|---|---|
| 01 | [Migration thành công](docs/evidence/bt2-01-migration-success.png) |
| 02 | [Chạy migration lần thứ hai: SKIP](docs/evidence/bt2-02-migration-idempotency.png) |
| 03 | [Unit Tests cũ 15/15 — trước bổ sung User](docs/evidence/bt2-03-unit-tests-15-passed.png) |
| 04 | [Kết nối MySQL](docs/evidence/bt2-04-database-connection.png) |
| 05 | [Đăng nhập](docs/evidence/bt2-05-login-success.png) |
| 06 | [Tạo khách hàng](docs/evidence/bt2-06-create-customer-success.png) |
| 07 | [Tra cứu có kết quả](docs/evidence/bt2-07-search-customer-found.png) |
| 08 | [Tra cứu không có kết quả](docs/evidence/bt2-08-search-no-results.png) |
| 09 | [Phát hiện hồ sơ trùng](docs/evidence/bt2-09-duplicate-detection.png) |
| 10 | [Merge thành công](docs/evidence/bt2-10-merge-success.png) |
| 11 | [Khách hàng ACTIVE sau Merge](docs/evidence/bt2-11-active-customers-after-merge.png) |
| 12 | [Trạng thái trong MySQL sau Merge](docs/evidence/bt2-12-merge-database-status.png) |
| 13 | [Lịch sử Merge](docs/evidence/bt2-13-merge-history.png) |
| 14 | [Tạo nhân viên thành công](docs/evidence/bt2-14-user-create-success.png) |
| 15 | [Danh sách nhân viên](docs/evidence/bt2-15-user-list.png) |
| 16 | [Cập nhật vai trò nhân viên](docs/evidence/bt2-16-user-update-success.png) |
| 17 | [Vô hiệu hóa nhân viên](docs/evidence/bt2-17-user-disable-success.png) |
| 18 | [Xác minh kết quả trong MySQL](docs/evidence/bt2-18-user-database-verification.png) |
| 19 | [Error State và nút Thử lại](docs/evidence/bt2-19-ui-error-state.png) |
| 20 | [Loading State khi giới hạn 3G](docs/evidence/bt2-20-ui-loading-state.png) |
| 21 | [Empty State](docs/evidence/bt2-21-ui-empty-state.png) |

Các ảnh 01–18 đã có trong nhánh phát triển trước khi chuẩn bị bản cập nhật này. Ảnh 19–21 là minh chứng mới cần thêm và commit cùng README. Ảnh `bt2-03` **chỉ chứng minh 15 test ở thời điểm cũ**; không được đặt tên lại để giả làm 34 test. Kết quả 34/34 và 58/58 đã có trong log thử nghiệm và nên bổ sung ảnh terminal thật riêng nếu cần giảng viên kiểm tra trực quan. Tệp dữ liệu và ID có thể khác sau khi dựng lại từ đầu.

## 6. Cấu trúc Repository và trạng thái hiện tại

```text
.
├── .env.example
├── README.md
├── database/
│   ├── schema.sql                 # SQL DDL gốc; không dùng để khởi tạo clean clone BT2
│   ├── seed.sql                   # Seeder cũ chỉ định USE smartcrm; KHÔNG chạy cho clean clone
│   ├── migrate.php                # Migration runner smartcrm_bt2_*
│   ├── seed_bt2.php               # Seeder BT2 an toàn + tài khoản demo Sales/Manager
│   └── migrations/001_initial.sql
├── src/
│   ├── config/
│   ├── controllers/
│   ├── repositories/
│   ├── security/
│   ├── services/
│   ├── validators/
│   └── public/index.php
├── frontend/
│   ├── package.json
│   ├── package-lock.json          # npm ci sử dụng lock file này
│   ├── vite.config.js
│   └── src/
├── tests/
│   ├── run_unit_tests.php
│   ├── run_user_integration.php
│   ├── user_unit_cases.php
│   └── test_db.php
└── docs/
    ├── srs.md
    ├── api-contract.md
    ├── thay-doi-thiet-ke.md
    ├── ai-disclosure.md
    ├── bug-log.md
    ├── architecture-implemented-bt2.png
    ├── architecture-implemented-bt2.drawio
    └── evidence/
```

**Đã kiểm tra trong mã nguồn và bằng chứng đã gửi:** FR1–FR5, hai CRUD thực thể Customer/User, PHP Session + RBAC + CSRF, Duplicate Detection, MySQL Transaction cho Merge, React UI, 34 Unit Tests và 58 HTTP Integration Checks (kết quả lịch sử), migration idempotent và Seeder BT2. Sơ đồ kiến trúc bổ sung tham khảo [Hình BT2](docs/architecture-implemented-bt2.png).

**Giới hạn và nội dung cần kiểm chứng trước khi nộp:**

- CRUD thứ hai là `users` với DELETE dưới hình thức **vô hiệu hóa**, không xóa cứng. `roles` không phải CRUD thứ ba.
- 34/34 và 58/58 là kết quả đã quan sát trên môi trường phát triển; vẫn nên chạy lại trên clean clone của commit/tag cuối.
- Có ảnh thủ công Loading, Empty, Error. Lỗi kỹ thuật trong thông báo Error chưa được sửa; không tuyên bố đã hoàn thành xử lý UX này.
- Chưa có Browser E2E tự động hoặc bài đo stress cạnh tranh nhiều request.
- Chưa đo thực nghiệm NFR1 tìm kiếm ≤2 giây trên 65.000 khách hàng.
- Chưa xác minh dựng lại trên Linux/macOS hoặc Docker; hướng dẫn được kiểm chứng trên Windows/XAMPP.

## 7. Tài liệu, AI và thay đổi từ BT1

- [`docs/srs.md`](docs/srs.md): yêu cầu và Use Case.
- [`docs/api-contract.md`](docs/api-contract.md): hợp đồng API.
- [`docs/thay-doi-thiet-ke.md`](docs/thay-doi-thiet-ke.md): quyết định điều chỉnh thiết kế sau phản hồi BT1.
- [`docs/ai-disclosure.md`](docs/ai-disclosure.md): khai báo ChatGPT và Codex, phạm vi hỗ trợ và phương pháp kiểm chứng theo từng hạng mục.
- [`docs/bug-log.md`](docs/bug-log.md): lỗi thực tế khi phát triển và dựng môi trường BT2; cần cập nhật thêm trường hợp Error JSON nếu sửa UI.

ChatGPT hỗ trợ phân tích, tài liệu, hướng dẫn kiểm thử và báo cáo; **Codex trong VS Code** hỗ trợ hiện thực User CRUD và bổ sung test tự động. Sinh viên chịu trách nhiệm chạy chương trình, kiểm tra MySQL, quan sát giao diện, đánh giá thay đổi và giải thích được kết quả. Xem tài liệu AI Disclosure để phân biệt việc AI hỗ trợ với bằng chứng chạy thực tế.

## 8. Checklist trước khi nộp BT2

- [ ] Dựng từ **clean clone của commit/tag cuối** và database `smartcrm_bt2_*` riêng; không thao tác lên `smartcrm` gốc.
- [ ] Migration lần đầu `SUCCESS`, chạy lại `SKIP`; Seeder chạy lại không nhân đôi bản ghi, không thay đổi mật khẩu đã có.
- [ ] Đăng nhập Sales và Manager; demo Customer CRUD, Search, Duplicate, Merge, User CRUD, role guard và soft disable.
- [ ] Chạy lại **34 Unit Tests** và **58 HTTP Integration Checks** trên môi trường phù hợp; lưu log/ảnh kết quả thật.
- [ ] Xác minh màn hình Loading / Empty / Error; kiểm tra nút Retry; không báo “đã sửa” lỗi JSON khi chưa sửa.
- [ ] Kiểm tra `npm.cmd --prefix frontend run lint` và `npm.cmd --prefix frontend run build`.
- [ ] Kiểm tra `docs/evidence/bt2-01...21`, tài liệu `docs/bug-log.md`, `docs/ai-disclosure.md` và Architecture thực tế.
- [ ] `git status` sạch; `.env` và mật khẩu không ở staging/commit; lock file đã có trong Git.
- [ ] Báo cáo **PDF 6–9 trang** xuất từ Word đã kiểm tra; nộp **riêng trên VLU E-learning**, không commit PDF vào Repository.
- [ ] Commit/Push trên `dev` một lần sau khi kiểm tra; merge `dev` → `main`; xác minh/tag `v0.2` theo commit cuối của `main`; kiểm tra URL GitHub.

**Lưu ý:** Tag `v0.2` đã được tạo trước lần bổ sung tài liệu BT2 và không tự động cập nhật khi thêm README/Evidence mới. Sau khi hoàn tất merge vào `main`, cần xác minh Tag có trỏ tới **commit cuối cùng** trước khi nộp; việc thay Tag đã công bố phải có kế hoạch và thông báo cho người sử dụng. Chưa thực hiện Browser E2E hoặc kiểm thử trên Windows khác.

## 9. BT2 – Quản lý nhân viên (User CRUD)

### 9.1. Chức năng và phân quyền

Đăng nhập bằng tài khoản `demo.manager@smartcrm.local` (`STORE_MANAGER`) từ Seeder BT2, chọn **Nhân viên**. Giao diện cho phép xem danh sách (cả tài khoản đã vô hiệu hóa), tạo, xem/sửa và vô hiệu hóa mềm tài khoản. Backend tự kiểm tra quyền, không dựa duy nhất vào việc ẩn/hiện nút trên frontend.

| Phương thức | Endpoint | Chức năng / HTTP thành công |
|---|---|---|
| POST | `/api/users` | Tạo tài khoản, `201` |
| GET | `/api/users` | Danh sách, `200` |
| GET | `/api/users/{id}` | Chi tiết, `200` |
| PUT | `/api/users/{id}` | Sửa tên, email, role, đổi mật khẩu nếu có, `200` |
| DELETE | `/api/users/{id}` | Đặt `is_active = 0`, giữ bản ghi, `200` |

Các API yêu cầu phiên đăng nhập thuộc tài khoản còn hoạt động và vai trò `STORE_MANAGER`; POST/PUT/DELETE yêu cầu `X-CSRF-Token` hợp lệ. Mật khẩu tạo mới bắt buộc 12–72 bytes; khi cập nhật bỏ trường password để giữ nguyên. Không gửi `password_hash` trong phản hồi. Trạng thái vô hiệu hóa không cho phép đăng nhập lại.

Backend từ chối tự vô hiệu hóa, từ chối loại bỏ/hạ quyền Manager đang hoạt động cuối cùng, và cập nhật kiểm tra role/active từ MySQL cho các request bảo vệ. Lỗi phổ biến: `400` dữ liệu không hợp lệ, `401` chưa xác thực/vô hiệu hóa, `403` thiếu quyền/CSRF, `404` không tồn tại, `405` phương thức không hỗ trợ, `409` email trùng hoặc vi phạm quy tắc Manager.

### 9.2. Kiểm chứng và giới hạn

- Unit: **34/34 PASS** (15 Customer/Merge + 19 User).
- HTTP Integration: **58/58 PASS**, có regression Customer, Duplicate/Merge, guard của Manager, RBAC/CSRF và idempotency của Seeder.
- PHP syntax, ESLint, Vite production build: đã từng PASS tại môi trường phát triển.
- Dữ liệu thực tế trong MySQL khi thử User CRUD: một tài khoản Sales được đổi sang `MARKETING` và `is_active=0`, tài khoản Manager giữ `STORE_MANAGER` và `is_active=1`, một nhân viên test mới được tạo.
- Chưa có bằng chứng tự động cho toàn bộ thao tác trình duyệt, tải đồng thời hoặc NFR ≤2 giây trên 65.000 hồ sơ.

### 9.3. Lưu ý bảo mật và sao chép môi trường

Seeder `database/seed_bt2.php` chỉ cho phép database tên `smartcrm_bt2_*` và tạo mật khẩu ngẫu nhiên **một lần**; không commit `.env`, mật khẩu hoặc screenshot chứa token. Integration Test tạo database tạm và dọn lại sau khi chạy bình thường; nếu tiến trình bị dừng giữa chừng, kiểm tra database thử nghiệm sót lại thủ công trước khi xóa. Không sử dụng các script này trên dữ liệu Production.
