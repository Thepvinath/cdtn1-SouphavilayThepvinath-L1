# NHẬT KÝ THAY ĐỔI THIẾT KẾ

**Dự án:** Smart CRM – Mekong Mobile  
**Luồng nghiệp vụ:** L1 – Tạo, tra cứu, gộp hồ sơ khách hàng trùng  
**Track:** Software Engineering (SE)  
**Sinh viên:** Thepvinath Souphavilay  
**MSSV:** 237480201IS03  
**Giai đoạn:** Sau phản hồi Bài tập 1 – Buổi 8, ngày 19/10/2026

## 1. Tổng hợp kết quả Bài tập 1

- **Điểm BT1:** 8/10.
- **Điểm mạnh được giảng viên ghi nhận:** bảng truy vết FR–US–UC–API đầy đủ; ERD có 5 bảng và 5 khóa ngoại; có các lập luận kiến trúc gắn NFR; Use Case Diagram có chú thích ký hiệu.
- **Nguyên tắc điều chỉnh:** bảo toàn phạm vi L1, cấu trúc dữ liệu và các thành phần đã được đánh giá tốt; chỉ làm rõ các điểm thiếu hoặc chưa nhất quán.

## 2. Quyết định sau phản hồi của giảng viên

### TD-01 – Bổ sung chú thích sơ đồ kiến trúc và phạm vi loại trừ
- **Phản hồi của GV:** Sơ đồ kiến trúc chưa có chú thích ký hiệu và dòng “NGOÀI PHẠM VI”.
- **Quyết định:** SỬA.
- **Lý do:** Giúp người đọc hiểu vai trò các lớp, chiều phụ thuộc và ranh giới chức năng L1.
- **Ảnh hưởng:** Bổ sung legend, khung “NGOÀI PHẠM VI” vào Architecture Diagram và cập nhật hình tương ứng trong báo cáo; không thay đổi các lớp chính PHP/MySQL.

### TD-02 – Bổ sung chú thích ký hiệu cho ERD
- **Phản hồi của GV:** ERD chưa có chú thích ký hiệu.
- **Quyết định:** SỬA.
- **Lý do:** Làm rõ PK, FK, UK và ký hiệu quan hệ; giữ nguyên mô hình 5 bảng, 5 khóa ngoại được giảng viên ghi nhận.
- **Ảnh hưởng:** Bổ sung legend trong ERD và thay hình trong báo cáo; không thay đổi DDL/MySQL chỉ vì yêu cầu trình bày.

### TD-03 – Chuẩn hóa luồng ngoại lệ UC5
- **Phản hồi của GV:** Luồng ngoại lệ UC5 nên đánh số theo bước, ví dụ 8a, 8b.
- **Quyết định:** SỬA.
- **Lý do:** Cho phép truy vết lỗi tới chính bước tương ứng trong luồng chính; làm rõ thao tác rollback khi gộp thất bại.
- **Ảnh hưởng:** Điều chỉnh mục UC5 trong `docs/srs.md`, bổ sung các nhánh 6a, 8a, 9a; không đổi mục tiêu nghiệp vụ UC5.

### TD-04 – Cụ thể hóa NFR1 về hiệu năng
- **Phản hồi của GV:** NFR1 cần nêu quy mô tập dữ liệu, ví dụ dưới 2 giây với 65.000 hồ sơ.
- **Quyết định:** SỬA.
- **Lý do:** Một ngưỡng thời gian chỉ có ý nghĩa kiểm thử khi có quy mô dữ liệu và điều kiện thử nghiệm xác định.
- **Ảnh hưởng:** Bổ sung điều kiện tối đa 65.000 hồ sơ, ngưỡng ≤ 2 giây và phương pháp kiểm chứng trong `docs/srs.md`; đây là yêu cầu mục tiêu, chưa phải kết quả đo thực tế.

