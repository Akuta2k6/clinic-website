<?php
require_once __DIR__ . '/config/database.php';

$chuyenKhoa = $pdo->query("SELECT MaCK, TenChuyenKhoa FROM ChuyenKhoa WHERE TrangThai = 1 ORDER BY MaCK, TenChuyenKhoa")->fetchAll(PDO::FETCH_ASSOC);
$bacSi = $pdo->query("SELECT bs.MaBS, bs.MaCK, bs.HoTen, bs.HocVi, bs.ChuyenMon, bs.KinhNghiem, bs.Email, bs.SoDienThoai, bs.AnhDaiDien, ck.TenChuyenKhoa FROM BacSi bs INNER JOIN ChuyenKhoa ck ON bs.MaCK = ck.MaCK WHERE bs.TrangThai = 1 AND ck.TrangThai = 1 ORDER BY ck.MaCK, bs.HoTen")->fetchAll(PDO::FETCH_ASSOC);
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function anhBacSi(?string $path): string {
    $path = trim($path ?? '');
    if ($path !== '' && preg_match('~^assets/uploads/doctors/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)$~i', $path) && is_file(__DIR__ . '/' . $path)) return $path;
    return 'assets/img/health/doctor.jpg';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Bác sĩ</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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

  <!-- =======================================================
  * Template Name: Clinic
  * Template URL: https://bootstrapmade.com/clinic-bootstrap-template/
  * Updated: Jul 23 2025 with Bootstrap v5.3.7
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="doctors-page">

  <header id="header" class="header fixed-top">

    <div class="topbar d-flex align-items-center dark-background">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope d-flex align-items-center"><a
              href="mailto:contact@example.com">contact@example.com</a></i>
          <i class="bi bi-phone d-flex align-items-center ms-4"><span>+1 5589 55488 55</span></i>
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
          <!-- <img src="assets/img/logo.webp" alt=""> -->
          <h1 class="sitename">Clinic</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index.html">Trang chủ</a></li>
            <li><a href="about.html">Giới thiệu</a></li>
            <li class="dropdown"><a href="departments.html"><span>Khoa</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="departments.html#departments-tabs-neurology">Khoa Nội</a></li>  
              <li><a href="departments.html#departments-tabs-surgery">Khoa Ngoại</a></li>
              <li><a href="departments.html#departments-tabs-dental">Khoa Răng Hàm Mặt</a></li>
              <li><a href="departments.html#departments-tabs-ophthalmology">Khoa Mắt</a></li>
              <li><a href="departments.html#departments-tabs-cardiology">Khoa Tim Mạch</a></li>
            </ul>
            </li>
            <li class="dropdown"><a href="services.html"><span>Dịch vụ</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="">Tim mạch</a></li>
              <li><a href="">Nội khoa</a></li>
              <li><a href="">Ngoại khoa</a></li>
              <li><a href="">Răng hàm mặt</a></li>
              <li><a href="">Mắt</a></li>
              <li><a href="">Xét nghiệm</a></li>
              <li><a href="">Tiêm ngừa</a></li>
            </ul>
            </li>
            <li><a href="doctors.php" class="active">Bác sĩ</a></li>
            <li><a href="contact.html">Liên hệ</a></li>
            <li class="dropdown"><a href="#"><span>Thêm</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="department-details.html">Chi tiết khoa</a></li>
              <li><a href="service-details.html">Chi tiết dịch vụ</a></li>
              <li><a href="appointment.php">Đặt lịch khám</a></li>
              <li><a href="testimonials.html">Đánh giá</a></li>
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
              <h1 class="heading-title">Bác sĩ</h1>
              <p class="mb-0">
                Tìm hiểu thông tin về đội ngũ bác sĩ của bệnh viện và lựa chọn
                bác sĩ phù hợp với nhu cầu khám chữa bệnh. Bạn có thể xem
                chuyên khoa và đặt lịch khám trực tuyến một cách thuận tiện.
              </p>
            </div>
          </div>
        </div>
      </div>

      <nav class="breadcrumbs">
        <div class="container">
          <ol>
            <li><a href="index.html">Trang chủ</a></li>
            <li class="current">Bác sĩ</li>
          </ol>
        </div>
      </nav>
    </div>
    <!-- End Page Title -->

    <!-- Doctors Section -->
    <section id="doctors" class="doctors section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- Doctor Filter -->
        <div class="row justify-content-center mb-5">
          <div class="col-lg-6">
            <div class="text-center">
              <h3>Đội ngũ bác sĩ</h3>
              <p>
                Đội ngũ bác sĩ thuộc các chuyên khoa của bệnh viện,
                sẵn sàng hỗ trợ khám và tư vấn sức khỏe.
              </p>
            </div>
          </div>
        </div>

        <style>
          .specialty-filters { display:flex; flex-wrap:wrap; justify-content:center; gap:10px; margin:-12px 0 36px; }
          .specialty-filter-btn { background:#fff; color:var(--default-color); border:1px solid #dbe4ea; border-radius:999px; padding:9px 20px; font-weight:600; cursor:pointer; transition:background .2s,color .2s,border-color .2s; }
          .specialty-filter-btn:hover, .specialty-filter-btn.active { color:var(--contrast-color, #fff); background:var(--accent-color, #1977cc); border-color:var(--accent-color, #1977cc); }
          .doctor-filter-hidden { display:none !important; }
        </style>
        <div class="specialty-filters" role="group" aria-label="Lọc bác sĩ theo chuyên khoa">
          <button type="button" class="specialty-filter-btn active" data-filter="all" aria-pressed="true">Tất cả</button>
          <?php foreach ($chuyenKhoa as $khoa): ?>
            <button type="button" class="specialty-filter-btn" data-filter="<?= (int)$khoa['MaCK'] ?>" aria-pressed="false"><?= e($khoa['TenChuyenKhoa']) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="row gy-4" id="doctor-list">
          <?php if (!$bacSi): ?>
            <div class="col-12 text-center"><p>Hiện chưa có bác sĩ nào được công bố.</p></div>
          <?php endif; ?>
          <?php foreach ($bacSi as $bs): ?>
            <div class="col-lg-4 col-md-6 doctor-filter-item" data-specialty="<?= (int)$bs['MaCK'] ?>">
              <div class="doctor-card">
                <div class="doctor-image">
                  <img src="<?= e(anhBacSi($bs['AnhDaiDien'])) ?>" alt="Ảnh bác sĩ <?= e($bs['HoTen']) ?>" class="img-fluid">
                  <div class="doctor-overlay">
                    <div class="social-links">
                      <?php if (!empty($bs['Email'])): ?><a href="mailto:<?= e($bs['Email']) ?>" aria-label="Email bác sĩ"><i class="bi bi-envelope"></i></a><?php endif; ?>
                      <?php if (!empty($bs['SoDienThoai'])): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $bs['SoDienThoai'])) ?>" aria-label="Gọi bác sĩ"><i class="bi bi-phone"></i></a><?php endif; ?>
                    </div>
                  </div>
                </div>
                <div class="doctor-content">
                  <h4>BS. <?= e($bs['HoTen']) ?></h4>
                  <span class="specialty"><?= e($bs['HocVi'] ?: 'Bác sĩ') ?></span>
                  <p><?= e($bs['ChuyenMon'] ?: 'Khám và tư vấn sức khỏe thuộc ' . $bs['TenChuyenKhoa'] . '.') ?></p>
                  <div class="doctor-meta">
                    <div class="department"><i class="bi bi-building"></i><span><?= e($bs['TenChuyenKhoa']) ?></span></div>
                  </div>
                  <a href="appointment.php?MaBS=<?= rawurlencode($bs['MaBS']) ?>&amp;MaCK=<?= (int)$bs['MaCK'] ?>" class="btn-appointment">Đặt lịch khám</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p id="doctor-filter-empty" class="text-center mt-4" role="status" hidden>Hiện chưa có bác sĩ thuộc chuyên khoa này.</p>

      </div>

    </section>
    <!-- /Doctors Section -->

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
                  <span>+84 000 000 0000</span>
                </div>

                <div class="contact-item">
                  <i class="bi bi-envelope"></i>
                  <span>info@benhvien.vn</span>
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
                      <a href="services.html">Dịch vụ</a>
                      <a href="contact.html">Liên hệ</a>
                    </nav>

                  </div>
                </div>

                <!-- Khoa -->
                <div class="col-6 col-lg-3">
                  <div class="nav-column">

                    <h6>Chuyên khoa</h6>

                    <nav class="footer-nav">
                      <a href="services.html">Khoa Nội</a>
                      <a href="services.html">Khoa Ngoại</a>
                      <a href="services.html">Răng Hàm Mặt</a>
                      <a href="services.html">Khoa Mắt</a>
                      <a href="services.html">Khoa Tim mạch</a>
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
                      <a href="services.html">Khám chuyên khoa</a>
                      <a href="contact.html">Liên hệ</a>
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
                      <a href="contact.html">Thông tin liên hệ</a>
                      <a href="contact.html">Vị trí bệnh viện</a>
                      <a href="contact.html">Hỗ trợ bệnh nhân</a>
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
                  All rights reserved.
                </p>

              </div>
            </div>

            <div class="col-lg-6">

              <div class="legal-links">

                <a href="#!">Chính sách bảo mật</a>
                <a href="#!">Điều khoản sử dụng</a>
                <a href="#!">Chính sách</a>

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
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const buttons = document.querySelectorAll('.specialty-filter-btn');
      const doctors = document.querySelectorAll('#doctor-list .doctor-filter-item');
      const empty = document.getElementById('doctor-filter-empty');
      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          const filter = button.dataset.filter;
          let visible = 0;
          buttons.forEach(function (item) {
            const active = item === button;
            item.classList.toggle('active', active);
            item.setAttribute('aria-pressed', String(active));
          });
          doctors.forEach(function (doctor) {
            const show = filter === 'all' || doctor.dataset.specialty === filter;
            doctor.classList.toggle('doctor-filter-hidden', !show);
            if (show) visible++;
          });
          empty.hidden = visible !== 0;
        });
      });
    });
  </script>
</body>

</html>