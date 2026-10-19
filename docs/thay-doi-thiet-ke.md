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
