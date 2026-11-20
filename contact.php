<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$contactSettings = require __DIR__ . '/config/contact.php';
$contactEmail = (string)$contactSettings['receiving_email'];

function contactEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function contactLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

$contactSubjects = [
    'Tư vấn dịch vụ khám chữa bệnh',
    'Hỏi đáp về lịch khám',
    'Hỗ trợ đặt lịch khám',
    'Góp ý chất lượng dịch vụ',
    'Khiếu nại',
    'Khác'
];

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['contact_csrf'];
$success = (string)($_SESSION['contact_flash'] ?? '');
unset($_SESSION['contact_flash']);
$error = '';
$formData = [
    'HoTen' => '',
    'Email' => '',
    'SoDienThoai' => '',
    'ChuDe' => '',
    'NoiDung' => ''
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($formData as $key => $default) {
        $value = $_POST[$key] ?? '';
        $formData[$key] = is_string($value) ? trim($value) : '';
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    $honeypot = $_POST['website'] ?? '';
    $phonePattern = '/^[0-9+().\-\s]{8,20}$/';

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif (!is_string($honeypot) || trim($honeypot) !== '') {
        $error = 'Không thể tiếp nhận yêu cầu lúc này. Vui lòng thử lại sau.';
    } elseif (isset($_SESSION['contact_last_submit']) && time() - (int)$_SESSION['contact_last_submit'] < 20) {
        $error = 'Bạn vừa gửi một yêu cầu. Vui lòng chờ ít nhất 20 giây trước khi gửi tiếp.';
    } elseif ($formData['HoTen'] === '' || contactLength($formData['HoTen']) > 100) {
        $error = 'Vui lòng nhập họ tên không quá 100 ký tự.';
    } elseif (!filter_var($formData['Email'], FILTER_VALIDATE_EMAIL) || strlen($formData['Email']) > 150) {
        $error = 'Địa chỉ email không hợp lệ.';
    } elseif ($formData['SoDienThoai'] !== '' && !preg_match($phonePattern, $formData['SoDienThoai'])) {
        $error = 'Số điện thoại không hợp lệ.';
    } elseif (!in_array($formData['ChuDe'], $contactSubjects, true)) {
        $error = 'Vui lòng chọn chủ đề liên hệ.';
    } elseif (contactLength($formData['NoiDung']) < 10 || contactLength($formData['NoiDung']) > 3000) {
        $error = 'Nội dung liên hệ phải có từ 10 đến 3.000 ký tự.';
    } else {
        require_once __DIR__ . '/config/database.php';

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO LienHe (HoTen, Email, SoDienThoai, ChuDe, NoiDung, NgayGui, TrangThai)
                 VALUES (:hoten, :email, :sodienthoai, :chude, :noidung, NOW(), 'Moi')"
            );
            $stmt->execute([
                ':hoten' => $formData['HoTen'],
                ':email' => $formData['Email'],
                ':sodienthoai' => $formData['SoDienThoai'] !== '' ? $formData['SoDienThoai'] : null,
                ':chude' => $formData['ChuDe'],
                ':noidung' => $formData['NoiDung']
            ]);

            $contactId = (int)$pdo->lastInsertId();
            $contactRecord = $formData + ['MaLienHe' => $contactId];
            $notificationSent = false;
            $confirmationSent = false;

            require_once __DIR__ . '/helpers/contact-mail.php';

            try {
                clinicContactSendNotification($contactRecord);
                $notificationSent = true;
            } catch (Throwable $mailError) {
                $notificationSent = false;
            }

            try {
                clinicContactSendConfirmation($contactRecord);
                $confirmationSent = true;
            } catch (Throwable $mailError) {
                $confirmationSent = false;
            }

            $statusStmt = $pdo->prepare(
                'UPDATE LienHe SET TrangThaiThongBao = ?, TrangThaiXacNhan = ? WHERE MaLienHe = ?'
            );
            $statusStmt->execute([
                $notificationSent ? 'DaGui' : 'ThatBai',
                $confirmationSent ? 'DaGui' : 'ThatBai',
                $contactId
            ]);

            $_SESSION['contact_last_submit'] = time();
            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
            $_SESSION['contact_flash'] = $confirmationSent
                ? 'Yêu cầu của bạn đã được ghi nhận. Email xác nhận đã được gửi đến hộp thư của bạn.'
                : 'Yêu cầu của bạn đã được lưu vào hệ thống. Bộ phận hỗ trợ sẽ tiếp nhận và phản hồi trong thời gian sớm nhất.';
            header('Location: contact.php#contact-form');
            exit;
        } catch (PDOException $exception) {
            $error = 'Chưa thể lưu yêu cầu liên hệ. Vui lòng thử lại sau.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Liên hệ | Bệnh viện Đa khoa Tôn Đức Thắng</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/contact-form.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: Clinic
  * Template URL: https://bootstrapmade.com/clinic-bootstrap-template/
  * Updated: Jul 23 2025 with Bootstrap v5.3.7
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="contact-page">

  <header id="header" class="header fixed-top">

    <div class="topbar d-flex align-items-center dark-background">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope d-flex align-items-center"><a
              href="mailto:<?= contactEscape($contactEmail) ?>"><?= contactEscape($contactEmail) ?></a></i>
          <i class="bi bi-phone d-flex align-items-center ms-4"><span>115</span></i>
        </div>
        <div class="social-links d-none d-md-flex align-items-center">
          <a href="#!" class="twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#!" class="facebook"><i class="bi bi-facebook"></i></a>
          <a href="#!" class="instagram"><i class="bi bi-instagram"></i></a>
          <a href="#!" class="linkedin"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div><!-- End Top Bar -->

    <div class="branding d-flex align-items-cente">

      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.html" class="logo d-flex align-items-center">
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <img src="assets/img/logo.jpg" alt="">
          <h1 class="sitename">Bệnh viện đa khoa Tôn Đức Thắng</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index.html">Trang chủ</a></li>
            <li><a href="about.html">Giới thiệu</a></li>
            <li class="dropdown"><a href="department-details.html"><span>Khoa</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="departments.html#departments-tabs-neurology">Khoa Nội</a></li>  
              <li><a href="departments.html#departments-tabs-surgery">Khoa Ngoại</a></li>
              <li><a href="departments.html#departments-tabs-dental">Khoa Răng Hàm Mặt</a></li>
              <li><a href="departments.html#departments-tabs-ophthalmology">Khoa Mắt</a></li>
              <li><a href="departments.html#departments-tabs-cardiology">Khoa Tim Mạch</a></li>
            </ul>
            </li>
            <li class="dropdown"><a href="service-details.html"><span>Dịch vụ</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="appointment.php">Đặt lịch khám</a></li>
              <li><a href="testing.php">Xét nghiệm</a></li>
              <li><a href="vaccination.php">Tiêm ngừa</a></li>
            </ul>
            </li>
            <li><a href="doctors.php">Bác sĩ</a></li>
            <li><a href="contact.php" class="active">Liên hệ</a></li>
            <li class="dropdown"><a href="#"><span>Thêm</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="testimonials.php">Đánh giá</a></li>
              <li><a href="faq.html">Câu hỏi thường gặp</a></li>
              <li><a href="terms.html">Điều khoản</a></li>
              <li><a href="privacy.html">Chính sách bảo mật</a></li>
            </ul>
            </li>
            <li><a href="login.php">Đăng nhập</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

      </div>

    </div>

  </header>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="heading">
        <div class="container">
          <div class="row d-flex justify-content-center text-center">
            <div class="col-lg-8">

              <h1 class="heading-title">Liên hệ</h1>

              <p class="mb-0">
                Liên hệ với chúng tôi để được hỗ trợ thông tin về các dịch vụ,
                chuyên khoa, bác sĩ và lịch khám. Đội ngũ bệnh viện luôn sẵn sàng
                hỗ trợ và giải đáp các thắc mắc của người bệnh.
              </p>

            </div>
          </div>
        </div>
      </div>

      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.html">Trang chủ</a></li>
            <li class="current">Liên hệ</li>
          </ol>
        </div>
      </nav>

    </div>
    <!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-5">

          <!-- Contact Information -->
          <div class="col-lg-5">

            <div class="contact-info-wrapper">

              <div class="contact-info-item" data-aos="fade-up" data-aos-delay="100">

                <div class="info-icon">
                  <i class="bi bi-geo-alt"></i>
                </div>

                <div class="info-content">

                  <h3>Địa chỉ</h3>

                  <p>
                    19 Đường Nguyễn Hữu Thọ, phường Tân Hưng, Quận 7, TP. Hồ Chí Minh
                  </p>

                </div>

              </div>

              <div class="contact-info-item" data-aos="fade-up" data-aos-delay="200">

                <div class="info-icon">
                  <i class="bi bi-envelope"></i>
                </div>

                <div class="info-content">

                  <h3>Email</h3>

                  <p><?= contactEscape($contactEmail) ?></p>

                </div>

              </div>

              <div class="contact-info-item" data-aos="fade-up" data-aos-delay="300">

                <div class="info-icon">
                  <i class="bi bi-headset"></i>
                </div>

                <div class="info-content">

                  <h3>Thời gian hoạt động</h3>

                  <p>Thứ Hai - Thứ Sáu: 7:00 - 17:00</p>
                  <p>Thứ Bảy: 7:00 - 12:00</p>
                  <p>Cấp cứu: 24/7</p>

                </div>

              </div>

            </div>

          </div>

          <!-- Contact Form -->
          <div class="col-lg-7">

            <div class="contact-form-card"
                data-aos="fade-up"
                data-aos-delay="200">

              <h2>Gửi yêu cầu liên hệ</h2>

              <p class="mb-4">
                Nếu bạn có thắc mắc hoặc cần hỗ trợ về dịch vụ khám chữa bệnh,
                vui lòng gửi thông tin cho chúng tôi. Đội ngũ bệnh viện sẽ
                hỗ trợ bạn trong thời gian sớm nhất.
              </p>

              <form id="contact-form" action="contact.php#contact-form" method="post" class="contact-form">
                <input type="hidden" name="csrf_token" value="<?= contactEscape($csrfToken) ?>">
                <div class="contact-honeypot" aria-hidden="true">
                  <label for="website">Không điền trường này</label>
                  <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <?php if ($success !== ''): ?>
                  <div class="alert alert-success" role="status"><?= contactEscape($success) ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                  <div class="alert alert-danger" role="alert"><?= contactEscape($error) ?></div>
                <?php endif; ?>

                <div class="row g-4">
                  <div class="col-md-6">
                    <label class="form-label" for="name">Họ và tên <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="HoTen" id="name" maxlength="100" value="<?= contactEscape($formData['HoTen']) ?>" placeholder="Nhập họ và tên" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="Email" id="email" maxlength="150" value="<?= contactEscape($formData['Email']) ?>" placeholder="name@example.com" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input type="tel" class="form-control" name="SoDienThoai" id="phone" maxlength="20" value="<?= contactEscape($formData['SoDienThoai']) ?>" placeholder="Nhập số điện thoại">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="subject">Chủ đề <span class="text-danger">*</span></label>
                    <select class="form-select" name="ChuDe" id="subject" required>
                      <option value="">Chọn chủ đề liên hệ</option>
                      <?php foreach ($contactSubjects as $subject): ?>
                        <option value="<?= contactEscape($subject) ?>" <?= $formData['ChuDe'] === $subject ? 'selected' : '' ?>><?= contactEscape($subject) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="message">Nội dung liên hệ <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="NoiDung" id="message" rows="6" maxlength="3000" placeholder="Nhập nội dung cần hỗ trợ (không gửi thông tin bệnh án hoặc dữ liệu sức khỏe nhạy cảm tại đây)" required><?= contactEscape($formData['NoiDung']) ?></textarea>
                    <small class="form-text">Từ 10 đến 3.000 ký tự. Không sử dụng biểu mẫu này cho trường hợp cấp cứu.</small>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-submit">
                      <i class="bi bi-send me-2"></i>Gửi yêu cầu liên hệ
                    </button>
                    <p class="small text-muted mt-3 mb-0">Thông tin được sử dụng để tiếp nhận và phản hồi yêu cầu của bạn.</p>
                  </div>
                </div>
              </form>

            </div>

          </div>

        </div>

      </div>

      <!-- Map -->
      <div class="container-fluid map-container"
          data-aos="fade-up"
          data-aos-delay="200">

        <div class="map-overlay"></div>

        <iframe
          title="Bản đồ vị trí Bệnh viện Đa khoa Tôn Đức Thắng"
          src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d3920.025543701688!2d106.6991429!3d10.7325132!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528b2747a81a3%3A0x33c1813055acb613!2sTon%20Duc%20Thang%20University!5e0!3m2!1sen!2s!4v1794756551690!5m2!1sen!2s"
          width="100%"
          height="450"
          style="border:0;"
          allowfullscreen
          loading="lazy"
          referrerpolicy="strict-origin-when-cross-origin">
        </iframe>

      </div>

    </section>
    <!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer-16 footer position-relative">

    <div class="container">

      <div class="footer-main" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-start">

          <!-- Thông tin bệnh viện -->
          <div class="col-lg-5">
            <div class="brand-section">

              <a href="index.html" class="logo d-flex align-items-center mb-4">
                <span class="sitename">Bệnh viện</span>
              </a>

              <p class="brand-description">
                Cung cấp dịch vụ khám và chăm sóc sức khỏe với đội ngũ bác sĩ
                chuyên môn và hệ thống hỗ trợ đặt lịch khám thuận tiện.
              </p>

              <div class="contact-info mt-5">

                <div class="contact-item">
                  <i class="bi bi-geo-alt"></i>
                  <span>19 Đường Nguyễn Hữu Thọ, phường Tân Hưng, Quận 7, TP. Hồ Chí Minh</span>
                </div>

                <div class="contact-item">
                  <i class="bi bi-telephone"></i>
                  <span>115</span>
                </div>

                <div class="contact-item">
                  <i class="bi bi-envelope"></i>
                  <span><?= contactEscape($contactEmail) ?></span>
                </div>

              </div>

            </div>
          </div>

          <!-- Menu Footer -->
          <div class="col-lg-7">
            <div class="footer-nav-wrapper">

              <div class="row">

                <!-- Thông tin -->
                <div class="col-6 col-lg-3">
                  <div class="nav-column">

                    <h6>Thông tin</h6>

                    <nav class="footer-nav">
                      <a href="index.html">Trang chủ</a>
                      <a href="about.html">Giới thiệu</a>
                      <a href="doctors.php">Bác sĩ</a>
                      <a href="service-details.html">Dịch vụ</a>
                      <a href="contact.php">Liên hệ</a>
                    </nav>

                  </div>
                </div>

                <!-- Khoa -->
                <div class="col-6 col-lg-3">
                  <div class="nav-column">

                    <h6>Chuyên khoa</h6>

                    <nav class="footer-nav">
                      <a href="departments.html#departments-tabs-neurology">Khoa Nội</a>
                      <a href="departments.html#departments-tabs-surgery">Khoa Ngoại</a>
                      <a href="departments.html#departments-tabs-dental">Răng Hàm Mặt</a>
                      <a href="departments.html#departments-tabs-ophthalmology">Khoa Mắt</a>
                      <a href="departments.html#departments-tabs-cardiology">Khoa Tim mạch</a>
                    </nav>

                  </div>
                </div>

                <!-- Dịch vụ -->
                <div class="col-6 col-lg-3">
                  <div class="nav-column">

                    <h6>Dịch vụ</h6>

                    <nav class="footer-nav">
                      <a href="appointment.php">Đặt lịch khám</a>
                      <a href="doctors.php">Tìm bác sĩ</a>
                      <a href="contact.php">Liên hệ</a>
                      <a href="login.php">Đăng nhập</a>
                    </nav>

                  </div>
                </div>

                <!-- Hỗ trợ -->
                <div class="col-6 col-lg-3">
                  <div class="nav-column">

                    <h6>Hỗ trợ</h6>

                    <nav class="footer-nav">
                      <a href="appointment.php">Đặt lịch khám</a>
                      <a href="contact.php">Thông tin liên hệ</a>
                      <a href="contact.php">Vị trí bệnh viện</a>
                      <a href="contact.php">Hỗ trợ bệnh nhân</a>
                      <a href="tel:115">Cấp cứu</a>
                    </nav>

                  </div>
                </div>

              </div>

            </div>
          </div>

        </div>
      </div>

    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">

      <div class="container">

        <div class="bottom-content" data-aos="fade-up" data-aos-delay="300">

          <div class="row align-items-center">

            <div class="col-lg-6">
              <div class="copyright">

                <p>
                  © <span class="sitename">Bệnh viện</span>.
                  Mọi quyền được bảo lưu.
                </p>

              </div>
            </div>

            <div class="col-lg-6">

              <div class="legal-links">

                <a href="privacy.html">Chính sách bảo mật</a>
                <a href="terms.html">Điều khoản sử dụng</a>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#!" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>