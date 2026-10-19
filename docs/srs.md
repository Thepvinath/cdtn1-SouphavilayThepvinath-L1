SRS – HỆ THỐNG QUẢN LÝ HỒ SƠ KHÁCH HÀNG
Đề tài: Smart CRM – Mekong Mobile
Luồng nghiệp vụ: L1 – Hồ sơ khách hàng
Phạm vi: Tạo – tra cứu – gộp hồ sơ khách hàng trùng
Track: SE – Software Engineering		
1. Giới thiệu và phạm vi
1.1. Bối cảnh
Mekong Mobile hiện có dữ liệu khách hàng được lưu trữ phân tán tại nhiều nguồn khác nhau. Điều này dẫn đến tình trạng một khách hàng có thể tồn tại nhiều hồ sơ khác nhau, gây khó khăn trong việc tra cứu và quản lý thông tin khách hàng.
Theo Case Study, vấn đề V1 là dữ liệu khách hàng bị phân tán và có nhiều hồ sơ trùng. Luồng L1 được đề xuất để quản lý hồ sơ khách hàng và xử lý dữ liệu trùng. 
1.2. Mục tiêu
Xây dựng chức năng quản lý hồ sơ khách hàng cho phép nhân viên:
Tạo hồ sơ khách hàng.
Tra cứu hồ sơ khách hàng.
Xem thông tin chi tiết của hồ sơ.
Phát hiện các hồ sơ có khả năng bị trùng.
So sánh thông tin giữa các hồ sơ trùng.
Chọn hồ sơ chính.
Gộp các hồ sơ trùng thành một hồ sơ duy nhất.
Cập nhật kết quả sau khi gộp.
1.3. Phạm vi
Hệ thống tập trung vào:
Tạo – tra cứu – gộp hồ sơ khách hàng trùng.
Phạm vi này phù hợp với gợi ý của L1 trong Case Study: sinh viên có thể chỉ chọn phần “tạo – tra cứu – gộp hồ sơ trùng”, thay vì thực hiện toàn bộ L1.
1.4. Ngoài phạm vi
Hệ thống không thực hiện:
Phân khúc khách hàng VIP / Thường xuyên / Mới / Ngủ đông.
Quản lý chiến dịch Marketing.
Quản lý đơn hàng.
Quản lý bảo hành.
Quản lý kho linh kiện.
Dự báo khách hàng rời bỏ.
Chỉnh sửa hồ sơ khách hàng như một chức năng nghiệp vụ độc lập.
Việc cập nhật thông tin phát sinh sau khi gộp hồ sơ vẫn thuộc UC5 và không được xem là chức năng chỉnh sửa hồ sơ độc lập.
1.5. Thuật ngữ
Thuật ngữ	Ý nghĩa
Hồ sơ khách hàng	Thông tin lưu trữ về một khách hàng
Hồ sơ trùng	Hai hoặc nhiều hồ sơ có khả năng thuộc cùng một khách hàng
Hồ sơ chính	Hồ sơ được chọn để giữ lại khi thực hiện gộp
Gộp hồ sơ	Hợp nhất các hồ sơ trùng thành một hồ sơ duy nhất
Actor	Người hoặc vai trò tương tác với hệ thống

