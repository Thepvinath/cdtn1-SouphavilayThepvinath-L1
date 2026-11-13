# TEST REPORT – BT3

**Dự án:** Smart CRM – Mekong Mobile · **Luồng:** L1 · **Track:** SE  
**Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03  
**Kế hoạch kiểm thử:** [`docs/test-plan.md`](test-plan.md) · **Mã nguồn test case:** [`tests/run_bt3_cases.php`](../tests/run_bt3_cases.php)

## 1. Thông tin lần chạy

| Mục | Lần chạy 1 (trước khi sửa lỗi) |
|---|---|
| Ngày chạy | 06/11/2026 |
| Máy | Windows, PowerShell 7, XAMPP PHP, MySQL Server 8.0 (máy của sinh viên) |
| Commit được kiểm thử | `a5dccb1` (nhánh `dev`) |
| Lệnh | `& "C:\xampp\php\php.exe" tests/run_bt3_cases.php TC-xx` (chạy từng mã) |
| Dữ liệu | Mỗi lần chạy test API tự tạo database tạm `smartcrm_bt2_tc_<ngẫu nhiên>`, chạy `database/migrate.php` + `database/seed_bt2.php` thật (3 hồ sơ mẫu, 2 tài khoản demo), xoá database khi kết thúc |
| Kết quả | **12 test case đã chạy thật · 10 PASS · 2 FAIL** (TC-09, TC-10) |

## 2. Bảng test case (8 cột)

Cột "Các bước" mô tả những gì script thực hiện khi chạy lệnh `tests/run_bt3_cases.php TC-xx`. Ảnh chụp kết quả nằm trong [`docs/screenshots/`](screenshots/), đặt tên theo mã test.