### TD-05 – Hoàn thiện phần giao diện và tinh gọn báo cáo
- **Phản hồi của GV:** Mục 5.4.2 bị cắt dở; trang “ĐÂY LÀ PHẦN TEST SQL” không cần đưa vào bài.
- **Quyết định:** SỬA.
- **Lý do:** Báo cáo cần trình bày đầy đủ và chỉ giữ lại nội dung đúng mục đích tài liệu phân tích, thiết kế.
- **Ảnh hưởng:** Hoàn thiện mục 5.4.2 và loại bỏ trang test SQL khỏi báo cáo Word/PDF; không xóa mã kiểm tra hoặc tệp SQL cần cho phát triển.

### TD-06 – Cụ thể hóa phương pháp kiểm chứng khi sử dụng AI
- **Phản hồi của GV:** Khai báo AI còn chung chung, cần ghi rõ đã kiểm chứng từng phần như thế nào.
- **Quyết định:** SỬA.
- **Lý do:** Tăng tính minh bạch; phân biệt nội dung do AI đề xuất với phần sinh viên trực tiếp đối chiếu hoặc thực nghiệm.
- **Ảnh hưởng:** Bổ sung cột “Phương pháp kiểm chứng” theo từng hạng mục trong phụ lục và `docs/ai-disclosure.md`; chỉ khẳng định các phép kiểm tra thực sự đã thực hiện.

### TD-07 – Chuẩn hóa tệp nguồn sơ đồ trong repository
- **Phản hồi của GV:** Repo chưa có file `.drawio` gốc; tên file nên đúng mẫu, không có khoảng trắng.
- **Quyết định:** SỬA.
- **Lý do:** Cho phép giảng viên chỉnh sửa sơ đồ, truy vết nguồn và mở tệp thống nhất giữa các máy.
- **Ảnh hưởng:** Đưa các file `.drawio` gốc của Architecture, ERD và Wireframe vào `docs/` và đặt tên chuẩn; có thể giữ PNG làm ảnh xem nhanh. Kiểm tra thực tế bằng cách mở các file trong draw.io trước khi đánh dấu hoàn tất.

### TD-08 – Giữ nguyên thiết kế lõi đã được đánh giá tốt
- **Phản hồi của GV:** Bảng truy vết đầy đủ; ERD 5 bảng/5 FK có lịch sử gộp; các lập luận kiến trúc gắn NFR hợp lý.
- **Quyết định:** GIỮ NGUYÊN.
- **Lý do:** Các thiết kế hiện tại đáp ứng mục tiêu L1, không nên tạo thay đổi mô hình dữ liệu hoặc chức năng ngoài phạm vi chỉ để sửa vấn đề trình bày.
- **Ảnh hưởng:** Giữ nguyên FR1–FR5, các thành phần liên quan, 5 bảng và ràng buộc khóa ngoại trong `database/schema.sql` làm cơ sở phát triển BT2.

## 3. Hướng hiện thực Buổi 8 – Sprint 1

- **Module đầu tiên:** quản lý hồ sơ khách hàng (`customers`) bằng PHP + MySQL.
- **Tách lớp:** `src/controllers/` (HTTP/JSON) → `src/services/` (quy tắc nghiệp vụ) → `src/repositories/` (SQL/PDO); có `src/validators/` và lớp cấu hình kết nối MySQL.
- **Quy tắc nghiệp vụ ưu tiên:** kiểm tra trường bắt buộc, chuẩn hóa số điện thoại; không tạo hồ sơ mới khi số điện thoại chuẩn hóa đã tồn tại (BR1, BR2).
- **Kiểm chứng:** chạy migration an toàn hai lần, seed có dữ liệu thử, gọi ít nhất hai API thật và lưu ảnh kết quả; cập nhật `README.md`.
- **Giai đoạn tiếp theo:** triển khai và kiểm thử MergeService, trong đó lỗi khi gộp phải rollback nguyên giao dịch (NFR4). Test nghiệp vụ gồm luồng đúng, giá trị biên, dữ liệu sai.

## 4. Trạng thái kiểm chứng

Nội dung trên là nhật ký quyết định thiết kế. Việc một mục có trong nhật ký không tự động chứng minh rằng tệp đã được commit hoặc test đã PASS. Khi hoàn thành mỗi mục, kiểm tra lại mã nguồn, ảnh kết quả, nhật ký Git và bổ sung bằng chứng tương ứng vào repo.

