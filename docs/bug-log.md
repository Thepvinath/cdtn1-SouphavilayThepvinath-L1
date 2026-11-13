# NHẬT KÝ LỖI (BUG LOG) – BT2 và BT3

**Dự án:** Smart CRM – Mekong Mobile  
**Học phần:** Chuyên đề tốt nghiệp 1 – Track Software Engineering (SE)  
**Phạm vi:** Customer CRUD, User CRUD, tìm kiếm, phát hiện và gộp hồ sơ khách hàng trùng  
**Môi trường đã thử:** Windows, PowerShell, XAMPP/PHP, MySQL 8, React/Vite; Docker Desktop (BT3: `db` mysql:8.0, `api` php:8.3, `web` nginx)  
**Cơ sở dữ liệu kiểm thử:** `smartcrm_bt2_clean_clone` (không phải `smartcrm` gốc)

## 1. Mục đích và nguyên tắc ghi nhận

Tài liệu này ghi lại **lỗi hoặc trở ngại đã thực sự xuất hiện** trong quá trình phát triển, dựng lại môi trường và thử nghiệm BT2. Phân biệt rõ **lỗi mã nguồn** với **vấn đề cấu hình/vận hành/Git**; không xem các tình huống kiểm thử đúng kỳ vọng (ví dụ danh sách hồ sơ trùng rỗng sau khi gộp thành công) là lỗi. Không đưa mật khẩu, CSRF token hoặc thông tin nhạy cảm vào tài liệu và ảnh công khai.

## BT3 – Bug đã sửa (mẫu bảy dòng, buổi 13–16)

Mỗi bug được phát hiện bởi một test case trong [`docs/test-report.md`](test-report.md), gỡ theo bốn bước (tái lập · khoanh vùng · sửa · xác nhận) và sửa bằng **một commit riêng**. Mức độ phân loại theo ba câu hỏi: người dùng có làm được việc không · dữ liệu có bị sai hoặc bị lộ không · có cách làm tạm không.

### BUG-02 · Critical – API đọc khách hàng không yêu cầu đăng nhập

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-02 · Critical.** Dữ liệu cá nhân bị **lộ** (họ tên, email, địa chỉ khách hàng) cho người không đăng nhập; đây là lỗ hổng bảo mật (OWASP A01 – Broken Access Control), không có cách làm tạm phía người dùng. |
| 2. Phát hiện bởi | **TC-10** (test bảo mật) – lần chạy 1 ngày 06/11/2026 trên Windows, commit `a5dccb1`; ảnh [`TC-10.png`](screenshots/TC-10.png). |
| 3. Triệu chứng | Không có cookie phiên, `GET /api/customers`, `GET /api/customers/1`, `GET /api/customers/duplicates` đều trả **HTTP 200** kèm danh sách/hồ sơ khách hàng; mong đợi HTTP 401 `UNAUTHORIZED`. |
| 4. Cách tái lập | Dựng hệ thống theo README → chạy `& "C:\xampp\php\php.exe" tests/run_bt3_cases.php TC-10`; hoặc khi backend đang chạy, mở trình duyệt ẩn danh vào `http://127.0.0.1:8000/api/customers`. |
| 5. Nguyên nhân | Trong `src/public/index.php`, nhánh `GET` của ba route `/api/customers`, `/api/customers/duplicates`, `/api/customers/{id}` gọi thẳng `CustomerController` mà **không gọi `RequestGuard::requireLogin()`**; chỉ các nhánh `POST/PUT/DELETE` có `requireRole()`. NFR2 trong SRS chỉ nêu "API thay đổi dữ liệu", nên khi hiện thực đã bỏ sót kiểm soát truy cập cho API đọc, trái với BR4 (QT-14). |
| 6. Cách sửa | Thêm `RequestGuard::requireLogin();` vào đầu ba nhánh `GET` trong `src/public/index.php` (sửa ở router vì đây là lớp đang chịu trách nhiệm xác thực cho mọi route khác; controller và service không đổi). Giao diện chỉ gọi các API này sau khi đăng nhập nên không bị ảnh hưởng. |
| 7. Commit và xác nhận | [`e6a16aa`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/e6a16aa26feaa0430954b1f83f97f6f69e7aacae) · Chạy lại TC-10: cả 3 route trả **HTTP 401** → **PASS** ([`BUG-02-fixed.png`](screenshots/BUG-02-fixed.png)). Chạy lại toàn bộ `tests/run_all.php`: unit 34/34, integration 58/58, BT3 12/12 – `ALL TEST SUITES PASSED` ([`run-all-after-fix.png`](screenshots/run-all-after-fix.png)). |

