# TEST PLAN RÚT GỌN – BT3

**Dự án:** Smart CRM – Mekong Mobile  
**Luồng nghiệp vụ:** L1 – Tạo, tra cứu, gộp hồ sơ khách hàng trùng  
**Track:** Software Engineering (SE)  
**Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03  
**Hệ thống được kiểm thử:** bản đã nộp BT2 (tag `v0.2`) và các bản sửa lỗi tiếp theo trên nhánh `dev`

## 1. Phạm vi kiểm thử và phần chủ ý KHÔNG kiểm

### 1.1. Được kiểm thử

| Quy tắc / yêu cầu đã hiện thực | Nội dung | API / thành phần |
|---|---|---|
| FR1, US1 | Tạo hồ sơ khách hàng, kiểm tra dữ liệu đầu vào | `POST /api/customers`, `CustomerValidator` |
| FR2, US2 | Tra cứu theo họ tên hoặc số điện thoại | `GET /api/customers?search=` |
| BR1 (QT-01) | Không tạo hồ sơ mới khi số điện thoại đã tồn tại | `CustomerService::createCustomer` |
| BR2 (QT-02) | Chuẩn hóa số điện thoại về dạng `0xxxxxxxxx` trước khi lưu và so sánh | `CustomerService::normalizePhone` |
| BR6, FR5 | Gộp hồ sơ trùng: hồ sơ nguồn chuyển `MERGED`, có lịch sử gộp; không gộp hồ sơ vào chính nó | `POST /api/customers/merge`, `MergeService` |
| NFR2, BR4 | API đọc và thay đổi dữ liệu khách hàng/người dùng yêu cầu đăng nhập và đúng vai trò | `RequestGuard` |
| Tái lập | Migration và Seeder chạy lần hai không lỗi, không nhân đôi dữ liệu | `database/migrate.php`, `database/seed_bt2.php` |

### 1.2. Chủ ý KHÔNG kiểm ở BT3 (kèm lý do)

| Nội dung | Lý do |
|---|---|
| Kiểm thử giao diện tự động (Browser E2E) | Giao diện không chứa quy tắc nghiệp vụ, chỉ gọi API; quy tắc đã được kiểm ở tầng service/API. Ba trạng thái đang tải / rỗng / lỗi đã có ảnh minh chứng ở BT2 (`docs/evidence/bt2-19..21`). |
| NFR1 – tra cứu ≤ 2 giây với 65.000 hồ sơ | Đề BT3 không yêu cầu tối ưu hiệu năng; cần nạp bộ dữ liệu đầy đủ `customers_raw.csv`. Ghi là hạn chế đã biết, chưa có số đo. |
| NFR4 – rollback khi gộp thất bại giữa giao dịch | Không gây được lỗi ở giữa giao dịch mà không sửa mã nguồn. Kiểm chứng bằng đọc mã `MergeService::merge` (`beginTransaction` / `rollBack`) thay vì test tự động. |
| BR5 (QT-15) – Quản lý xem đầy đủ số điện thoại | Hiện hệ thống che số điện thoại cho MỌI vai trò; ngoại lệ cho Quản lý chưa được hiện thực. Ghi là hạn chế đã biết, không viết test PASS cho chức năng chưa có. |
| Kiểm thử tải đồng thời, kiểm thử xâm nhập | Ngoài phạm vi CĐTN1 theo đề BT3. |

## 2. Loại test và số lượng mỗi tầng

Tổng cộng **12 test case**, phân bố theo kim tự tháp kiểm thử (nhiều unit, ít API):

| Tầng | Số lượng | Test case | Công cụ |
|---|---:|---|---|
| Unit (không CSDL) | 5 | TC-04, TC-05, TC-06, TC-08, TC-09 | `tests/run_unit_tests.php` |
| API / HTTP integration (CSDL tạm thật) | 6 | TC-01, TC-02, TC-03, TC-07, TC-10, TC-11 | `tests/run_user_integration.php`, curl/Postman |
| Tái lập | 1 | TC-12 | Lệnh migration + seeder chạy hai lần |

Danh sách và nguồn sinh test case:

