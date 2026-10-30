# SMART CRM – MEKONG MOBILE

**Bài tập 2 (BT2) — Chuyên đề tốt nghiệp 1 | Track Software Engineering (SE)**

- **Luồng nghiệp vụ:** L1 – Tạo, tra cứu, gộp hồ sơ khách hàng trùng
- **Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03
- **Repository:** https://github.com/Thepvinath/smartcrm-237480201is03-customer-profile-management
- **Nhánh phát triển:** `dev` · **Tag nộp BT2:** `v0.2` (*chỉ sử dụng sau khi tạo và đẩy tag*)

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

Hệ thống còn có các API cập nhật và xóa mềm khách hàng để kiểm thử CRUD. Ngoài phạm vi: đặt hàng, thanh toán, quản lý kho và chiến dịch marketing.

**Kiến trúc:** React/Vite → API PHP (Controller) → Service/Validator → Repository (PDO) → MySQL. Authentication sử dụng PHP Session; các thao tác thay đổi dữ liệu sử dụng RBAC và CSRF Token.

## 2. Yêu cầu môi trường

| Thành phần | Yêu cầu cho quy trình bên dưới |
|---|---|
| Hệ điều hành | Windows, PowerShell |
| Backend | PHP 8.x (XAMPP), extensions `PDO`, `pdo_mysql`, `mbstring` |
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

Mở PowerShell tại thư mục muốn chứa dự án. Trong giai đoạn chuẩn bị (trước khi phát hành tag), dùng nhánh `dev`:

```powershell
cd C:\Users\Donut
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

**Dùng tệp hỗ trợ BT2 `database/seed_bt2.php` đi kèm bản README này** (phải được thêm và commit vào repo trước khi phát hành `v0.2`). Seeder chỉ chấp nhận database `smartcrm_bt2_*`, kiểm tra `.env` đúng database, không thay đổi dữ liệu nguồn nếu chạy lại.

```powershell
& "C:\xampp\php\php.exe" database/seed_bt2.php smartcrm_bt2_clean_clone
```

Chạy lần đầu sẽ thêm vai trò, 3 hồ sơ khách hàng mẫu (gồm một cặp nghi trùng), tài khoản `demo.sales@smartcrm.local` (`SALES_STAFF`) và `demo.manager@smartcrm.local` (`STORE_MANAGER`). **Mỗi mật khẩu demo được tạo ngẫu nhiên và hiển thị đúng một lần trong Terminal**. Ghi lại mật khẩu vào nơi riêng tư **bên ngoài Repository**; không đưa vào ảnh minh chứng hoặc tài liệu công khai. Chạy lại không đổi mật khẩu, vai trò hoặc trạng thái tài khoản đã tồn tại.

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

Kết quả đã lưu trong quá trình phát triển: `Results: 15 passed, 0 failed, 15 total`. Hãy **chạy lại** và xác nhận trên máy mới; không xem kết quả lịch sử là bằng chứng của môi trường hiện tại.

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

Tài khoản đăng nhập: `demo.sales@smartcrm.local`; mật khẩu là giá trị **Seeder hiển thị tại lần chạy đầu tiên**. Không dùng password của tài khoản GitHub/MySQL để đăng nhập ứng dụng.

## 4. Các màn hình và luồng demo

**Ba màn hình chính** sử dụng API thật:

1. **Danh sách và tra cứu khách hàng:** xem các khách hàng `ACTIVE`, nhập từ khóa theo tên hoặc số điện thoại, thử từ khóa không có kết quả.
2. **Tạo hồ sơ khách hàng:** nhập họ tên và số điện thoại hợp lệ, kiểm tra danh sách đã cập nhật; thử email sai hoặc thiếu trường bắt buộc để quan sát Validation.
3. **Hồ sơ khách hàng nghi trùng:** dữ liệu mẫu gồm `BT2 DEMO MASTER` và `BT2 DEMO SOURCE`. Chọn một hồ sơ làm Master, hồ sơ còn lại làm Source, so sánh rồi bấm **Xác nhận gộp hồ sơ**.

Sau Merge thành công, Master giữ `ACTIVE`, Source chuyển `MERGED`, thông tin Merge được ghi trong `merge_operations` và `merge_items`; cặp trùng không còn xuất hiện trong danh sách hồ sơ `ACTIVE` nghi trùng.

**Ba trạng thái giao diện cần kiểm chứng:**

- **Đang tải (Loading):** khi đang kiểm tra phiên đăng nhập hoặc nạp danh sách.
- **Rỗng (Empty):** tìm từ khóa không tồn tại, hoặc sau khi gộp hết cặp hồ sơ trùng.
- **Lỗi (Error):** dừng Backend trong lúc Frontend đang chạy, thực hiện tải lại và quan sát thông báo lỗi / nút thử lại; sau đó bật Backend để tiếp tục.

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

Đọc thêm: [`docs/api-contract.md`](docs/api-contract.md), [`docs/srs.md`](docs/srs.md).

## 5. Kiểm thử và minh chứng

Bộ unit test PHP Native trong `tests/run_unit_tests.php` gồm **34 trường hợp**: 15 kiểm tra Customer/Merge hiện có và 19 kiểm tra User validation/manager guard. Bộ HTTP integration tự động trong `tests/run_user_integration.php` kiểm tra User CRUD, RBAC, CSRF, đăng nhập, thu hồi phiên bị vô hiệu hóa/hạ quyền, seeder và hồi quy Customer/duplicate/merge. Xem hướng dẫn User Management bên dưới.

Các tình huống API/E2E đã được ghi nhận trong quá trình làm bài: Create, Search, Duplicate Detection, Merge, xác thực/phân quyền/CSRF, MySQL Transaction Rollback. Để kiểm chứng lại, chạy các bước trong Mục 3–4 trên database thử nghiệm mới.

**Ảnh minh chứng BT2** trong [`docs/evidence/`](docs/evidence/):

| Mã | Minh chứng |
|---|---|
| 01 | [Migration thành công](docs/evidence/bt2-01-migration-success.png) |
| 02 | [Chạy migration lần thứ hai: SKIP](docs/evidence/bt2-02-migration-idempotency.png) |
| 03 | [15 Unit Tests PASS](docs/evidence/bt2-03-unit-tests-15-passed.png) |
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

Các ảnh là **bằng chứng từ những lần kiểm thử trước**. Tệp dữ liệu và ID khách hàng có thể khác khi dựng lại từ đầu.

## 6. Cấu trúc Repository và trạng thái hiện tại

```text
.
├── .env.example
├── README.md
├── database/
│   ├── schema.sql                 # SQL DDL gốc; không dùng để khởi tạo clean clone BT2
│   ├── seed.sql                   # Seeder cũ chỉ định USE smartcrm; KHÔNG chạy cho clean clone
│   ├── migrate.php                # Migration runner smartcrm_bt2_*
│   ├── seed_bt2.php               # Seeder BT2 an toàn + tạo tài khoản demo (tệp bổ sung)
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
│   └── test_db.php
└── docs/
    ├── srs.md
    ├── api-contract.md
    ├── thay-doi-thiet-ke.md
    ├── ai-disclosure.md
    └── evidence/