### BUG-03 · Critical – Số điện thoại `+84…` / `84…` không được chuẩn hoá theo QT-02

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-03 · Critical.** Dữ liệu bị **sai**: khoá chống trùng `phone_normalized` lưu sai định dạng, nên có thể tạo **hồ sơ trùng** cho cùng một khách hàng (vi phạm BR1/QT-01) và chức năng phát hiện trùng (FR4) bỏ sót cặp này. Cách làm tạm (nhân viên luôn tự gõ dạng `0…`) không được hệ thống bảo đảm. |
| 2. Phát hiện bởi | **TC-09** (test chất lượng dữ liệu QT-02) – lần chạy 1 ngày 06/11/2026 trên Windows, commit `a5dccb1`; ảnh [`TC-09.png`](screenshots/TC-09.png). |
| 3. Triệu chứng | `"+84 901 234 567"` và `"84901234567"` được lưu `phone_normalized = "84901234567"`; trong khi `"0901234567"` lưu `"0901234567"`. Cùng một số nhưng hai khoá khác nhau. |
| 4. Cách tái lập | `& "C:\xampp\php\php.exe" tests/run_bt3_cases.php TC-09`; hoặc qua API: tạo khách hàng với `phone: "0901234567"`, rồi tạo tiếp với `phone: "+84 901 234 567"` → lần hai trả 201 thay vì 409. |
| 5. Nguyên nhân | `CustomerService::normalizePhone()` (`src/services/CustomerService.php`) chỉ chạy `preg_replace('/[^0-9]/', '', $phone)` – xoá ký tự không phải chữ số – mà **không chuyển mã quốc gia `84` về `0`** như QT-02 yêu cầu. Vì `findByPhone()` so sánh bằng `phone_normalized`, hai dạng của cùng một số không bao giờ khớp nhau. |
| 6. Cách sửa | Trong `normalizePhone()`, sau khi xoá ký tự không phải số: nếu chuỗi có **11 chữ số và bắt đầu bằng `84`** thì đổi thành `0` + 9 chữ số còn lại. Điều kiện 11 chữ số giữ nguyên các số không phải của Việt Nam (ví dụ `+856 20 1234 5678`) và không đổi quy tắc 8–15 chữ số của validator. Chỉ sửa ở service vì đây là nơi duy nhất sinh `phone_normalized`. |
| 7. Commit và xác nhận | [`1b9e87e`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/1b9e87e415da1de402dfc686774ce5acbce31495) · Chạy lại TC-09: cả 4 giá trị lưu `"0901234567"` → **PASS** ([`BUG-03-fixed.png`](screenshots/BUG-03-fixed.png)). Toàn bộ `tests/run_all.php` PASS ([`run-all-after-fix.png`](screenshots/run-all-after-fix.png)). **Giới hạn:** bản sửa áp dụng cho dữ liệu nhập mới; hồ sơ đã lưu trước đó với `84…` (nếu có) chưa được chuyển đổi. |

