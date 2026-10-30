# KHAI BÁO SỬ DỤNG AI – BT1 & BT2 (AI DISCLOSURE)

**Đề tài:** Smart CRM – Mekong Mobile
**Học phần:** Chuyên đề tốt nghiệp 1 — Track Software Engineering (SE)
**Luồng nghiệp vụ:** L1 – Tạo, tra cứu, gộp hồ sơ khách hàng trùng
**Sinh viên:** Thepvinath Souphavilay · **MSSV:** 237480201IS03
**Cập nhật cho BT2:** 31/10/2026

## 1. Công cụ AI và mục đích sử dụng

| Công cụ | Mục đích và phạm vi sử dụng thực tế |
|---|---|
| ChatGPT (OpenAI) | Giải thích yêu cầu, phân tích SRS/Use Case, kiến trúc/ERD, hướng dẫn thao tác Git/MySQL, soạn nội dung README, Bug Log, báo cáo Word/PDF và tổ chức ảnh minh chứng. |
| Codex trong VS Code (OpenAI) | Hỗ trợ đọc, triển khai và rà soát mã nguồn BT2; bổ sung User Management (Controller, Service, Repository, Validator, React UI/API client), test liên quan, quy tắc bảo mật và hướng dẫn chạy/kiểm tra. |

Các công cụ đưa ra gợi ý hoặc tạo bản nháp; người thực hiện xem lại, điều chỉnh, chạy thử trên máy của mình và chịu trách nhiệm về nội dung nộp. Không mặc nhiên xem phản hồi của AI là bằng chứng thử nghiệm.

## 2. Các hạng mục được AI hỗ trợ và phương pháp kiểm chứng

| Hạng mục | AI hỗ trợ | Phương pháp kiểm chứng và bằng chứng hiện có |
|---|---|---|
| SRS, FR/NFR, User Stories, Use Case (BT1) | ChatGPT hỗ trợ xây dựng cấu trúc và diễn đạt tiếng Việt. | Đối chiếu phạm vi L1, phản hồi giảng viên và `docs/srs.md`; các mục TD-03, TD-04 trong `docs/thay-doi-thiet-ke.md` ghi nhận điều chỉnh. NFR hiệu năng 65.000 bản ghi là **mục tiêu**, chưa được đo đạt. |
| Architecture, ERD, SQL DDL, Wireframe | ChatGPT hỗ trợ thiết kế sơ đồ, nội dung và chú thích. | Đối chiếu cấu trúc PHP Controller/Service/Repository, MySQL tables và `database/migrations/001_initial.sql`; chạy migration thực tế, MySQL hiển thị 6 bảng gồm `schema_migrations`. Sơ đồ Architecture thực tế BT2 mô tả thêm User CRUD và đường giao dịch Merge dùng PDO trực tiếp. |
| Customer CRUD, Search, Duplicate, Merge | ChatGPT hỗ trợ giải thích logic, sửa lỗi và lên kịch bản thử. | Thao tác thật trên Frontend, đối chiếu bản ghi MySQL Master `ACTIVE` / Source `MERGED` và lịch sử Merge trong `merge_operations`/`merge_items`; ảnh `docs/evidence/bt2-05...13`. |
| User CRUD & security (BT2) | Codex hỗ trợ xây Controller/Service/Repository/Validator, màn hình Nhân viên, RBAC, CSRF, password hashing, khóa tự vô hiệu hóa và Manager cuối cùng. | Người thực hiện chạy giao diện thật với `STORE_MANAGER`, tạo/sửa/vô hiệu hóa nhân viên, đối chiếu `users.is_active` và `roles` trong MySQL; ảnh `bt2-14...18`; kiểm tra các file backend/frontend trước khi commit. |
| Unit Tests | Codex hỗ trợ viết và mở rộng test; ChatGPT hỗ trợ đọc kết quả. | Chạy `tests/run_unit_tests.php`; trong lần thử đã ghi nhận **34 PASS / 0 FAIL** (15 Customer/Merge + 19 User). Chưa khẳng định coverage toàn bộ hệ thống. |
| HTTP Integration Tests | Codex hỗ trợ viết runner và các kiểm tra RBAC/CSRF, User CRUD, seeder, hồi quy Customer/merge. | Chạy `tests/run_user_integration.php`; kết quả đã ghi nhận **58 PASS / 0 FAIL**. Script dùng cơ sở dữ liệu tạm `smartcrm_bt2_users_*`, không đổi `.env` của repo. Đây là HTTP integration, **không phải Browser E2E**. |
| Frontend build / lint | Codex hỗ trợ sửa lỗi CSS và rà soát nguồn. | Chạy `npm.cmd --prefix frontend run lint` và `npm.cmd --prefix frontend run build` trên Windows; đã quan sát ESLint và Vite build thành công. |
| UI Loading / Empty / Error | ChatGPT hướng dẫn kịch bản mô phỏng mạng chậm và lỗi Backend. | Người thực hiện tự chạy trình duyệt, giới hạn mạng 3G và chụp thực tế giao diện `Đang tải dữ liệu...`, Empty và Error/nút `Thử lại`; ảnh `bt2-19`, `bt2-20`, `bt2-21`. **Error còn hiện thông báo kỹ thuật JSON**, chưa được xác minh là đã sửa. |
| README, Bug Log, báo cáo BT2 | ChatGPT hỗ trợ tổng hợp tài liệu tiếng Việt, hình ảnh và dàn trang. | Đối chiếu tên file, lệnh chạy, kết quả terminal, màn hình, MySQL và yêu cầu Buổi 12; báo cáo PDF 6–9 trang xuất riêng cho VLU E-learning, **không cần commit PDF vào GitHub**. |