| Mã | Tiền điều kiện | Các bước | Dữ liệu thử | Kết quả mong đợi | Ưu tiên | Kết quả thực tế (lần 1) | PASS/FAIL |
|---|---|---|---|---|---|---|---|
| **TC-01** | Đã đăng nhập `SALES_STAFF`; SĐT `0901000001` chưa tồn tại | 1. Đếm bảng `customers`. 2. `POST /api/customers`. 3. Đếm lại bảng và tìm dòng theo `phone_normalized` | `{full_name:"TC01 Nguyen Van Moi", phone:"0901000001", email:"tc01@example.com", address:"Quan 1"}` | HTTP 201, message "Tao ho so khach hang thanh cong"; `customers` +1 dòng; có 1 dòng `phone_normalized = 0901000001` | Cao | HTTP 201, message "Tao ho so khach hang thanh cong"; customers 3 → 4; dòng có `phone_normalized=0901000001`: 1 | **PASS** · [TC-01.png](screenshots/TC-01.png) |
| **TC-02** | Đã đăng nhập `SALES_STAFF`; đã tạo "TC02 Tran Thi Tim", SĐT `0901000002` | 1. Tạo hồ sơ tiền điều kiện. 2. `GET /api/customers?search=TC02%20Tran` | Từ khoá `TC02 Tran` | HTTP 200; đúng 1 kết quả, `full_name` "TC02 Tran Thi Tim"; SĐT bị che `09******02` | Cao | HTTP 200; 1 kết quả; full_name "TC02 Tran Thi Tim"; phone "09******02" | **PASS** · [TC-02.png](screenshots/TC-02.png) |
| **TC-03** | Đã đăng nhập `SALES_STAFF`; 2 hồ sơ ACTIVE "TC03 MASTER" và "TC03 SOURCE" cùng SĐT `0901000003` | 1. Thêm 2 hồ sơ trùng vào CSDL. 2. Đếm `merge_operations`. 3. `POST /api/customers/merge`. 4. Đọc trạng thái 2 hồ sơ, đếm `merge_operations`, `merge_items` | `{master_customer_id:<MASTER>, source_customer_id:<SOURCE>}` | HTTP 200, `data.status` SUCCESS; SOURCE → `MERGED`, `deleted_at` có giá trị; MASTER vẫn `ACTIVE`; `merge_operations` +1; `merge_items` có SOURCE | Cao | HTTP 200, data.status SUCCESS; SOURCE #5 MERGED, deleted_at có; MASTER #4 ACTIVE; merge_operations 0 → 1; merge_items của SOURCE: 1 | **PASS** · [TC-03.png](screenshots/TC-03.png) |
| **TC-04** | Không cần CSDL | 1. Gọi `CustomerValidator::validateCreate` | `{full_name:"TC04 Bien Duoi", phone:"09012345"}` – đúng **8** chữ số (biên dưới, trong vùng hợp lệ) | Không ném ngoại lệ; phone trả về `09012345` | Trung bình | Không ném ngoại lệ; phone "09012345" | **PASS** · [TC-04.png](screenshots/TC-04.png) |
| **TC-05** | Không cần CSDL | 1. Gọi `CustomerValidator::validateCreate` | `{full_name:"TC05 Bien Duoi", phone:"0901234"}` – **7** chữ số (ngay ngoài biên dưới) | `InvalidArgumentException` "So dien thoai phai co tu 8 den 15 chu so" | Trung bình | InvalidArgumentException "So dien thoai phai co tu 8 den 15 chu so" | **PASS** · [TC-05.png](screenshots/TC-05.png) |
| **TC-06** | Không cần CSDL; repository giả đếm số lần `create()` | 1. Gọi `validateCreate`. 2. Gọi `CustomerService::createCustomer` với repository giả | `{phone:"0901000006"}` – thiếu `full_name` | Validator ném `InvalidArgumentException` "Ho ten khach hang la bat buoc"; service từ chối; `create()` được gọi 0 lần | Cao | Validator: InvalidArgumentException "Ho ten khach hang la bat buoc"; service: InvalidArgumentException; create() được gọi 0 lần | **PASS** · [TC-06.png](screenshots/TC-06.png) |
| **TC-07** | Đã đăng nhập `SALES_STAFF`; đã tạo "TC07 Le Van Dau", SĐT `0901000007` | 1. Tạo hồ sơ tiền điều kiện. 2. Đếm `customers`. 3. `POST /api/customers` cùng SĐT. 4. Đếm lại | `{full_name:"TC07 Le Van Trung", phone:"0901000007"}` | HTTP 409, error `BUSINESS_ERROR`, message "So dien thoai da ton tai trong he thong"; `customers` KHÔNG thêm dòng | Cao | HTTP 409, error BUSINESS_ERROR, message "So dien thoai da ton tai trong he thong"; customers 4 → 4 | **PASS** · [TC-07.png](screenshots/TC-07.png) |
| **TC-08** | Không cần CSDL; PDO giả báo lỗi khi có truy cập CSDL | 1. Gọi `MergeService::merge` | `merge(master=5, source=5, performedBy=1)` | `DomainException` "Khong the gop mot ho so vao chinh no"; không mở giao dịch CSDL | Cao | DomainException "Khong the gop mot ho so vao chinh no" | **PASS** · [TC-08.png](screenshots/TC-08.png) |
| **TC-09** | Không cần CSDL; repository giả ghi lại dữ liệu được lưu | 1. Với mỗi giá trị, gọi `CustomerService::createCustomer`. 2. Đọc `phone_normalized` đã lưu | `"+84 901 234 567"`, `"84901234567"`, `"0901.234.567"`, `"0901234567"` | Cả 4 giá trị lưu `phone_normalized = "0901234567"` (QT-02) | Cao | "+84 901 234 567" → "84901234567"; "84901234567" → "84901234567"; "0901.234.567" → "0901234567"; "0901234567" → "0901234567" | **FAIL** → [BUG-03](bug-log.md) · [TC-09.png](screenshots/TC-09.png) |
| **TC-10** | KHÔNG đăng nhập; CSDL có 3 hồ sơ mẫu của seeder | 1. `GET /api/customers`. 2. `GET /api/customers/1`. 3. `GET /api/customers/duplicates` | Không gửi cookie phiên | Cả 3 trả HTTP 401, error `UNAUTHORIZED`; phản hồi không chứa dữ liệu khách hàng (`full_name`) | Cao | /api/customers → HTTP 200 (CÓ full_name khách hàng); /api/customers/1 → HTTP 200 (CÓ full_name); /api/customers/duplicates → HTTP 200 (CÓ full_name) | **FAIL** → [BUG-02](bug-log.md) · [TC-10.png](screenshots/TC-10.png) |
| **TC-11** | Đã đăng nhập `SALES_STAFF` (không phải `STORE_MANAGER`) | 1. `GET /api/users` | Cookie phiên của `demo.sales@smartcrm.local` | HTTP 403, error `FORBIDDEN`; không trả danh sách nhân viên | Cao | HTTP 403, error FORBIDDEN; không có dữ liệu nhân viên | **PASS** · [TC-11.png](screenshots/TC-11.png) |
| **TC-12** | Database tạm đã chạy migration + seeder lần 1 | 1. Đếm `roles`, `users`, `customers`, `schema_migrations`. 2. Chạy lại `migrate.php` và `seed_bt2.php`. 3. Đếm lại | Cùng database tạm, cùng `.env` | Cả hai mã thoát 0; migration in "SKIP"; seeder không tạo mật khẩu mới; số dòng 4 bảng không đổi | Cao | migrate exit 0 (SKIP); seeder exit 0; roles 3 → 3, users 2 → 2, customers 3 → 3, schema_migrations 1 → 1 | **PASS** · [TC-12.png](screenshots/TC-12.png) |