## 5. Quyết định sau phản hồi Bài tập 2 – Buổi 14

- **Kết quả BT2:** Giảng viên xác nhận đạt đủ các mục kiểm chứng chung và mục theo track SE (bảng 4.2 đề BT2).
- **Điểm mạnh được ghi nhận:** React + PHP + MySQL; CRUD khách hàng (xóa mềm) và người dùng (vô hiệu hóa); phát hiện và gộp hồ sơ trùng theo SĐT chuẩn hóa trong giao dịch; đăng nhập; ảnh chạy thật đối chiếu MySQL Workbench; commit đều.
- **Cần khắc phục:** (1) nộp `.docx` thay vì một tệp PDF; (2) README cần ghi rõ lệnh chạy kiểm thử.

### TD-09 – Nộp một tệp PDF thay vì .docx
- **Phản hồi của GV:** Nộp .docx thay vì một tệp PDF.
- **Quyết định:** SỬA (quy trình nộp bài).
- **Lý do:** Đề bài yêu cầu nộp một tệp PDF duy nhất, đặt tên `BT<n>_<MSSV>_<Họ tên không dấu>.pdf`.
- **Ảnh hưởng:** Không ảnh hưởng mã nguồn. Từ BT3, báo cáo được xuất ra `BT3_237480201IS03_ThepvinathSouphavilay.pdf`; mở lại tệp PDF để kiểm tra trước khi nộp lên VLU E-learning.

### TD-10 – README ghi rõ lệnh chạy kiểm thử
- **Phản hồi của GV:** README cần ghi rõ lệnh chạy kiểm thử.
- **Quyết định:** SỬA.
- **Lý do:** Lệnh kiểm thử trước đây nằm rải rác trong mục 3.6, lẫn với lệnh kiểm tra kết nối và lint/build; người chấm không thấy ngay một lệnh duy nhất. BT3 (mục C3) yêu cầu bộ kiểm thử chạy PASS bằng một lệnh.
- **Ảnh hưởng:** Thêm `tests/run_all.php` chạy lần lượt `tests/run_unit_tests.php` và `tests/run_user_integration.php`, trả mã thoát 0/1. README mục 3.6 đổi thành "Chạy kiểm thử" với đúng một lệnh `& "C:\xampp\php\php.exe" tests/run_all.php` và kết quả mong đợi; mục 5 và cây thư mục tham chiếu cùng lệnh. Test Plan (`docs/test-plan.md`) dùng cùng lệnh này.

### TD-11 – Giữ nguyên thiết kế lõi BT2
- **Quyết định:** GIỮ NGUYÊN.
- **Lý do:** GV xác nhận đạt đủ mục kiểm chứng; không có nhận xét về kiến trúc, mô hình dữ liệu hay nghiệp vụ.
- **Ảnh hưởng:** Giữ nguyên phạm vi L1, 5 bảng nghiệp vụ và kiến trúc tách lớp. Các lỗi tự phát hiện khi kiểm thử BT3 được ghi vào `docs/bug-log.md` và sửa theo từng commit riêng, không ghi thành thay đổi thiết kế.

## 6. Thay đổi so với BT2 – Buổi 15–16 (đóng gói và bảo mật)

Mỗi thay đổi ghi ba ý: thay đổi gì · vì sao · ảnh hưởng tới thành phần nào. Các lỗi sửa trong giai đoạn này (BUG-02 … BUG-08) được ghi chi tiết ở `docs/bug-log.md`; mục này chỉ ghi những thay đổi về cấu trúc, cấu hình và cách chạy hệ thống.

### TD-12 – Mật khẩu tài khoản demo cấu hình được qua `.env`
- **Thay đổi:** `database/seed_bt2.php` dùng `DEMO_SALES_PASSWORD` / `DEMO_MANAGER_PASSWORD` (≥ 8 ký tự) khi **tạo mới** tài khoản demo; để trống thì giữ cách cũ (mật khẩu ngẫu nhiên in một lần). Tài khoản đã có không bao giờ bị đổi mật khẩu.
- **Vì sao:** người chấm chạy gói Docker không đọc được mật khẩu ngẫu nhiên chỉ in một lần trong log container; mục C1 yêu cầu dựng và kiểm tra hệ thống mà không phải hỏi sinh viên.
- **Ảnh hưởng:** `database/seed_bt2.php`, `.env.example`, Deployment Guide mục 3–4, README mục 0 và 3.3/3.5.