```

**Đã có trong mã được rà soát:** FR1–FR5, Customer CRUD API, PHP Session + RBAC + CSRF, dịch vụ phát hiện trùng, MySQL Transaction cho Merge, 3 giao diện chính, 15 unit tests, migration và ảnh minh chứng.

**Giới hạn cần ghi nhận trung thực trước khi nộp:**

- Hai thực thể có CRUD: Customer và User (DELETE User là vô hiệu hóa mềm). Role là dữ liệu tham chiếu, không phải CRUD thứ ba.
- Unit và HTTP integration đã tự động hóa; chưa có browser E2E hoặc kiểm thử cạnh tranh nhiều kết nối.
- Chưa đo thực nghiệm NFR1 trên tập 65.000 khách hàng; đây vẫn là mục tiêu thiết kế.
- Seeder `database/seed_bt2.php` và README mới phải được đưa vào repo và thử lại trong **clone mới** trước khi phát hành tag.
- Chưa kiểm tra được việc tạo Docker image hay chạy Linux/macOS với cấu hình PHP Session phụ thuộc `%LOCALAPPDATA%`.

## 7. Tài liệu, AI và thay đổi từ BT1

- [`docs/srs.md`](docs/srs.md): yêu cầu và Use Case.
- [`docs/api-contract.md`](docs/api-contract.md): hợp đồng API.
- [`docs/thay-doi-thiet-ke.md`](docs/thay-doi-thiet-ke.md): quyết định điều chỉnh thiết kế sau phản hồi BT1.
- [`docs/ai-disclosure.md`](docs/ai-disclosure.md): khai báo sử dụng AI; cần bảo đảm có thông tin kiểm chứng cụ thể theo tiêu chí môn học.
- `docs/bug-log.md`: **cần bổ sung bản ghi lỗi thực tế trước khi nộp**, không tạo lỗi giả hoặc bằng chứng giả.

ChatGPT được sử dụng hỗ trợ phân tích yêu cầu, tài liệu, hướng dẫn lập trình và kiểm thử. Kết quả thực tế, câu lệnh chạy và lập luận trong bài nộp phải được sinh viên kiểm tra và giải thích được.

## 8. Checklist trước khi nộp BT2

- [ ] Làm theo **toàn bộ README từ một clone mới**, không dùng database cũ.
- [ ] Migration lần đầu thành công; lần thứ hai báo `SKIP`; Seeder chạy lại không nhân đôi bản ghi.
- [ ] Tạo demo login, mở được Frontend, chạy được Create → Search → Duplicate → Merge.
- [ ] Chạy 15 unit tests trên clone mới, lưu ảnh PASS (ẩn mật khẩu và dữ liệu nhạy cảm).
- [ ] Kiểm chứng một tình huống dữ liệu sai và Loading / Empty / Error.
- [ ] Đối chiếu tiêu chí SE ≥ 2 thực thể đủ CRUD, không đánh dấu đạt nếu chưa có.
- [ ] Bổ sung `docs/bug-log.md` từ lỗi thực tế và kiểm tra `docs/ai-disclosure.md`.
- [ ] `git status` sạch; `.env` và secret không nằm trong Repository; lock file đã commit.
- [ ] Hoàn thiện PDF BT2 một tệp 6–9 trang, đủ 7 phần, phụ lục AI và ảnh minh chứng.
- [ ] Sau khi kiểm tra toàn bộ: hợp nhất phiên bản nộp, tạo và đẩy **tag `v0.2`**, kiểm tra URL GitHub của tag và nộp theo VLU E-learning.

**Lưu ý:** Các kiểm tra cục bộ không thay thế việc chạy lại trên máy Windows khác hoặc clone mới. Không có commit/push/tag/deploy được thực hiện trong phần triển khai User Management.

## 9. BT2 User Management (second CRUD entity)

Đăng nhập bằng `demo.manager@smartcrm.local` với mật khẩu ngẫu nhiên của seeder, chọn **Nhân viên**. Trang hỗ trợ danh sách (gồm tài khoản đã vô hiệu hóa), chi tiết/sửa, tạo và vô hiệu hóa nhân viên; có Loading, Empty, Error/Thử lại và thông báo thành công. Nút tự vô hiệu hóa bị khóa; backend luôn kiểm tra quyền độc lập với giao diện.

| Method | Endpoint | Result |
|---|---|---|
| POST | `/api/users` | Create; 201 |
| GET | `/api/users` | List active and disabled users; 200 |
| GET | `/api/users/{id}` | Detail including disabled users; 200 |
| PUT | `/api/users/{id}` | Update name, email, role, optional password; 200 |
| DELETE | `/api/users/{id}` | Set `is_active=0`, retain row and audit references; 200 |

All five endpoints require an authenticated, currently active `STORE_MANAGER`. Writes require the session's `X-CSRF-Token` from `GET /api/auth/csrf`. Request bodies for POST/PUT are JSON objects:

```json
{
  "full_name": "Employee Name",
  "email": "employee@example.com",
  "role_name": "SALES_STAFF"
}
```

For POST, add a private `password` value of 12–72 bytes; never use a public example as a password. For PUT, omit `password` to preserve it, or supply a new valid password. An explicitly empty password is rejected. Name/email are required and limited to 150 bytes; emails are trimmed and lowercased. Roles: `SALES_STAFF`, `STORE_MANAGER`, `MARKETING`. Unsupported fields (including `is_active` and `password_hash`) are rejected. Disabled accounts cannot be reactivated through these endpoints.

Responses use `{ "data": ... }` and select only safe user fields; passwords/hashes are never returned. Errors: 400 validation/JSON, 401 unauthenticated/disabled, 403 role/CSRF, 404 missing user, 405 method, 409 duplicate email or manager safeguard. Email remains reserved after disable. Repeated DELETE is idempotent except self-disable is always rejected.

The service rejects self-disable and removing the last active manager through disable or demotion. All user mutations lock the existing manager role row in a transaction; the unique email index also protects concurrent duplicate writes. Protected requests and `/api/auth/me` reload current account status/role, so disabled sessions and stale manager permissions lose access immediately. No schema changes are required; existing migration `001_initial.sql` already defines the necessary fields and constraints.

Safe demo setup: use the clean-clone instructions above with `.env` pointing to your dedicated `smartcrm_bt2_*` database, then run `php database/seed_bt2.php YOUR_BT2_DATABASE`. The seeder refuses `smartcrm` and configuration mismatches; it generates separate random sales/manager passwords and leaves existing accounts unchanged. Do not run the legacy `seed.sql`. The local development `.env` is not changed by this implementation.

Run checks from the repository root (use `C:\xampp\php\php.exe` if PHP is not on PATH):

```powershell
& C:\xampp\php\php.exe tests/run_unit_tests.php
& C:\xampp\php\php.exe tests/run_user_integration.php
npm.cmd --prefix frontend run lint
npm.cmd --prefix frontend run build
```

The integration runner requires MySQL, `pdo_mysql`, `curl`, permission to launch a local PHP server, and CREATE/DROP privileges for disposable `smartcrm_bt2_users_*` databases. It reads connection credentials from `.env` but never uses its DB_NAME: it creates a random test database, uses a temporary source/config copy, then drops only that generated database and removes temporary files. It does not modify the original `smartcrm` database or repository `.env`. Do not interrupt it during cleanup; an interrupted run may leave its dedicated test database behind.

Local validation: **34 unit tests and 58 HTTP integration checks passed**, PHP syntax checks passed, ESLint passed, and Vite production build passed. Integration includes successful Customer CRUD, duplicate detection and merge with audit history, plus demo seeder refusal/idempotency checks. Fixed an existing extra closing brace in `frontend/src/index.css` that blocked CSS minification. Browser interaction/layout, concurrent-request stress, fresh-machine setup and 65,000-customer performance are not verified by these checks. Historical screenshots above predate User Management.