## 3. Đối chiếu với Test Plan

| Yêu cầu BT3 (mục 2.2) | Test case | Đạt |
|---|---|---|
| ≥ 1 test luồng đúng cho mỗi quy tắc đã hiện thực | TC-01 (FR1), TC-02 (FR2), TC-03 (FR5/BR6), TC-07 (BR1), TC-09 (BR2) | Có |
| 2 test giá trị biên, hai bên cùng một ranh giới | TC-04 (8 chữ số) và TC-05 (7 chữ số) | Có |
| 2 test dữ liệu sai, kiểm mã lỗi và thông báo | TC-06, TC-07, TC-08 | Có |
| 1 test bảo mật | TC-10 (chưa đăng nhập), TC-11 (sai vai trò) – OWASP A01 | Có |
| 1 test tái lập | TC-12 | Có |

## 4. Tổng hợp lần chạy 1

- **Đã chạy:** 12/12 · **PASS:** 10 · **FAIL:** 2
- **TC-10 FAIL** – lỗ hổng truy cập dữ liệu khách hàng khi chưa đăng nhập → **BUG-02 (Critical)**.
- **TC-09 FAIL** – số điện thoại dạng `+84…` / `84…` không được chuẩn hoá theo QT-02 → **BUG-03 (Critical)**.
- Hai lỗi được gỡ theo bốn bước (tái lập · khoanh vùng · sửa · xác nhận), mỗi lỗi một commit riêng; chi tiết trong [`docs/bug-log.md`](bug-log.md). Kết quả chạy lại sau khi sửa ở mục 5.

## 5. Chạy lại sau khi sửa lỗi (lần chạy 2)

| Mục | Giá trị |
|---|---|
| Ngày chạy | 06/11/2026 · cùng máy Windows của sinh viên |
| Commit được kiểm thử | `1b9e87e` (sau hai commit sửa lỗi `e6a16aa` – BUG-02 và `1b9e87e` – BUG-03) |

| Mã | Lần 1 | Kết quả thực tế lần 2 | Lần 2 | Ảnh |
|---|---|---|---|---|
| TC-10 | FAIL | /api/customers → HTTP 401; /api/customers/1 → HTTP 401; /api/customers/duplicates → HTTP 401 | **PASS** | [BUG-02-fixed.png](screenshots/BUG-02-fixed.png) |
| TC-09 | FAIL | "+84 901 234 567" → "0901234567"; "84901234567" → "0901234567"; "0901.234.567" → "0901234567"; "0901234567" → "0901234567" | **PASS** | [BUG-03-fixed.png](screenshots/BUG-03-fixed.png) |
| Toàn bộ | – | `tests/run_all.php`: Unit tests PASS · HTTP integration tests PASS · BT3 test cases **12 passed, 0 failed** · `ALL TEST SUITES PASSED` | **PASS** | [run-all-after-fix.png](screenshots/run-all-after-fix.png) |

**Kết luận theo tiêu chí thành công của Test Plan (mục 3):** 12/12 test case đã chạy thật và có ảnh · mỗi quy tắc đã hiện thực có ≥ 1 test · 0 bug Critical đang mở · `tests/run_all.php` kết thúc bằng `ALL TEST SUITES PASSED` → **ĐẠT**.