### BUG-04 · Major – Giao diện hiện lỗi thô "Unexpected end of JSON input" khi mất kết nối backend

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-04 · Major.** Trạng thái **lỗi kết nối** – một trong ba trạng thái bắt buộc của giao diện (đề BT2, thành phần 3) – hiển thị thông báo kỹ thuật của trình duyệt, người dùng không biết nguyên nhân và cách xử lý. Dữ liệu không bị sai hay lộ (không phải Critical); có cách làm tạm là bấm "Thử lại" sau khi backend chạy lại. |
| 2. Phát hiện bởi | Tình huống kiểm thử "tắt một thành phần phụ trợ rồi thử lại nghiệp vụ chính" (đề BT2 mục 4.3 bước 5): tắt backend PHP khi giao diện đang mở, ngày 06/11/2026 trên Windows + Chrome, commit `a4225b7`; ảnh [`BUG-04-before.png`](screenshots/BUG-04-before.png). |
| 3. Triệu chứng | Trang "Danh sách khách hàng" hiện chữ đỏ `Failed to execute 'json' on 'Response': Unexpected end of JSON input` kèm nút "Thử lại"; trang "Hồ sơ trùng" hiện cùng thông báo. |
| 4. Cách tái lập | 1. Terminal 1: `& "C:\xampp\php\php.exe" -S 127.0.0.1:8000 -t src/public src/public/index.php`. 2. Terminal 2: `cd frontend; npm run dev`, mở `http://127.0.0.1:5173`, đăng nhập. 3. Bấm `Ctrl + C` ở Terminal 1 để tắt backend. 4. Bấm "Tìm kiếm". |
| 5. Nguyên nhân | Khi backend tắt, proxy của Vite trả HTTP 5xx với **thân phản hồi rỗng**. Hàm `request()` trong `frontend/src/api/client.js` gọi `response.json()` **trước khi kiểm tra `response.ok`**, nên `json()` ném `SyntaxError` và thông báo thô này đi thẳng lên giao diện thay cho thông báo của ứng dụng. `frontend/src/api/merge.api.js` tự gọi `fetch` + `json()` riêng (không qua lớp client), nên có cùng lỗi. |
| 6. Cách sửa | Trong `client.js`: thêm `parseResponse()` đọc thân phản hồi bằng `text()`, chỉ `JSON.parse` khi có nội dung; phản hồi lỗi không có JSON → thông báo "Không thể kết nối đến hệ thống. Vui lòng kiểm tra kết nối và thử lại." (5xx) hoặc "Yêu cầu thất bại (HTTP …)"; thêm `send()` đổi lỗi mạng của `fetch` thành cùng thông báo. Phản hồi lỗi có JSON vẫn hiển thị `message` của backend như cũ. `merge.api.js` chuyển sang dùng `getCsrfToken()`, `send()`, `parseResponse()` của lớp client, giữ nguyên giá trị trả về nên `DuplicatesPage.jsx` không phải sửa. |
| 7. Commit và xác nhận | [`260d285`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/260d28516563b62d4fa48bcf1b7605ee099aa627) · Tái lập lại đúng các bước ở dòng 4: trang hiện "Không thể kết nối đến hệ thống. Vui lòng kiểm tra kết nối và thử lại." kèm nút "Thử lại" ([`BUG-04-fixed.png`](screenshots/BUG-04-fixed.png)). Bản sửa chỉ thay đổi frontend; backend và `tests/run_all.php` không bị ảnh hưởng. |

