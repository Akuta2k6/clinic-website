## 1. Chức năng chính

### Dành cho bệnh nhân và khách truy cập

- Xem thông tin bệnh viện, chuyên khoa, dịch vụ và đội ngũ bác sĩ.
- Xem danh sách bác sĩ và lọc theo chuyên khoa.
- Đặt lịch khám không cần tạo tài khoản.
- Chọn chuyên khoa, bác sĩ, ngày khám và khung giờ còn chỗ.
- Nhận mã tra cứu và email xác nhận khi gửi yêu cầu đặt lịch thành công.
- Tra cứu lịch hẹn bằng mã tra cứu và số điện thoại.
- Yêu cầu hủy lịch trước giờ khám bằng mã OTP gửi đến email:
  - OTP gồm 6 chữ số.
  - Mã có hiệu lực trong 10 phút.
  - Có thời gian chờ 60 giây trước khi gửi lại mã.
  - Tối đa 5 lần nhập sai cho mỗi mã.
- Gửi đánh giá trải nghiệm mà không cần đăng nhập; có thể hiển thị tên hoặc chọn ẩn danh, chấm từ 1 đến 5 sao.
- Gửi liên hệ, yêu cầu hỗ trợ đến bệnh viện.

### Dành cho bác sĩ

- Xem bảng tổng quan lịch khám và các thống kê liên quan.
- Xem, xác nhận hoặc hủy lịch hẹn thuộc phạm vi bác sĩ phụ trách.
- Tạo và điều chỉnh lịch làm việc, khung giờ khám và số lượng tiếp nhận.
- Xem lịch sử khám của bệnh nhân.
- Lập hồ sơ khám gồm chẩn đoán, kết luận, hướng điều trị và ghi chú.
- Kê đơn thuốc, cập nhật số lượng thuốc tồn kho tương ứng.
- Hoàn tất lượt khám và gửi email kết quả dưới dạng PDF tổng hợp thông tin khám cùng đơn thuốc (nếu có).
- Thử gửi lại email kết quả khi lần gửi trước thất bại.

### Dành cho quản trị viên

- Theo dõi số liệu hoạt động trên trang tổng quan.
- Quản lý tài khoản và thông tin bác sĩ, bao gồm ảnh đại diện.
- Quản lý chuyên khoa.
- Quản lý phòng khám.
- Quản lý danh mục thuốc, giá, hạn sử dụng và số lượng tồn kho.
- Theo dõi danh sách lịch hẹn.
- Phản hồi yêu cầu hỗ trợ của bệnh nhân, khách hàng.

## 2. Yêu cầu môi trường

- XAMPP hoặc môi trường tương đương có Apache, PHP và MySQL/MariaDB.
- PHP có các extension cần thiết cho PDO MySQL và mPDF, đặc biệt là `pdo_mysql`, `mbstring` và `gd`.
- Composer để cài đặt các thư viện PHP được khai báo trong `composer.json`.
- Trình duyệt web hiện đại.

## 3. Cài đặt và chạy dự án

### Bước 1: Đưa mã nguồn vào thư mục web

Đặt thư mục `clinic-website` trong thư mục `htdocs` của XAMPP. Ví dụ:

```text
C:\xampp\htdocs\finalProject\clinic-website
```

Khởi động Apache và MySQL trong XAMPP.

### Bước 2: Tạo cơ sở dữ liệu

1. Mở phpMyAdmin tại `http://localhost/phpmyadmin`.
2. Tạo cơ sở dữ liệu có tên `QuanLyKhamBenh`, sử dụng bộ mã `utf8mb4`.
3. Chọn cơ sở dữ liệu vừa tạo và import tệp:

```text
database/QuanLyKhamBenh.sql
```

Tệp SQL tạo 13 bảng dữ liệu chính: `TaiKhoan`, `ChuyenKhoa`, `BacSi`, `BenhNhan`, `PhongKham`, `LichKham`, `DatLich`, `DanhGia`, `XacMinhHuyLich`, `Thuoc`, `HoSoKham`, `DonThuoc` và `ChiTietDonThuoc`.

**Lưu ý:** Tệp SQL khởi tạo cấu trúc bảng, không tạo sẵn tài khoản đăng nhập hoặc dữ liệu mẫu.

### Bước 3: Cấu hình kết nối MySQL

Mở `config/database.php` và cập nhật thông tin kết nối phù hợp với máy đang chạy:

```php
$host = 'localhost';
$dbname = 'QuanLyKhamBenh';
$username = 'root';
$password = '';
```

Các giá trị trên là cấu hình thường dùng cho XAMPP cài đặt mặc định. Hãy thay đổi nếu MySQL của bạn sử dụng tài khoản hoặc mật khẩu khác.

### Bước 4: Cài đặt thư viện PHP

Mở Terminal tại thư mục gốc của dự án và chạy:

```bash
composer install
```

Lệnh này cài đặt các thư viện được khai báo trong `composer.json`, trong đó có mPDF dùng để tạo PDF kết quả khám.

### Bước 5: Cấu hình SMTP gửi email

