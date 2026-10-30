# NHẬT KÝ LỖI VÀ VẤN ĐỀ THỰC TẾ – BT2 (BUG LOG)

**Dự án:** Smart CRM – Mekong Mobile  
**Học phần:** Chuyên đề tốt nghiệp 1 – Track Software Engineering (SE)  
**Phạm vi BT2:** Customer CRUD, User CRUD, tìm kiếm, phát hiện và gộp hồ sơ khách hàng trùng  
**Môi trường đã thử:** Windows, PowerShell, XAMPP/PHP, MySQL 8, React/Vite  
**Cơ sở dữ liệu kiểm thử:** `smartcrm_bt2_clean_clone` (không phải `smartcrm` gốc)

## 1. Mục đích và nguyên tắc ghi nhận

Tài liệu này ghi lại **lỗi hoặc trở ngại đã thực sự xuất hiện** trong quá trình phát triển, dựng lại môi trường và thử nghiệm BT2. Phân biệt rõ **lỗi mã nguồn** với **vấn đề cấu hình/vận hành/Git**; không xem các tình huống kiểm thử đúng kỳ vọng (ví dụ danh sách hồ sơ trùng rỗng sau khi gộp thành công) là lỗi. Không đưa mật khẩu, CSRF token hoặc thông tin nhạy cảm vào tài liệu và ảnh công khai.

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