2. Stakeholders và Actors
2.1. Nhân viên bán hàng – Actor chính
Nhân viên bán hàng là Actor chính của chức năng quản lý hồ sơ khách hàng.
Có thể:
Tạo hồ sơ khách hàng.
Tra cứu hồ sơ.
Xem thông tin chi tiết.
Kiểm tra hồ sơ có khả năng bị trùng.
Thực hiện quy trình gộp hồ sơ.
2.2. Quản lý cửa hàng – Actor phụ
Quản lý cửa hàng có thể sử dụng thông tin hồ sơ khách hàng để tra cứu, kiểm tra và hỗ trợ quản lý dữ liệu khách hàng.
2.3. Marketing – Actor phụ
Marketing sử dụng dữ liệu khách hàng phục vụ các nghiệp vụ liên quan đến khách hàng. Trong phạm vi bài này, Marketing chỉ tương tác với các chức năng phù hợp của hồ sơ khách hàng; chức năng phân khúc khách hàng không thuộc phạm vi triển khai.
Case Study xác định các nhóm người dùng chính của L1 gồm Nhân viên bán hàng, Marketing và Quản lý cửa hàng. 
3. Functional Requirements và User Stories
3.1. Functional Requirements
FR1 – Tạo hồ sơ khách hàng
Hệ thống phải cho phép nhân viên bán hàng tạo hồ sơ khách hàng mới.
Trước khi tạo, hệ thống phải kiểm tra số điện thoại để tránh tạo hồ sơ trùng theo quy tắc dữ liệu khách hàng.
FR2 – Tra cứu hồ sơ khách hàng
Hệ thống phải cho phép tra cứu hồ sơ khách hàng theo họ tên hoặc số điện thoại.
Hệ thống hiển thị các kết quả phù hợp với điều kiện tìm kiếm.
FR3 – Xem chi tiết hồ sơ khách hàng
Hệ thống phải cho phép người dùng xem thông tin chi tiết của hồ sơ khách hàng được chọn từ kết quả tra cứu.
FR4 – Phát hiện hồ sơ có khả năng bị trùng
Hệ thống phải hỗ trợ xác định và hiển thị các hồ sơ khách hàng có khả năng bị trùng để người dùng kiểm tra.
Người dùng có thể so sánh thông tin giữa các hồ sơ trước khi quyết định gộp.
Lưu ý: Case Study xác nhận có vấn đề hồ sơ khách hàng trùng, nhưng không quy định cụ thể thuật toán hoặc ngưỡng similarity để xác định “có khả năng bị trùng”. Vì vậy SRS này không tự đặt một ngưỡng như 80% hoặc 90%.
FR5 – Gộp hồ sơ khách hàng trùng
Hệ thống phải cho phép người dùng:
1.	Chọn các hồ sơ cần gộp.
2.	So sánh thông tin giữa các hồ sơ.
3.	Chọn hồ sơ chính.
4.	Xác nhận thao tác gộp.
5.	Gộp các hồ sơ trùng.
6.	Cập nhật hồ sơ sau khi gộp.
Nếu không đáp ứng điều kiện thực hiện, hệ thống phải từ chối thao tác và thông báo lỗi phù hợp.
3.2. User Stories
US1 – Tạo hồ sơ khách hàng — MUST
Là nhân viên bán hàng, tôi muốn tạo hồ sơ khách hàng để lưu thông tin khách hàng vào hệ thống và tránh phải ghi chép rời rạc.
US2 – Tra cứu hồ sơ khách hàng — MUST
Là nhân viên bán hàng, tôi muốn tra cứu hồ sơ khách hàng theo họ tên hoặc số điện thoại để nhanh chóng tìm đúng hồ sơ khách hàng.
US3 – Xem chi tiết hồ sơ — COULD
Là nhân viên bán hàng, tôi muốn xem thông tin chi tiết của hồ sơ khách hàng để kiểm tra thông tin trước khi xử lý hồ sơ.
US4 – Phát hiện hồ sơ trùng — SHOULD
Là nhân viên bán hàng, tôi muốn phát hiện các hồ sơ khách hàng có khả năng bị trùng để xác định những hồ sơ cần kiểm tra và gộp.
US5 – So sánh hồ sơ trùng — SHOULD
Là nhân viên bán hàng, tôi muốn so sánh thông tin giữa các hồ sơ có khả năng bị trùng để xác định chúng có thuộc cùng một khách hàng hay không.
US6 – Chọn hồ sơ chính và gộp hồ sơ trùng — MUST
Là nhân viên bán hàng, tôi muốn chọn hồ sơ chính và xác nhận gộp các hồ sơ trùng để hợp nhất thông tin khách hàng thành một hồ sơ duy nhất.
US7 – Cập nhật hồ sơ sau khi gộp — COULD
Là nhân viên bán hàng, tôi muốn cập nhật thông tin của hồ sơ sau khi gộp để bảo đảm hồ sơ khách hàng cuối cùng đầy đủ và chính xác.
3.3. Acceptance Criteria
US1 – Tạo hồ sơ khách hàng
AC1 – Tạo thành công
GIVEN số điện thoại chưa tồn tại trong hệ thống
WHEN nhân viên nhập đầy đủ thông tin hợp lệ và thực hiện tạo hồ sơ
THEN hệ thống tạo một hồ sơ khách hàng mới.
AC2 – Số điện thoại đã tồn tại
GIVEN số điện thoại đã tồn tại trong hệ thống
WHEN nhân viên sử dụng số điện thoại đó để tạo hồ sơ mới
THEN hệ thống không tạo hồ sơ trùng và hiển thị hồ sơ hiện có.
Điều này phù hợp với QT-01 của Case Study về tính duy nhất của số điện thoại khách hàng. 
US2 – Tra cứu hồ sơ khách hàng
AC1 – Tìm thấy
GIVEN hồ sơ khách hàng tồn tại trong hệ thống
WHEN nhân viên tra cứu bằng họ tên hoặc số điện thoại
THEN hệ thống hiển thị hồ sơ phù hợp.
AC2 – Không tìm thấy
GIVEN không có hồ sơ phù hợp
WHEN nhân viên thực hiện tra cứu
THEN hệ thống hiển thị thông báo không tìm thấy hồ sơ.
US6 – Chọn hồ sơ chính và gộp hồ sơ trùng
AC1 – Gộp thành công
GIVEN các hồ sơ đã được xác nhận để thực hiện gộp và đã chọn hồ sơ chính
WHEN nhân viên xác nhận gộp
THEN hệ thống thực hiện gộp và giữ lại một hồ sơ khách hàng chính.
AC2 – Gộp không hợp lệ
GIVEN dữ liệu cần thiết cho thao tác gộp không hợp lệ
WHEN nhân viên xác nhận gộp
THEN hệ thống từ chối thao tác và hiển thị thông báo phù hợp.
4. Non-functional Requirements
Buổi 4 yêu cầu SRS có ít nhất 3 NFR và NFR phải có giá trị đo được. CDTN1_Buoi04_Phan tich yeu cau …
**NFR1 – Hiệu năng**

