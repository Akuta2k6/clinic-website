USE QuanLyKhamBenh;
-- Ví dụ tạo lịch cho bác sĩ MaBS=1. Đổi ngày trước khi chạy nếu cần.
INSERT INTO LichKham (MaBS, MaPK, NgayKham, GioBatDau, GioKetThuc, SoLuongToiDa) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '08:30:00', 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:30:00', '09:00:00', 1),
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '09:30:00', 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '13:30:00', 1),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:30:00', '14:00:00', 1);
