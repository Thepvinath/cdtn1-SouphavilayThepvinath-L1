# CHECKLIST BẢO MẬT – BT3

**Dự án:** Smart CRM – Mekong Mobile · **Luồng:** L1 · **Track:** SE  
**Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03  
**Phiên bản đánh giá:** nhánh `dev` → tag `v1.0` · **Ngày tự đánh giá:** 13/11/2026

Mỗi mục ghi một trong ba giá trị **ĐÃ LÀM / CHƯA LÀM / KHÔNG ÁP DỤNG**, kèm minh chứng là tệp, lệnh đã chạy và kết quả, hoặc ảnh chụp trong `docs/screenshots/`. Mục nào chưa làm được ghi trung thực kèm phần đã có và hướng xử lý.

## Bảng tổng hợp

| # | Mục kiểm | Trạng thái |
|---:|---|---|
| 1 | Không có secret nào trong repo | **ĐÃ LÀM** |
| 2 | Dữ liệu vào được kiểm ở tầng ứng dụng | **ĐÃ LÀM** |
| 3 | Kiểm soát truy cập theo chủ sở hữu | **CHƯA LÀM** (đã có xác thực + phân quyền theo vai trò) |
| 4 | Mật khẩu và dữ liệu nhạy cảm được băm hoặc che | **ĐÃ LÀM** |
| 5 | Thông báo lỗi không lộ cấu trúc bên trong | **ĐÃ LÀM** |
| 6 | Nhật ký không ghi secret hay dữ liệu cá nhân không cần thiết | **ĐÃ LÀM** |
| 7 | Phụ thuộc được ghim phiên bản và đã kiểm cảnh báo lỗ hổng cơ bản | **ĐÃ LÀM** (còn 4 cảnh báo mức moderate ở công cụ dev, ghi rõ bên dưới) |

## Chi tiết và minh chứng

### 1. Không có secret nào trong repo – ĐÃ LÀM

- `.env` bị chặn bởi `.gitignore` (dòng 2) và `.dockerignore` (không chép vào image):
  ```text
  $ git check-ignore -v .env
  .gitignore:2:.env	.env
  ```
- `.env` **chưa từng** vào lịch sử Git: `git log --all --oneline -- .env` → không in dòng nào (0 dòng).
- `git ls-files` không có tệp `.env` nào; chỉ có `.env.example` và `frontend/.env.example`.
- `.env.example` chỉ chứa **giá trị mẫu cho môi trường demo cục bộ** (`smartcrm_local_db_password`, `Demo-Sales-2026`…), không dùng ở hệ thống thật; Deployment Guide mục 3 yêu cầu đổi mọi mật khẩu trước khi dùng thật.
- Bộ kiểm thử tự sinh mật khẩu ngẫu nhiên (`bin2hex(random_bytes(16))`), không có mật khẩu ghi cứng.
- Ảnh minh chứng không chứa mật khẩu: khi cần chứng minh biến môi trường của máy (BUG-07) chỉ chụp **tên biến** (`Get-ChildItem env:DB_* | Select-Object Name`).

### 2. Dữ liệu vào được kiểm ở tầng ứng dụng – ĐÃ LÀM

- Lớp kiểm tra riêng: `src/validators/CustomerValidator.php` (họ tên 1–150, SĐT chỉ chữ số và `+ ( ) . -`, 8–15 chữ số, email hợp lệ ≤ 150, địa chỉ ≤ 255), `src/validators/UserValidator.php` (chỉ nhận 4 trường cho phép, vai trò trong danh sách, mật khẩu 12–72 byte); `MergeController` chỉ nhận id là số nguyên JSON > 0.
- Truy vấn SQL dùng **prepared statement** PDO với `ATTR_EMULATE_PREPARES => false` (`src/config/Database.php`) – không nối chuỗi dữ liệu người dùng vào SQL.
- Minh chứng chạy thật (Test Report): TC-05 (7 chữ số → `InvalidArgumentException`), TC-06 (thiếu họ tên → 400, `create()` được gọi 0 lần), TC-07 (SĐT trùng → 409, bảng không thêm dòng), TC-08 (gộp vào chính nó → từ chối); `docs/api-docs.md` có ví dụ thật: `{"master_customer_id":"1",…}` → 400 `Ma ho so khach hang khong hop le`.

### 3. Kiểm soát truy cập theo chủ sở hữu – CHƯA LÀM

**Đã có (kiểm chứng được):**
- Mọi API dữ liệu khách hàng và nhân viên yêu cầu đăng nhập; trạng thái tài khoản được đọc lại từ CSDL ở mỗi request (`RequestGuard::requireLogin`). Lỗ hổng đọc dữ liệu khi chưa đăng nhập đã được phát hiện và sửa (BUG-02).
- Phân quyền theo vai trò: tạo/sửa/xoá/gộp khách hàng chỉ `SALES_STAFF`, `STORE_MANAGER`; quản lý nhân viên chỉ `STORE_MANAGER`; mọi thao tác thay đổi dữ liệu cần CSRF token.
- Test PASS: **TC-10** (chưa đăng nhập → 401 ở 3 endpoint, không lộ `full_name`), **TC-11** (`SALES_STAFF` gọi `/api/users` → 403); 58 kiểm tra integration gồm 401/403/CSRF cho User CRUD.

**Chưa có:** quy tắc QT-14 "nhân viên chỉ xem dữ liệu của cửa hàng/trung tâm mình" – mô hình dữ liệu L1 chưa có cột cửa hàng trên `customers`/`users`, nên mọi nhân viên đã đăng nhập xem được toàn bộ khách hàng. **Hướng xử lý:** thêm `store_id` cho `users` và `customers`, lọc theo `store_id` của phiên đăng nhập ở repository, và thêm test "nhân viên cửa hàng A đọc khách hàng cửa hàng B → 403/404".