Hệ thống phải trả về kết quả tra cứu hồ sơ khách hàng theo họ tên hoặc số điện thoại trong thời gian không quá 2 giây (≤ 2 giây) với cơ sở dữ liệu có tối đa 65.000 hồ sơ khách hàng, trong điều kiện môi trường kiểm thử được xác định.

**Phương pháp kiểm chứng:** Chuẩn bị tập dữ liệu 65.000 hồ sơ khách hàng, thực hiện các truy vấn tra cứu theo họ tên và số điện thoại, đo thời gian phản hồi và đối chiếu với ngưỡng NFR1.
NFR2 – Bảo mật
100% API thay đổi dữ liệu khách hàng phải yêu cầu người dùng đã được xác thực và có quyền phù hợp trước khi thực hiện.
NFR3 – Bảo vệ dữ liệu cá nhân
Đối với vai trò không được phép xem đầy đủ số điện thoại, hệ thống phải che số điện thoại theo quy tắc phân quyền trong 100% kết quả hiển thị.
Case Study có business rule yêu cầu mask số điện thoại đối với phần lớn vai trò. 
NFR4 – Độ tin cậy
Nếu thao tác gộp hồ sơ thất bại, hệ thống không được để dữ liệu ở trạng thái gộp một phần; 100% thao tác gộp thất bại phải được rollback.
5. Business Rules
BR1 – Số điện thoại khách hàng là duy nhất
Số điện thoại được dùng để xác định khách hàng và phải bảo đảm tính duy nhất theo QT-01. Nếu số điện thoại đã tồn tại, hệ thống không tạo thêm hồ sơ mới với cùng số điện thoại. 
BR2 – Chuẩn hóa số điện thoại
Số điện thoại phải được chuẩn hóa trước khi lưu hoặc so sánh theo QT-02. 
BR3 – Soft Delete
Dữ liệu nghiệp vụ không được xóa vật lý trực tiếp mà áp dụng cơ chế soft delete theo QT-13. 
BR4 – Phân quyền dữ liệu
Người dùng chỉ được truy cập dữ liệu phù hợp với vai trò và phạm vi được phép theo QT-14. 
BR5 – Che số điện thoại
Số điện thoại phải được che đối với các vai trò không có quyền xem đầy đủ theo QT-15. 
BR6 – Quy tắc gộp hồ sơ
Người dùng phải chọn một hồ sơ chính trước khi xác nhận gộp.
Không được chọn cùng một hồ sơ vừa là hồ sơ chính vừa là hồ sơ cần gộp.
Case Study không quy định chi tiết cách chọn giá trị cuối cùng khi các trường như email hoặc địa chỉ của hai hồ sơ xung đột. Quy tắc này cần được xác nhận trước khi triển khai thay vì tự giả định.
6. Use Case và Traceability
6.1. Danh sách Use Case chính
Use Case Diagram sử dụng thống nhất 5 Use Cases:
ID	Use Case
UC1	Tạo hồ sơ khách hàng
UC2	Tra cứu hồ sơ khách hàng
UC3	Xem chi tiết hồ sơ khách hàng
UC4	Phát hiện hồ sơ có khả năng bị trùng
UC5	Gộp hồ sơ khách hàng trùng
Các chức năng phụ như so sánh thông tin, chọn hồ sơ chính, xác nhận gộp, cập nhật hồ sơ sau khi gộp được xử lý trong luồng của UC4/UC5 thay vì tạo thêm UC chính.
6.2. Đặc tả UC5 – Gộp hồ sơ khách hàng trùng
ID: UC5
Tên: Gộp hồ sơ khách hàng trùng
Actor chính: Nhân viên bán hàng
Actor phụ: Quản lý cửa hàng, Marketing (theo quyền được cấp)
Mục tiêu
Hợp nhất các hồ sơ được xác định thuộc cùng một khách hàng thành một hồ sơ duy nhất.
Tiền điều kiện
Người dùng đã đăng nhập.
Người dùng có quyền thực hiện chức năng.
Các hồ sơ cần xử lý tồn tại trong hệ thống.
Hậu điều kiện
Nếu thành công:
Một hồ sơ chính được giữ lại.
Kết quả gộp được cập nhật trong hệ thống.
Nếu thất bại:
Không để dữ liệu ở trạng thái gộp một phần.
Main Flow
1.	Người dùng mở chức năng xử lý hồ sơ trùng.
2.	Hệ thống hiển thị các hồ sơ có khả năng bị trùng.
3.	Người dùng chọn các hồ sơ cần xử lý.
4.	Hệ thống hiển thị thông tin để so sánh.
5.	Người dùng kiểm tra thông tin giữa các hồ sơ.
6.	Người dùng chọn hồ sơ chính.
7.	Hệ thống hiển thị thông tin chuẩn bị gộp.
8.	Người dùng xác nhận gộp.
9.	Hệ thống thực hiện gộp.
10.	Hệ thống cập nhật hồ sơ sau khi gộp.
11.	Hệ thống thông báo thao tác hoàn tất.
**Exception Flow – UC5**