1. Sao chép `config/mail.example.php` thành `config/mail.php`.
2. Mở `config/mail.php` và điền thông tin SMTP của bạn.
3. Cập nhật `base_url` theo địa chỉ dự án đang chạy.

Ví dụ khi dự án nằm tại `htdocs/finalProject/clinic-website`:

```php
'base_url' => 'http://localhost/finalProject/clinic-website'
```

Nếu sử dụng Gmail, hãy cấu hình mật khẩu ứng dụng (App Password) và bật xác thực SMTP phù hợp. Không sử dụng mật khẩu đăng nhập Gmail thông thường.

**Bảo mật:** `config/mail.php` chứa thông tin xác thực nên không được đưa lên Git hoặc chia sẻ công khai. Tệp này đã được khai báo trong `.gitignore`.

### Bước 6: Tạo tài khoản quản trị ban đầu

Do cơ sở dữ liệu không có tài khoản mẫu, cần tạo tài khoản Admin đầu tiên trước khi đăng nhập.

Tạo chuỗi mật khẩu đã băm bằng PHP:

```bash
php -r "echo password_hash('ThayBangMatKhauCuaBan', PASSWORD_DEFAULT), PHP_EOL;"
```

Sao chép kết quả và chạy câu lệnh SQL sau trong phpMyAdmin:

```sql
INSERT INTO TaiKhoan (TenDangNhap, MatKhau, VaiTro, TrangThai)
VALUES ('admin', 'DAN_CHUOI_HASH_VAO_DAY', 'Admin', 1);
```

Sau khi đăng nhập bằng tài khoản vừa tạo, quản trị viên có thể tạo chuyên khoa, phòng khám, tài khoản bác sĩ và các dữ liệu cần thiết. Bác sĩ cần được liên kết với một chuyên khoa trước khi thiết lập lịch làm việc.

### Bước 7: Truy cập website

Với ví dụ thư mục ở trên, mở:

```text
http://localhost/finalProject/clinic-website/
```

Các địa chỉ thường dùng:

- Trang chủ: `/index.php`
- Đặt lịch: `/appointment.php`
- Danh sách bác sĩ: `/doctors.php`
- Tra cứu lịch: `/patient/lookup.php`
- Đánh giá: `/testimonials.php`
- Đăng nhập: `/login.php`

## bonus 6. Các tài khoản admin và bác sĩ

### ADMIN

Tên đăng nhập: admin
Mật khẩu:      admin123

### Bác sĩ:

Tên đăng nhập: bs.tenbacsi (có thể lấy các tên bác sĩ trong doctor.php. Ví dụ:bs.nguyenminhanh)
Mật khẩu:      doctor123

## 4. Kiểm tra sau khi cài đặt

- [ ] Trang chủ và các trang thông tin hiển thị bình thường.
- [ ] Kết nối MySQL thành công.
- [ ] Đăng nhập được bằng tài khoản Admin đã tạo.
- [ ] Tạo được chuyên khoa, phòng khám và tài khoản bác sĩ.
- [ ] Bác sĩ tạo được lịch làm việc.
- [ ] Bệnh nhân đặt được lịch và nhận mã tra cứu.
- [ ] Email xác nhận đặt lịch được gửi thành công.
- [ ] Bệnh nhân tra cứu và yêu cầu hủy lịch bằng OTP được.
- [ ] Bác sĩ lưu được hồ sơ khám, đơn thuốc và gửi được PDF kết quả.
- [ ] Khách truy cập gửi được đánh giá.

## 5. Luồng nghiệp vụ tiêu biểu

### Đặt lịch khám

1. Bệnh nhân nhập thông tin cá nhân và lý do khám.
2. Chọn chuyên khoa, bác sĩ, ngày khám và khung giờ còn chỗ.
3. Hệ thống kiểm tra trạng thái bác sĩ, chuyên khoa, thời gian và sức chứa của lịch.
4. Hệ thống lưu thông tin bệnh nhân và lịch hẹn trong MySQL.
5. Hệ thống tạo mã tra cứu và gửi email xác nhận.

### Hủy lịch bằng OTP

1. Bệnh nhân nhập mã tra cứu và số điện thoại để tìm lịch.
2. Yêu cầu gửi mã OTP đến email đã đăng ký.
3. Bệnh nhân nhập OTP còn hiệu lực.
4. Nếu mã hợp lệ và lịch đủ điều kiện hủy, hệ thống cập nhật trạng thái lịch và điều chỉnh số chỗ đã đặt.
5. Hệ thống gửi email thông báo kết quả hủy lịch.

### Hoàn tất khám và gửi kết quả

1. Bác sĩ mở lịch hẹn thuộc phạm vi phụ trách.
2. Lưu hồ sơ khám và đơn thuốc hoặc xác nhận không kê thuốc.
3. Hoàn tất lượt khám.
4. Hệ thống tạo PDF tổng hợp và gửi đến email bệnh nhân.
5. Hệ thống lưu trạng thái gửi kết quả để theo dõi hoặc thử gửi lại khi cần.