## 3. Kiểm chứng an toàn và dữ liệu

- Kiểm tra `git status`, `git diff --cached` và file `.gitignore` trước commit để tránh thêm `.env`, mật khẩu, CSRF token hoặc secret.
- Chạy migration và Seeder chỉ trên database thử nghiệm có tiền tố `smartcrm_bt2_`; **không thay đổi database gốc `smartcrm`** khi kiểm tra BT2.
- Kiểm tra dữ liệu bằng MySQL Workbench và thao tác trên UI thực tế; việc AI tạo kịch bản không thay thế bước chạy thử.
- Kết quả Unit 34/34, HTTP Integration 58/58 và build PASS là kết quả **đã quan sát trong quá trình phát triển**, không tuyên bố được chạy lại trên máy người chấm hoặc mọi môi trường.
- Các ảnh minh chứng được chọn, cắt vùng hiển thị nếu cần cho rõ chữ; không thay đổi HTTP status, số lượng test, nội dung thông báo hay dữ liệu kết quả để tạo bằng chứng giả.

## 4. Giới hạn và trách nhiệm

- **Chưa xác minh:** Browser E2E tự động, chạy đồng thời nhiều request, đo tải trên 65.000 hồ sơ, tính tương thích Windows khác/Linux/macOS/Docker.
- **Lỗi UI còn theo dõi:** khi Backend API không phản hồi phù hợp, màn hình đôi lúc hiển thị `Unexpected end of JSON input`; hiện ảnh đã chứng minh Error State và nút Retry, **không phải** bằng chứng lỗi đã được sửa.
- Các đề xuất Architecture/ERD/DDL từ AI được điều chỉnh dựa trên cấu trúc code thực tế; tài liệu không thay thế việc kiểm tra logic, transaction hay bảo mật.
- Sinh viên chịu trách nhiệm cuối cùng về độ đúng, tính trung thực, bảo mật và khả năng giải thích mã nguồn, mô hình dữ liệu, các ca kiểm thử và báo cáo.

## 5. Cam kết

Tôi xác nhận đã sử dụng ChatGPT và Codex trong quá trình thực hiện BT1 và BT2 theo các phạm vi nêu trên. Tôi chịu trách nhiệm rà soát nội dung AI hỗ trợ, đối chiếu tài liệu môn học, chạy kiểm thử thực tế và chỉnh sửa kết quả trước khi nộp.

**Sinh viên:** Thepvinath Souphavilay
**MSSV:** 237480201IS03