**6a. Chưa chọn hồ sơ chính**

- 6a.1. Tại bước 6, nếu người dùng chưa chọn hồ sơ chính, hệ thống hiển thị thông báo yêu cầu lựa chọn Master Record.
- 6a.2. Người dùng chọn hồ sơ chính hợp lệ trước khi tiếp tục sang bước 7.

**8a. Dữ liệu gộp không hợp lệ**

- 8a.1. Tại bước 8, nếu hồ sơ chính trùng với hồ sơ nguồn hoặc dữ liệu không đáp ứng điều kiện gộp, hệ thống từ chối thao tác.
- 8a.2. Hệ thống hiển thị thông báo lỗi và giữ nguyên dữ liệu hiện có.
- 8a.3. Người dùng kiểm tra lại các hồ sơ đã chọn và thực hiện lại thao tác khi dữ liệu hợp lệ.

**9a. Lỗi trong quá trình gộp hồ sơ**

- 9a.1. Tại bước 9, nếu xảy ra lỗi trong quá trình thực hiện gộp, hệ thống rollback toàn bộ transaction.
- 9a.2. Thông tin các hồ sơ khách hàng được giữ nguyên như trước khi thực hiện thao tác gộp.
- 9a.3. Hệ thống thông báo gộp thất bại và không ghi nhận kết quả gộp một phần.

**Liên kết NFR:** Luồng ngoại lệ 9a hỗ trợ yêu cầu NFR4 về tính toàn vẹn dữ liệu và rollback khi gộp thất bại.

### 6.3. Traceability Matrix

| FR | User Story | Use Case | MoSCoW | API |
|---|---|---|---|---|
| FR1 – Tạo hồ sơ khách hàng | US1 | UC1 | MUST | POST /api/customers |
| FR2 – Tra cứu hồ sơ khách hàng | US2 | UC2 | MUST | GET /api/customers |
| FR3 – Xem chi tiết hồ sơ | US3 | UC3 | COULD | GET /api/customers/{id} |
| FR4 – Phát hiện hồ sơ có khả năng bị trùng | US4, US5 | UC4 | SHOULD | GET /api/customers/duplicates |
| FR5 – Gộp hồ sơ khách hàng trùng | US6, US7 | UC5 | MUST | POST /api/customers/merge |
