PHASE 2 - CÁC CHỨC NĂNG ĐÃ BỔ SUNG

1. Admin CRUD/khóa-mở:
   - admin/doctors.php: thêm, sửa, khóa/mở bác sĩ, tạo tài khoản, đặt lại mật khẩu.
   - admin/specialties.php: thêm, sửa, khóa/mở chuyên khoa.
   - admin/rooms.php: thêm, sửa, khóa/mở phòng khám.
   - admin/medicines.php: thêm, sửa, khóa/mở thuốc, tồn kho, đơn giá, hạn sử dụng.

2. Bác sĩ:
   - doctor/schedules.php: tự tạo/sửa/hủy/mở lại lịch làm việc, kiểm tra trùng giờ.
   - doctor/appointments.php: có nút Khám/Hồ sơ.
   - doctor/medical-record.php: chẩn đoán, kết luận, hướng điều trị, đơn thuốc.

3. Database mới:
   - HoSoKham
   - DonThuoc
   - ChiTietDonThuoc

NẾU BẠN ĐÃ IMPORT DATABASE CỦA PHASE 1:
- Vào phpMyAdmin -> QuanLyKhamBenh -> Import
- Import file database/phase2_upgrade.sql

NẾU CÀI MỚI HOÀN TOÀN:
- Chỉ cần import database/quanlykham.sql

URL ví dụ khi project nằm ở D:\xampp\htdocs\clinic
- http://localhost/clinic/login.php
- http://localhost/clinic/admin/dashboard.php
- http://localhost/clinic/doctor/dashboard.php