### BUG-05 · Major – API trả HTTP 500 trên Linux/container vì phụ thuộc biến `LOCALAPPDATA` của Windows

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-05 · Major.** Người khác **không dựng được hệ thống** ngoài Windows (Linux, macOS, container Docker) – vi phạm nguyên tắc TÁI LẬP ĐƯỢC và chặn thành phần 4 của BT3. Dữ liệu không sai, không lộ; có cách làm tạm (đặt `LOCALAPPDATA` thủ công) nên không phải Critical. |
| 2. Phát hiện bởi | Phép thử máy trắng: clone tag `v0.2` vào thư mục trống trên Linux (03/11/2026), tái lập lại trong điều kiện giống container ngày 13/11/2026, commit `e9f2ce2`. |
| 3. Triệu chứng | Mọi request (kể cả `GET /api/auth/csrf`) trả `HTTP/1.0 500 Internal Server Error`; log PHP: `PHP Fatal error: Uncaught RuntimeException: LOCALAPPDATA environment variable is missing in src/public/index.php:11`. |
| 4. Cách tái lập | Trên Linux/macOS (không có `LOCALAPPDATA`): `php -S 127.0.0.1:8090 -t src/public src/public/index.php` → `curl -i http://127.0.0.1:8090/api/auth/csrf`. |
| 5. Nguyên nhân | Phần cấu hình session ở đầu `src/public/index.php` **bắt buộc** biến `LOCALAPPDATA` (chỉ Windows có) và ghép đường dẫn bằng dấu `\` của Windows (`$localAppData . '\\SmartCRM\\sessions'`); đoạn này chạy **trước** khối `try` nên ngoại lệ không được bắt, PHP trả 500 cho mọi route. |
| 6. Cách sửa | Thư mục session lấy theo thứ tự: `SESSION_SAVE_PATH` (Docker đặt biến này) → `%LOCALAPPDATA%\SmartCRM\sessions` (giữ nguyên cách chạy Windows của README) → `<thư mục tạm>/smartcrm-sessions`; dùng `DIRECTORY_SEPARATOR` và tự tạo thư mục nếu chưa có. Chỉ sửa `src/public/index.php`. |
| 7. Commit và xác nhận | [`4f7513c`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/4f7513c7acd66e55e6456a08c9f2c69fee6e978a) · Chạy lại bước 4 không có `LOCALAPPDATA`: `{"data":{"csrf_token":"<token>"}} [HTTP 200]`, session được tạo trong `/tmp/smartcrm-sessions`. Chế độ Windows (`LOCALAPPDATA`) vẫn dùng `…\SmartCRM\sessions`. Trong container: `api` **healthy** ([`docker-02-ps-health.png`](screenshots/docker-02-ps-health.png)). |

### BUG-06 · Major – Không cấu hình được CSDL bằng biến môi trường (chỉ đọc tệp `.env`)

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-06 · Major.** Container Docker nhận cấu hình qua biến môi trường và không có tệp `.env` trong image (`.env` bị `.dockerignore` chặn để không lộ secret) → API, migration và seeder **không chạy được** trong container. Không sai/lộ dữ liệu. |
| 2. Phát hiện bởi | Tái lập điều kiện container ngày 13/11/2026 (không có tệp `.env`, biến `DB_*` đặt trong môi trường), commit `e9f2ce2`, khi chuẩn bị đóng gói Docker. |
| 3. Triệu chứng | `php tests/test_db.php` → `Connection failed: Khong tim thay file .env`; `php database/migrate.php smartcrm_bt2_docker` → `MIGRATION ERROR: Missing .env file` – dù `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` đã được đặt. |
| 4. Cách tái lập | Ở thư mục không có `.env`: `DB_HOST=127.0.0.1 DB_NAME=smartcrm_bt2_docker DB_USER=… DB_PASSWORD=… php tests/test_db.php`. |
| 5. Nguyên nhân | Năm nơi đọc cấu hình (`src/config/Database.php`, `database/migrate.php`, `database/seed_bt2.php`, `tests/run_user_integration.php`, `tests/run_bt3_cases.php`) đều **chỉ gọi `parse_ini_file('.env')`** và ném lỗi khi không có tệp; không nơi nào đọc biến môi trường. |
| 6. Cách sửa | Thêm `src/config/Env.php` (`Env::load()`): có tệp `.env` → dùng tệp (cách chạy Windows/XAMPP và bản sao tạm của bộ kiểm thử không đổi); không có → đọc các biến `DB_*`, `DEMO_*`, `SESSION_SAVE_PATH` từ môi trường. Năm nơi trên dùng chung `Env::load()`; bộ kiểm thử ghi `.env` cho bản sao tạm bằng `Env::toDotenv()`. |
| 7. Commit và xác nhận | [`7cf7b2c`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/7cf7b2cd84561e62f4117292e5c370804dc0755d) · Không có `.env`: `MySQL connection: SUCCESS`, migration `Tables created: 5`, `SEED SUCCESS`; toàn bộ `tests/run_all.php` PASS ở **cả hai** chế độ (có `.env` và chỉ biến môi trường). Trong container: `ALL TEST SUITES PASSED` ([`docker-03-tests-in-container.png`](screenshots/docker-03-tests-in-container.png)). |

### BUG-07 · Major – Container `db` dừng ngay (exit 1) khi máy đã có biến `DB_USER=root`

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-07 · Major.** Gói Docker **không khởi động được** trên máy có sẵn biến môi trường `DB_USER`/`DB_PASSWORD` (thường gặp ở máy đã cài XAMPP/MySQL) → mục cốt lõi C1 thất bại trên những máy đó. Có cách làm tạm (xoá biến khỏi phiên PowerShell) nên không phải Critical. |
| 2. Phát hiện bởi | **Phép thử máy trắng trên Windows** ngày 13/11/2026: clone nhánh `dev` (commit `dd07689`) vào thư mục mới `smartcrm-docker-check`, `cp .env.example .env`, `docker compose up -d --build`; ảnh [`BUG-07-before-up.png`](screenshots/BUG-07-before-up.png). |
| 3. Triệu chứng | `Container smartcrm-db-1 Error dependency db failed to start` · `container smartcrm-db-1 exited (1)`; `docker compose logs db`: `[ERROR] [Entrypoint]: MYSQL_USER="root", MYSQL_USER and MYSQL_PASSWORD are for configuring a regular user and cannot be used for the root user` ([`BUG-07-before-logs.png`](screenshots/BUG-07-before-logs.png)) – trong khi `.env` ghi `DB_USER=smartcrm`. |
| 4. Cách tái lập | Trong shell đặt `DB_USER=root` rồi `docker compose up -d`; hoặc không cần chạy container: `DB_USER=root docker compose config` → hiện `MYSQL_USER: root`. |
| 5. Nguyên nhân | `docker-compose.yml` truyền cấu hình bằng **nội suy `${DB_USER}`**, mà Docker Compose **ưu tiên biến môi trường của shell hơn tệp `.env`** khi nội suy. Máy Windows của sinh viên có sẵn biến `DB_USER` (= `root`) và `DB_PASSWORD` ([`BUG-07-host-env.png`](screenshots/BUG-07-host-env.png), chỉ hiện tên biến) nên giá trị trong `.env` bị ghi đè. |
| 6. Cách sửa | `db` và `api` đọc cấu hình bằng **`env_file: .env`** (giá trị lấy thẳng từ tệp, biến của máy chủ không ghi đè được); `db` ánh xạ `DB_*` → `MYSQL_*` ngay trong container trước khi chạy `docker-entrypoint.sh`; healthcheck dùng `DB_ROOT_PASSWORD`. Thêm `.env.example text eol=lf` vào `.gitattributes`. |
| 7. Commit và xác nhận | [`f74a1dc`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/f74a1dc9fda3be3b1e2dfb2807bad57e7e360fd0) · Cùng máy, vẫn còn biến `DB_USER=root`: `db`, `api`, `web` đều **healthy**, `/api/health` → `{"status":"ok","database":"ok"}` ([`docker-02-ps-health.png`](screenshots/docker-02-ps-health.png)), test trong container `ALL TEST SUITES PASSED`. `DB_USER=root docker compose config` nay cho `DB_USER: smartcrm`. |

### BUG-08 · Minor – Số điện thoại nhập dạng `+84…` hiển thị sai mẫu che (`84*******78`)

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-08 · Minor.** Chỉ ảnh hưởng **trình bày**: dữ liệu lưu đúng (`phone_normalized = 0912345678`, chống trùng vẫn đúng), số vẫn bị che, không lộ thêm chữ số; nhưng hồ sơ hiển thị khác mẫu `09******78` của các hồ sơ còn lại. |
| 2. Phát hiện bởi | Khi chạy thật mọi endpoint để lấy ví dụ cho `docs/api-docs.md` ngày 13/11/2026, commit `f74a1dc`: `POST /api/customers` với `"phone":"+84 901 234 567"`. |
| 3. Triệu chứng | Phản hồi trả `"phone":"84*******67"` thay vì `"09******67"`; tương tự `+84 912 345 678` → `84*******78`. |
| 4. Cách tái lập | Đăng nhập `SALES_STAFF` → `POST /api/customers {"full_name":"Le Thi Cam","phone":"+84 912 345 678"}` → `GET /api/customers?search=Cam`. |
| 5. Nguyên nhân | `CustomerController::maskCustomer()` che các chữ số của cột **`phone` (chuỗi người dùng nhập)** thay vì `phone_normalized`; BUG-03 chỉ sửa giá trị chuẩn hoá dùng để chống trùng, còn hàm che vẫn đọc chuỗi gốc có tiền tố `84`. |
| 6. Cách sửa | `maskCustomer()` dùng `phone_normalized` khi có (sau đó vẫn bị `unset` khỏi phản hồi), chỉ quay về `phone` khi không có. Chỉ sửa `src/controllers/CustomerController.php`. |
| 7. Commit và xác nhận | [`fce04ce`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/fce04ce9e7e6244b05e2459da1adabcafe2128bf) · Cùng hồ sơ: trước `"phone":"84*******78"`, sau `"phone":"09******78"`; TC-02 (`09******02`) và toàn bộ `tests/run_all.php` vẫn PASS. Trên giao diện chạy bằng Docker: [`BUG-08-fixed.png`](screenshots/BUG-08-fixed.png). |

### BUG-09 · Major – Giao diện treo sau khi dịch vụ `api` khởi động lại (nginx giữ địa chỉ cũ của `api`)

| Dòng | Nội dung |
|---|---|
| 1. Mã và mức độ | **BUG-09 · Major.** Sau khi `api` được khởi động lại ở địa chỉ mới, **mọi chức năng không dùng được** (không đăng nhập được) cho tới khi có người khởi động lại `web` bằng tay. Dữ liệu không sai, không lộ; có cách làm tạm nên không phải Critical. |
| 2. Phát hiện bởi | Bản triển khai thử trên Railway ngày 13/11/2026 (commit `e5445f4`): sau khi xoá volume CSDL và triển khai lại `api` để đổi mật khẩu demo, mở lại trang web. |
| 3. Triệu chứng | Trang dừng ở "Đang kiểm tra phiên đăng nhập…" ([`BUG-09-before.png`](screenshots/BUG-09-before.png)), nút đăng nhập dừng ở "Đang đăng nhập…"; trong khi `api` **Online** và log `api` bình thường (`SEED SUCCESS`, `Starting PHP server on [::]:8000`, khởi động lúc 21:52, sau `web` 21:46). |
| 4. Cách tái lập | Trên nền tảng cấp địa chỉ mới cho container mỗi lần triển khai: (1) chạy `web` và `api`; (2) triển khai lại riêng `api`; (3) mở trang web → treo. Bấm Restart cho `web` thì hết. |
| 5. Nguyên nhân | `frontend/nginx.conf.template` dùng `proxy_pass http://${API_UPSTREAM};` với tên máy cố định: nginx **chỉ phân giải tên `api` một lần lúc khởi động** và giữ địa chỉ đó mãi. Khi `api` có địa chỉ mới, nginx vẫn gửi yêu cầu tới địa chỉ cũ không còn ai nhận. |
| 6. Cách sửa | Trong `location /api/`: khai báo `resolver ${NGINX_LOCAL_RESOLVERS} valid=10s;` và đưa tên máy vào biến (`set $smartcrm_api …; proxy_pass http://$smartcrm_api;`) để nginx phân giải lại tên sau mỗi 10 giây. `frontend/Dockerfile` đặt `NGINX_ENTRYPOINT_LOCAL_RESOLVERS=1` để image nginx lấy máy chủ DNS của container. Chỉ sửa hai tệp này. |
| 7. Commit và xác nhận | [`7ef32bf`](https://github.com/Thepvinath/cdtn1-SouphavilayThepvinath-L1/commit/7ef32bf3e2b51235be47dc20b6b5338a93ce3428) · (a) Mô phỏng với nginx thật: đổi bản ghi DNS của `api` sang địa chỉ khác, sau 12 giây nginx tự chuyển sang địa chỉ mới, đường dẫn và query string giữ nguyên. (b) Docker trên Windows: `docker compose up -d --build` → `db`, `api` healthy; `docker compose restart api`, không đụng `web` → `/api/health` trả `{"status":"ok","database":"ok"}`. (c) Railway sau khi triển khai bản sửa: `/api/health` trả `200` ([`deploy-01-health.png`](screenshots/deploy-01-health.png)), đăng nhập được ([`deploy-03-customers-manager.png`](screenshots/deploy-03-customers-manager.png)). |

> Mục 2–6 dưới đây là nhật ký lỗi và vấn đề vận hành của **BT2**, giữ nguyên để truy vết.

## 2. Danh sách vấn đề

| Mã | Phân loại | Hiện tượng thực tế | Nguyên nhân/đánh giá | Cách xử lý đã thực hiện | Kết quả xác minh | Trạng thái |
|---|---|---|---|---|---|---|
| BUG-01 | Lỗi mã nguồn – CSS | Quá trình build/minify CSS gặp trở ngại do cú pháp không hợp lệ. | Có dấu `}` đóng dư trong `frontend/src/index.css` (được ghi nhận trong README BT2). | Loại bỏ dấu đóng dư khi phát triển User Management. | `npm.cmd --prefix frontend run lint` và `npm.cmd --prefix frontend run build` chạy thành công; Vite báo `built`. | Đã sửa |
| CFG-01 | Cấu hình môi trường – MySQL | Migration trên clean clone báo `SQLSTATE[HY000] [1045] Access denied for user 'YOUR_MYSQL_USERNAME'@'localhost'`. | `.env` vẫn giữ tên người dùng mẫu, chưa thay bằng tài khoản MySQL trên máy. | Chỉnh `DB_USER` và thông tin kết nối tương ứng trong `.env` cục bộ; không commit `.env`. | Migration `001_initial` thành công, tạo 5 bảng nghiệp vụ; chạy lại báo `SKIP`. Kiểm tra kết nối MySQL thành công, tổng 6 bảng kể cả `schema_migrations`. | Đã xử lý |
| CFG-02 | Dữ liệu khởi tạo / đăng nhập | Ở bước đầu dựng clean clone, bảng `users` chưa có tài khoản để đăng nhập ứng dụng; giao diện từng hiển thị thông báo đăng nhập sai. | Migration chỉ tạo cấu trúc bảng, không tự động tạo tài khoản. | Chạy `database/seed_bt2.php` trên database BT2 dành riêng; seeder tạo tài khoản demo cục bộ và dữ liệu khách hàng mẫu. | Trong MySQL có tài khoản demo `SALES_STAFF` và `STORE_MANAGER`; đăng nhập bằng tài khoản Manager thành công, mở được trang `Quản lý nhân viên`. | Đã xử lý |
| OPS-01 | Vận hành demo – mật khẩu | Người thực hiện không nhớ mật khẩu demo Manager sau khi khởi tạo. | Seeder tạo mật khẩu ngẫu nhiên, chỉ hiển thị tại lần tạo tài khoản đầu tiên; chạy lại không đổi mật khẩu. | Kiểm tra hướng dẫn seeder và sử dụng lại thông tin đăng nhập đã lưu để tiếp tục kiểm thử; không công khai mật khẩu. | Tài khoản `STORE_MANAGER` đăng nhập và thao tác trên giao diện thành công. Chưa coi đây là lỗi xác thực của hệ thống. | Đã xử lý trong buổi thử |
| GIT-01 | Quy trình Git – minh chứng | Đã sao chép 5 ảnh PNG vào `docs/evidence/`, nhưng `git commit` báo `nothing added to commit but untracked files present`; `git push` báo `Everything up-to-date`. | Các file PNG mới đang ở trạng thái `??` (untracked), chưa được `git add`. | Xác định cần `git add` đúng 5 ảnh, kiểm tra `git diff --cached --stat`, sau đó commit và push. | Các ảnh đã được đưa vào nội dung nhánh `dev` và sau đó có trong bản merge lên `main` theo quy trình BT2. | Đã xử lý |

## 3. Kiểm tra hồi quy sau khi xử lý

| Nội dung kiểm tra | Kết quả đã quan sát |
|---|---|
| PHP unit tests (`tests/run_unit_tests.php`) | **34 passed, 0 failed** |
| HTTP integration (`tests/run_user_integration.php`) | **58 passed, 0 failed** |
| Frontend ESLint | Đã chạy không báo lỗi trong lần kiểm tra BT2 |
| Frontend production build | Vite thông báo `built` thành công |
| Migration idempotency | Lần đầu `SUCCESS`, chạy lại `SKIP: 001_initial already applied.` |
| MySQL và User CRUD | Trang `Quản lý nhân viên` tạo, đọc, cập nhật role và vô hiệu hóa người dùng; kết quả được đối chiếu trong MySQL |
| Gộp hồ sơ khách hàng | Sau khi gộp, Master ở `ACTIVE`, Source ở `MERGED`; có bản ghi lịch sử gộp trong MySQL |

**Dữ liệu đối chiếu User CRUD:** Trong `smartcrm_bt2_clean_clone`, các bản ghi quan sát gồm demo Sales (`MARKETING`, `is_active = 0`), demo Manager (`STORE_MANAGER`, `is_active = 1`) và một tài khoản kiểm thử mới (`MARKETING`, `is_active = 1`). Không dùng các địa chỉ email cá nhân trong báo cáo công khai.

## 4. Trường hợp không được ghi thành bug

- Tìm kiếm với từ khóa không có trong dữ liệu trả về danh sách rỗng là **Empty State đúng yêu cầu**.
- Sau khi gộp thành công một cặp hồ sơ trùng, trang `Hồ sơ khách hàng nghi trùng` không còn hiển thị cặp đó là **kết quả đúng**; cần bộ dữ liệu thử mới nếu muốn demo lại.
- Seeder chạy lại và không nhân đôi hồ sơ/tài khoản là **hành vi idempotent đúng thiết kế**.
- Nút tự vô hiệu hóa tài khoản Manager bị khóa trên giao diện là **cơ chế phòng vệ**, không phải lỗi.

## 5. Giới hạn kiểm chứng và công việc theo dõi

- **Chưa có bằng chứng** kiểm thử tải đồng thời nhiều request / cạnh tranh giao dịch và UI tự động bằng trình duyệt (browser E2E).
- **Chưa đo** thời gian đáp ứng NFR tìm kiếm trên tập dữ liệu 65.000 khách hàng; không đánh dấu là đã đạt chỉ dựa trên unit/integration tests.
- Khi nộp BT2, kiểm tra lại README, đường dẫn ảnh trong `docs/evidence/`, AI disclosure và PDF báo cáo trên commit/tag dự định nộp.
- Nếu tag `v0.2` đã được đẩy lên remote trước khi bổ sung tài liệu này, cần thống nhất cách phát hành phiên bản cập nhật trước khi thay đổi tag đã công bố.

## 6. Minh chứng tham chiếu

- `README.md`: hướng dẫn dựng clean clone, kết quả kiểm thử, ghi nhận sửa lỗi CSS và giới hạn kiểm chứng.
- `docs/evidence/bt2-01-migration-success.png` đến `bt2-04-database-connection.png`: migration và kết nối môi trường.
- `docs/evidence/bt2-05-login-success.png` đến `bt2-13-merge-history.png`: demo Customer, tìm kiếm và gộp hồ sơ.
- `docs/evidence/bt2-14-user-create-success.png` đến `bt2-18-user-database-verification.png`: User CRUD và đối chiếu MySQL.
- Terminal/ảnh trong quá trình phát triển: lỗi đăng nhập MySQL với placeholder, kết quả kiểm thử 34/34 và 58/58, thông báo Git về file untracked.

> **Ghi chú:** Nội dung được tổng hợp từ các lần chạy và ảnh đã ghi nhận; không khẳng định mọi lỗi đều có ảnh riêng trong repository. Người thực hiện cần kiểm tra lại tài liệu trước khi nộp.