### 4. Mật khẩu và dữ liệu nhạy cảm được băm hoặc che – ĐÃ LÀM

- Mật khẩu lưu bằng `password_hash(…, PASSWORD_DEFAULT)` (bcrypt), kiểm bằng `password_verify`. Kiểm trực tiếp trong CSDL:
  ```text
  SELECT email, LEFT(password_hash,7) prefix, LENGTH(password_hash) len FROM users;
  demo.sales@smartcrm.local     $2y$10$   60
  demo.manager@smartcrm.local   $2y$10$   60
  ```
- API không bao giờ trả `password_hash` (danh sách trường an toàn trong `UserRepository::FIELDS`, `AuthService` chỉ trả `user_id, full_name, email, role_id, role_name`); integration test "Create does not disclose credentials", "Password stored as verifiable hash" PASS.
- Số điện thoại khách hàng luôn được **che** trong phản hồi API (`09******67`), `phone_normalized` không được trả ra (BR5/NFR3; BUG-08 sửa mẫu che cho số nhập dạng `+84`).
- Cookie phiên: `HttpOnly`, `SameSite=Strict`, `session.use_strict_mode=1`; đổi session id khi đăng nhập/đăng xuất.
- Giới hạn đã biết: ngoại lệ "Quản lý xem đầy đủ số điện thoại" của QT-15 chưa hiện thực (hiện che với mọi vai trò – an toàn hơn yêu cầu).

### 5. Thông báo lỗi không lộ cấu trúc bên trong – ĐÃ LÀM

- `src/public/index.php` bắt mọi ngoại lệ chưa xử lý và trả đúng một thông báo chung: `{"error":"INTERNAL_SERVER_ERROR","message":"He thong gap loi"}`; chi tiết chỉ ghi vào log máy chủ (`error_log`).
- Gây lỗi thật bằng cách **tắt MySQL** khi API đang chạy (13/11/2026): mọi endpoint trả `500 {"error":"INTERNAL_SERVER_ERROR","message":"He thong gap loi"}`, `/api/health` trả `503 {"status":"error","database":"unavailable"}` – không có stack trace, tên bảng, câu SQL hay chuỗi kết nối.
- Ảnh Docker không có `php.ini` nên mặc định `display_errors=1`, `expose_php=1` (header `X-Powered-By: PHP/8.3.x`). Đã thêm `conf.d/zz-smartcrm.ini` (`display_errors=Off`, `expose_php=Off`) và `server_tokens off` cho nginx (TD-15). Kiểm: `curl.exe -s -i http://localhost:8080/api/health` → có `Server: nginx` (không số phiên bản), **không có** `X-Powered-By` ([`docker-06-ps-headers.png`](screenshots/docker-06-ps-headers.png)).
- Lỗi kết nối phía giao diện hiển thị thông báo dễ hiểu thay cho lỗi thô của trình duyệt (BUG-04).

### 6. Nhật ký không ghi secret hay dữ liệu cá nhân không cần thiết – ĐÃ LÀM

- Mã ứng dụng chỉ ghi log khi có lỗi, và chỉ ghi thông điệp ngoại lệ (`error_log($e->getMessage())`); không ghi mật khẩu, token, nội dung request hay dữ liệu khách hàng. Seeder không in mật khẩu khi mật khẩu lấy từ `.env` (chỉ in "value of DEMO_SALES_PASSWORD in the configuration").
- PHP built-in server chỉ ghi `IP:port Accepted/Closing`, không ghi URL (kiểm ngày 13/11/2026 với `GET /api/customers?search=0901234567`).
- nginx mặc định ghi cả query string (`/api/customers?search=<tên hoặc SĐT>`) → đã đổi định dạng log sang `$uri` (không có query string) (TD-15). Kiểm: tìm `0912` trên giao diện rồi `docker compose logs web --tail 5` → dòng log chỉ có `"GET /api/customers"` ([`docker-10-web-log.png`](screenshots/docker-10-web-log.png)).
- Giới hạn đã biết: log nginx vẫn ghi địa chỉ IP của người dùng (cần cho vận hành).

### 7. Phụ thuộc được ghim phiên bản và đã kiểm cảnh báo lỗ hổng – ĐÃ LÀM

- Frontend: `frontend/package-lock.json` đã commit; image build bằng `npm ci` (cài đúng phiên bản trong lock file).
- Backend PHP: không có thư viện bên thứ ba (không Composer); chỉ extension chính thức `pdo_mysql`.
- Image nền ghim theo phiên bản: `mysql:8.0`, `php:8.3-cli-alpine`, `node:22-alpine`, `nginx:1.27-alpine` (ghim mức minor, chưa ghim digest).
- Kiểm lỗ hổng (13/11/2026, thư mục `frontend/`):
  ```text
  $ npm audit --omit=dev
  found 0 vulnerabilities
  $ npm audit
  4 moderate severity vulnerabilities   (minimatch 7.0.0–11.0.0 qua eslint >=10, @eslint/config-array, @eslint/js)
  ```
  Cả 4 cảnh báo nằm ở **công cụ lint dùng khi phát triển** (ESLint), không có trong ảnh `web` chạy thật (chỉ chứa tệp tĩnh đã build + nginx). Bản sửa đề xuất `npm audit fix --force` hạ ESLint xuống 9.x (thay đổi phá vỡ) nên **chưa áp dụng**; theo dõi bản ESLint 10.x mới.