| Mã | Loại BT3 | Kiểm cái gì | Nguồn |
|---|---|---|---|
| TC-01 | Luồng đúng | Tạo khách hàng hợp lệ → 201, bảng `customers` thêm đúng 1 dòng | US1-AC1, FR1 |
| TC-02 | Luồng đúng | Tra cứu theo họ tên có trong hệ thống → 200, trả đúng hồ sơ | US2-AC1, FR2 |
| TC-03 | Luồng đúng | Gộp hai hồ sơ trùng SĐT → 200; nguồn `MERGED`; `merge_operations` thêm 1 dòng | US6-AC1, FR5, BR6 |
| TC-04 | Giá trị biên | Số điện thoại đúng 8 chữ số → được chấp nhận | Ràng buộc 8–15 chữ số |
| TC-05 | Giá trị biên | Số điện thoại 7 chữ số → bị từ chối | Ràng buộc 8–15 chữ số |
| TC-06 | Dữ liệu sai | Thiếu `full_name` → lỗi rõ ràng nêu trường thiếu, không tạo bản ghi | US1, validator |
| TC-07 | Dữ liệu sai | Tạo hồ sơ với SĐT đã tồn tại → 409, không tạo bản ghi mới | US1-AC2, BR1 (QT-01) |
| TC-08 | Dữ liệu sai | Gộp một hồ sơ vào chính nó → bị từ chối | US6-AC2, BR6 |
| TC-09 | Chất lượng dữ liệu | `+84 901 234 567`, `84901234567`, `0901.234.567` đều chuẩn hóa thành `0901234567` | BR2 (QT-02) |
| TC-10 | Bảo mật | Chưa đăng nhập gọi `GET /api/customers` → 401, không lộ dữ liệu khách hàng | NFR2, BR4, OWASP A01 |
| TC-11 | Bảo mật | Vai trò `SALES_STAFF` gọi `GET /api/users` → 403 | BR4, OWASP A01 |
| TC-12 | Tái lập | Chạy migration và seeder lần hai → không lỗi, số bản ghi không đổi | Đề BT2/BT3 (C5/C8) |

Kỹ thuật chọn dữ liệu thử:
- **Giá trị biên** cho độ dài số điện thoại (8–15 chữ số): thử 7 và 8 ở hai bên ranh giới dưới (TC-04, TC-05).
- **Phân vùng tương đương** cho định dạng số điện thoại theo QT-02: một giá trị đại diện cho mỗi dạng `+84…`, `84…`, có dấu chấm/khoảng trắng, dạng chuẩn `0…` (TC-09).

## 3. Tiêu chí thành công (đo được)

Kiểm thử được coi là ĐẠT khi đồng thời thỏa mãn:

1. **12/12 test case đã chạy thật**, mỗi test case có kết quả thực tế cụ thể và ảnh chụp `docs/screenshots/TC-xx.png`.
2. **Mỗi quy tắc nghiệp vụ đã hiện thực ở mục 1.1 có ≥ 1 test case**; BR1, BR2, BR6 có cả test tuân thủ và test vi phạm.
3. **0 bug mức Critical đang mở** trong `docs/bug-log.md`.
4. Sau khi sửa lỗi, lệnh `& "C:\xampp\php\php.exe" tests/run_all.php` kết thúc bằng `ALL TEST SUITES PASSED` (mã thoát 0).

PASS/FAIL được ghi trung thực. Test case FAIL sẽ được gỡ lỗi, ghi vào Bug Log, sửa bằng commit riêng và chạy lại.

## 4. Môi trường và dữ liệu thử

| Mục | Giá trị |
|---|---|
| Hệ điều hành | Windows, PowerShell |
| Runtime | PHP 8.x (XAMPP) với `PDO`, `pdo_mysql`, `mbstring`, `curl`; Node.js ≥ 20.19 cho frontend |
| CSDL | MySQL Server 8.0 |
| CSDL kiểm thử thủ công / API | `smartcrm_bt2_clean_clone`, dựng mới theo README mục 3.2–3.5 (migration + `database/seed_bt2.php`) |
| Dữ liệu mẫu | Seeder tạo 3 hồ sơ: `BT2 DEMO MASTER` và `BT2 DEMO SOURCE` cùng SĐT `0888802026` (cặp trùng cho TC-03), `BT2 DEMO NORMAL` SĐT `0888802027`; 2 tài khoản demo `SALES_STAFF` và `STORE_MANAGER` |
| CSDL kiểm thử tự động | `tests/run_user_integration.php` tự tạo database tạm `smartcrm_bt2_users_<ngẫu nhiên>`, tự nạp migration và dữ liệu cần thiết, và **xoá database đó khi kết thúc**; không dùng dữ liệu có sẵn trên máy |
| Dọn dữ liệu | Unit test không chạm CSDL (dùng đối tượng giả `NoDatabasePDO`). Khi cần chạy lại TC-03 (gộp) với cặp trùng chưa gộp: dựng database `smartcrm_bt2_*` mới theo README thay vì sửa tay dữ liệu cũ |
| Lệnh chạy toàn bộ test tự động | Windows/XAMPP: `& "C:\xampp\php\php.exe" tests/run_all.php` · Docker (BT3): `docker compose exec api php tests/run_all.php` – cùng bộ test, chạy trong container `api`, CSDL tạm tạo trong dịch vụ `db` |
| Kết quả và minh chứng | `docs/test-report.md`, ảnh trong `docs/screenshots/` đặt tên theo mã test |