### TD-13 – Thêm endpoint `GET /api/health`
- **Thay đổi:** endpoint không cần đăng nhập, chạy `SELECT 1`; trả 200 `{"status":"ok","database":"ok"}` hoặc 503 `{"status":"error","database":"unavailable"}`, không trả chi tiết lỗi.
- **Vì sao:** healthcheck của Docker và mục "Verify it works" của Deployment Guide cần một cách kiểm tra hệ thống đã lên mà không phải đăng nhập.
- **Ảnh hưởng:** `src/public/index.php`; `docker-compose.yml` (healthcheck `api`, `web`); `docs/api-docs.md` mục 2.1. Không đổi FR1–FR5.

### TD-14 – Đóng gói Docker: ba dịch vụ `db` · `api` · `web`
- **Thay đổi:** thêm `Dockerfile` (php:8.3-cli-alpine + pdo_mysql, chạy bằng `www-data`), `frontend/Dockerfile` (build Vite → nginx:1.27-alpine, proxy `/api`), `docker-compose.yml` (mysql:8.0 không mở cổng ra ngoài, healthcheck cả ba dịch vụ, `api` chờ `db` healthy, `web` chờ `api` healthy), `docker/api-entrypoint.sh` (migrate → seed → PHP server), `docker/mysql-init/` (cấp quyền cho bộ kiểm thử tạo CSDL tạm `smartcrm_bt2_*`), `.dockerignore`, `.gitattributes` (tệp `.sh` luôn LF). Cấu hình đọc từ `.env` (`env_file`) hoặc biến môi trường qua `src/config/Env.php` (BUG-06, BUG-07); thư mục session cấu hình được (BUG-05).
- **Vì sao:** BT3 thành phần 4 / mục cốt lõi C1 – hệ thống phải dựng lại được từ thư mục trống bằng ≤ 2 lệnh; README bằng lời (mức BT2) không đạt.
- **Ảnh hưởng:** thêm tệp hạ tầng nêu trên; `.env.example` đổi giá trị mẫu (`DB_NAME=smartcrm_bt2_docker`, `DB_USER=smartcrm`) và thêm `DB_ROOT_PASSWORD`, `WEB_PORT`, `DEMO_*`. Kiến trúc lớp (Controller → Service/Validator → Repository) và 5 bảng nghiệp vụ **không đổi**; cách chạy thủ công Windows/XAMPP của BT2 vẫn dùng được (README mục 3).

### TD-15 – Gia cố cấu hình bảo mật của gói Docker
- **Thay đổi:** image `api` thêm `zz-smartcrm.ini` (`display_errors=Off`, `log_errors=On`, `expose_php=Off`); nginx `server_tokens off` và định dạng access log dùng `$uri` (không ghi query string).
- **Vì sao:** checklist bảo mật mục 5 (thông báo lỗi không lộ cấu trúc, không lộ phiên bản PHP) và mục 6 (log không ghi từ khoá tìm kiếm có thể là tên hoặc SĐT khách hàng).
- **Ảnh hưởng:** `Dockerfile`, `frontend/nginx.conf`; `docs/checklist-bao-mat.md`. Không đổi hành vi API.

### TD-16 – Tài liệu bàn giao BT3
- **Thay đổi:** thêm `docs/deployment-guide.md`, `docs/api-docs.md` (tiếng Anh, 17 endpoint có ví dụ chạy thật), `docs/checklist-bao-mat.md`; `docs/api-contract.md` của BT1 được giữ để truy vết, **`docs/api-docs.md` là tài liệu API hiện hành**. README chuyển sang BT3 (mục 0 – hai lệnh Docker).
- **Vì sao:** BT3 thành phần 5 và 6; tài liệu phải mô tả đúng hệ thống thật (C9).
- **Ảnh hưởng:** chỉ tài liệu.
