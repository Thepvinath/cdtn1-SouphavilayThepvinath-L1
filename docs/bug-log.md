# NHẬT KÝ LỖI (BUG LOG) – BT2 và BT3

**Dự án:** Smart CRM – Mekong Mobile  
**Học phần:** Chuyên đề tốt nghiệp 1 – Track Software Engineering (SE)  
**Phạm vi:** Customer CRUD, User CRUD, tìm kiếm, phát hiện và gộp hồ sơ khách hàng trùng  
**Môi trường đã thử:** Windows, PowerShell, XAMPP/PHP, MySQL 8, React/Vite  
**Cơ sở dữ liệu kiểm thử:** `smartcrm_bt2_clean_clone` (không phải `smartcrm` gốc)

## 1. Mục đích và nguyên tắc ghi nhận

Tài liệu này ghi lại **lỗi hoặc trở ngại đã thực sự xuất hiện** trong quá trình phát triển, dựng lại môi trường và thử nghiệm BT2. Phân biệt rõ **lỗi mã nguồn** với **vấn đề cấu hình/vận hành/Git**; không xem các tình huống kiểm thử đúng kỳ vọng (ví dụ danh sách hồ sơ trùng rỗng sau khi gộp thành công) là lỗi. Không đưa mật khẩu, CSRF token hoặc thông tin nhạy cảm vào tài liệu và ảnh công khai.

## BT3 – Bug đã sửa (mẫu bảy dòng, buổi 13–14)

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