## 6. Lần chạy 3 – trong gói Docker, phép thử máy trắng (BT3, buổi 15–16)

| Mục | Giá trị |
|---|---|
| Ngày chạy | 13/11/2026 · máy Windows 11 của sinh viên, Docker 29.9.1 (Docker Desktop) |
| Cách dựng | Clone nhánh `dev` vào thư mục **mới** `smartcrm-docker-check` → `cp .env.example .env` → `docker compose down -v` → `docker compose up -d --build` (đúng Deployment Guide) |
| Commit được kiểm thử | `cb79fed` (sau các commit BUG-05 … BUG-08 và đóng gói Docker TD-12 … TD-15) |
| Lệnh | `docker compose exec api php tests/run_all.php`; một test case: `docker compose exec api php tests/run_bt3_cases.php TC-05` |

| Kiểm tra | Kết quả thực tế | Ảnh |
|---|---|---|
| Lần dựng đầu tiên trên máy Windows (commit `dd07689`) | **FAIL** – `db` exited (1): `MYSQL_USER="root"…` → ghi **BUG-07**, sửa ở `f74a1dc` | [BUG-07-before-up.png](screenshots/BUG-07-before-up.png), [BUG-07-before-logs.png](screenshots/BUG-07-before-logs.png) |
| `docker compose up -d --build` sau khi sửa | `db` Healthy, `api` Healthy, `web` Started | [docker-05-rebuild-up.png](screenshots/docker-05-rebuild-up.png) |
| `docker compose ps` + `curl.exe -s -i http://localhost:8080/api/health` | 3 dịch vụ `(healthy)`, chỉ `web` mở cổng 8080; `HTTP/1.1 200 OK`, `Server: nginx`, không có `X-Powered-By`; `{"status":"ok","database":"ok"}` | [docker-06-ps-headers.png](screenshots/docker-06-ps-headers.png) |
| Toàn bộ bộ test trong container | Unit tests PASS · HTTP integration tests PASS · BT3 test cases **12 passed, 0 failed, 12 total** · `ALL TEST SUITES PASSED` | [docker-07-tests-and-tc05.png](screenshots/docker-07-tests-and-tc05.png) |
| Chạy riêng **TC-05** (chọn ngẫu nhiên tại chỗ) | `InvalidArgumentException "So dien thoai phai co tu 8 den 15 chu so"` · `Ket qua : PASS` · `1 passed, 0 failed` | [docker-07-tests-and-tc05.png](screenshots/docker-07-tests-and-tc05.png) |
| Tái lập: `docker compose restart api` | Log: `SKIP: manager exists…`, `SKIP: demo user already exists…`; đếm bảng: roles **3**, users **2**, customers **3** (không nhân đôi; bộ test dùng CSDL tạm riêng nên dữ liệu demo không đổi) | [docker-08-restart-counts.png](screenshots/docker-08-restart-counts.png) |
| Giao diện `http://localhost:8080` (đăng nhập `demo.sales`) | Danh sách KH1–KH3 hiển thị, SĐT được che | [docker-04-web-login.png](screenshots/docker-04-web-login.png) |
| Tạo khách hàng với SĐT `+84 912 345 678` (xác nhận BUG-08) | "Đã tạo hồ sơ khách hàng Hello test thành công!", SĐT hiển thị **`09******78`**; tìm `0912` → đúng 1 kết quả | [BUG-08-create-form.png](screenshots/BUG-08-create-form.png), [BUG-08-fixed.png](screenshots/BUG-08-fixed.png), [docker-09-search-0912.png](screenshots/docker-09-search-0912.png) |
| Log nginx sau khi tìm `0912` (checklist bảo mật mục 6) | `"GET /api/customers" 200 290` – **không có** `?search=0912` | [docker-10-web-log.png](screenshots/docker-10-web-log.png) |

**Kết luận lần chạy 3:** gói Docker dựng lại được từ thư mục trống bằng hai lệnh (`cp .env.example .env`, `docker compose up -d --build`); mọi dịch vụ healthy; 12/12 test case và toàn bộ bộ test PASS bằng một lệnh trong container; chạy lại không nhân đôi dữ liệu. Lỗi duy nhất của phép thử máy trắng (BUG-07) đã được ghi Bug Log và sửa.
