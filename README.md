Website hỗ trợ bệnh nhân tìm hiểu thông tin bệnh viện, đặt lịch khám trực tuyến và tra cứu lịch hẹn; đồng thời cung cấp cổng làm việc riêng cho bác sĩ và quản trị viên.

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

## 2. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Giao diện | HTML, CSS, JavaScript |
| Giao diện và thành phần dựng sẵn | Bootstrap 5, Bootstrap Icons |
| Xử lý phía máy chủ | PHP |
| Cơ sở dữ liệu | MySQL |
| Kết nối cơ sở dữ liệu | PDO |
| Giao tiếp bất đồng bộ | JavaScript Fetch API, PHP JSON API |
| Gửi email | PHPMailer, SMTP |
| Tạo PDF | mPDF |
| Môi trường phát triển đề xuất | XAMPP, Composer |

## 3. Kiến trúc tổng thể

Dự án được tổ chức theo mô hình 3 lớp:

1. **Presentation Layer:** Trình duyệt hiển thị giao diện HTML/CSS/JavaScript và Bootstrap; Fetch API gửi yêu cầu lấy dữ liệu động.
2. **Application Layer:** Apache và PHP tiếp nhận yêu cầu, kiểm tra dữ liệu, xử lý nghiệp vụ, xác thực vai trò và trao đổi dữ liệu với cơ sở dữ liệu.
3. **Data Layer:** MySQL lưu trữ tài khoản, bệnh nhân, bác sĩ, lịch khám, hồ sơ khám, đơn thuốc và các dữ liệu liên quan.

Ví dụ: khi bệnh nhân chọn chuyên khoa, JavaScript gọi `api/get-doctors.php` để lấy danh sách bác sĩ phù hợp. Khi chọn bác sĩ và ngày khám, trình duyệt gọi `api/get-schedules.php` để lấy các khung giờ còn nhận lịch.

## 4. Cấu trúc thư mục

```text
clinic-website/
├── actions/        # Xử lý đăng nhập và đặt lịch
├── admin/          # Các trang quản trị viên
├── api/            # API trả dữ liệu JSON
├── assets/         # CSS, JavaScript, hình ảnh và tài nguyên giao diện
├── auth/           # Kiểm tra đăng nhập và phân quyền
├── config/         # Cấu hình cơ sở dữ liệu và email
├── database/       # Tệp SQL khởi tạo cơ sở dữ liệu
├── doctor/         # Các trang chức năng dành cho bác sĩ
├── forms/          # Các xử lý biểu mẫu theo template
├── helpers/        # Hàm hỗ trợ email, PDF và bố cục portal
├── libs/PHPMailer/ # Thư viện gửi email
├── patient/        # Tra cứu và yêu cầu hủy lịch của bệnh nhân
├── vendor/         # Thư viện PHP được Composer cài đặt
├── appointment.php # Trang đặt lịch khám
├── doctors.php     # Danh sách bác sĩ
├── index.php       # Trang chủ
├── login.php       # Trang đăng nhập
├── contact.php     # Trang gửi yêu cầu hỗ trợ
└── testimonials.php# Trang đánh giá
```

## 5. Yêu cầu môi trường

- XAMPP hoặc môi trường tương đương có Apache, PHP và MySQL/MariaDB.
- PHP có các extension cần thiết cho PDO MySQL và mPDF, đặc biệt là `pdo_mysql`, `mbstring` và `gd`.
- Composer để cài đặt các thư viện PHP được khai báo trong `composer.json`.
- Trình duyệt web hiện đại.

## 6. Cài đặt và chạy dự án

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

## 7. Luồng nghiệp vụ tiêu biểu

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

## 8. Kiểm tra sau khi cài đặt

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

## 9. Phạm vi hiện tại và lưu ý

- Bệnh nhân đặt lịch và tra cứu lịch bằng thông tin xác minh, không cần tài khoản bệnh nhân.
- Các chức năng xét nghiệm và tiêm ngừa hiện có trang giao diện giới thiệu/đăng ký; chưa được tích hợp thành quy trình đặt lịch và quản lý riêng như chức năng đặt lịch khám.
- Biểu mẫu liên hệ trên trang `contact.html` chưa được kết nối hoàn chỉnh với dịch vụ gửi email hoặc chức năng lưu dữ liệu.
- Cần cấu hình SMTP hợp lệ để sử dụng các chức năng gửi email.
- Cần cài Composer dependencies để tạo PDF kết quả khám.
- Đây là sản phẩm phục vụ học tập và trình diễn quy trình quản lý phòng khám. Trước khi triển khai thực tế cần rà soát bảo mật, phân quyền, sao lưu, bảo vệ dữ liệu cá nhân và kiểm thử toàn bộ nghiệp vụ.

## 10. Tác giả và mục đích

Dự án được xây dựng nhằm thực hành phát triển ứng dụng web PHP kết nối MySQL, xử lý nghiệp vụ đặt lịch khám, quản lý lịch làm việc, hồ sơ khám bệnh, đơn thuốc và phân quyền người dùng.

