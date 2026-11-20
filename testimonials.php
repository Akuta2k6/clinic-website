<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['review_csrf'])) {
    $_SESSION['review_csrf'] = bin2hex(random_bytes(32));
}

function reviewEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function reviewLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

$error = '';
$success = $_SESSION['review_flash'] ?? '';
unset($_SESSION['review_flash']);
$postedName = $_POST['TenNguoiDanhGia'] ?? '';
$postedContent = $_POST['NoiDung'] ?? '';
$postedStars = $_POST['SoSao'] ?? null;
$formName = is_string($postedName) ? trim($postedName) : '';
$formContent = is_string($postedContent) ? trim($postedContent) : '';
$validatedStars = is_scalar($postedStars) ? filter_var($postedStars, FILTER_VALIDATE_INT) : false;
$formStars = $validatedStars === false ? 0 : (int)$validatedStars;
$formAnonymous = ($_POST['AnDanh'] ?? '0') === '1';
$dbReady = true;
$reviews = [];
$reviewCount = 0;
$averageRating = 0.0;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$totalPages = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $postedHoneypot = $_POST['website'] ?? '';
    $honeypot = is_string($postedHoneypot) ? trim($postedHoneypot) : 'invalid';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['review_csrf'], $submittedToken)) {
        $error = 'Phiên gửi đánh giá đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif ($honeypot !== '') {
        $error = 'Không thể gửi đánh giá lúc này. Vui lòng thử lại.';
    } elseif (!$formAnonymous && $formName === '') {
        $error = 'Vui lòng nhập tên hoặc chọn đánh giá ẩn danh.';
    } elseif (!$formAnonymous && reviewLength($formName) > 100) {
        $error = 'Tên hiển thị không được vượt quá 100 ký tự.';
    } elseif (!in_array($formStars, [1, 2, 3, 4, 5], true)) {
        $error = 'Vui lòng chọn mức đánh giá từ 1 đến 5 sao.';
    } elseif ($formContent === '' || reviewLength($formContent) < 10) {
        $error = 'Nội dung đánh giá cần có ít nhất 10 ký tự.';
    } elseif (reviewLength($formContent) > 1000) {
        $error = 'Nội dung đánh giá không được vượt quá 1.000 ký tự.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO DanhGia (TenNguoiDanhGia, AnDanh, SoSao, NoiDung, NgayDanhGia, TrangThai)
                VALUES (:ten, :an_danh, :so_sao, :noi_dung, NOW(), 'HienThi')
            ");
            $stmt->execute([
                ':ten' => $formAnonymous ? null : $formName,
                ':an_danh' => $formAnonymous ? 1 : 0,
                ':so_sao' => $formStars,
                ':noi_dung' => $formContent,
            ]);

            $_SESSION['review_flash'] = 'Cảm ơn bạn đã chia sẻ trải nghiệm. Đánh giá của bạn đã được đăng.';
            $_SESSION['review_csrf'] = bin2hex(random_bytes(32));
            header('Location: testimonials.php#review-list');
            exit;
        } catch (PDOException $exception) {
            $error = 'Chưa thể lưu đánh giá. Vui lòng thử lại sau hoặc liên hệ phòng khám.';
        }
    }
}

try {
    $summaryStmt = $pdo->query("
        SELECT COUNT(*) AS TongDanhGia, COALESCE(AVG(SoSao), 0) AS DiemTrungBinh
        FROM DanhGia
        WHERE TrangThai = 'HienThi'
    ");
    $summary = $summaryStmt->fetch();
    $reviewCount = (int)($summary['TongDanhGia'] ?? 0);
    $averageRating = (float)($summary['DiemTrungBinh'] ?? 0);
    $totalPages = max(1, (int)ceil($reviewCount / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $reviewStmt = $pdo->prepare("
        SELECT MaDanhGia, TenNguoiDanhGia, AnDanh, SoSao, NoiDung, NgayDanhGia
        FROM DanhGia
        WHERE TrangThai = 'HienThi'
        ORDER BY NgayDanhGia DESC, MaDanhGia DESC
        LIMIT :limit OFFSET :offset
    ");
    $reviewStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $reviewStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $reviewStmt->execute();
    $reviews = $reviewStmt->fetchAll();
} catch (PDOException $exception) {
    $dbReady = false;
}

$csrfToken = $_SESSION['review_csrf'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Đánh giá</title>
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
  <link href="assets/css/reviews.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: Clinic
  * Template URL: https://bootstrapmade.com/clinic-bootstrap-template/
  * Updated: Jul 23 2025 with Bootstrap v5.3.7
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="testimonials-page">

  <header id="header" class="header fixed-top">

    <div class="topbar d-flex align-items-center dark-background">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope d-flex align-items-center"><a
              href="mailto:tdtuclinic@gmail.com">tdtuclinic@gmail.com</a></i>
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
        <a href="index.php" class="logo d-flex align-items-center">
          <!-- Uncomment the line below if you also wish to use an image logo -->
          <img src="assets/img/logo.jpg" alt="">
          <h1 class="sitename">Bệnh viện đa khoa Tôn Đức Thắng</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index.php">Trang chủ</a></li>
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
            <li><a href="service-details.html">Dịch vụ</a></li>
            <li><a href="doctors.php">Bác sĩ</a></li>
            <li><a href="contact.html">Liên hệ</a></li>
            <li class="dropdown"><a href="#"><span>Thêm</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="testimonials.php" class="active">Đánh giá</a></li>
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



    <section class="clinic-review-page section">
      <div class="container" data-aos="fade-up">
        <div class="clinic-review-intro">
          <span class="clinic-review-eyebrow"><i class="bi bi-chat-heart-fill"></i> Ý KIẾN KHÁCH HÀNG</span>
          <h2>Trải nghiệm của bạn là động lực để chúng tôi tốt hơn</h2>
          <p>Hãy chia sẻ cảm nhận về quá trình tìm hiểu thông tin, đặt lịch và sử dụng dịch vụ tại phòng khám.</p>
        </div>

        <div class="clinic-review-summary">
          <div class="clinic-review-summary-icon"><i class="bi bi-star-half"></i></div>
          <div class="clinic-review-summary-score">
            <strong><?= number_format($averageRating, 1) ?></strong>
            <span>/ 5.0</span>
          </div>
          <div class="clinic-review-summary-detail">
            <div class="clinic-review-summary-stars" aria-label="Điểm trung bình <?= number_format($averageRating, 1) ?> trên 5">
              <?php for ($star = 1; $star <= 5; $star++): ?>
                <i class="bi <?= $star <= (int)round($averageRating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
              <?php endfor; ?>
            </div>
            <span><?= number_format($reviewCount) ?> lượt đánh giá từ khách hàng</span>
          </div>
          <div class="clinic-review-summary-note"><i class="bi bi-shield-check"></i> Ý kiến được cập nhật trực tiếp từ hệ thống</div>
        </div>

        <div class="clinic-review-layout">
          <section class="clinic-review-form-card" id="review-form">
            <div class="clinic-review-form-heading">
              <div class="clinic-review-form-icon"><i class="bi bi-pencil-square"></i></div>
              <div>
                <span>GỬI ĐÁNH GIÁ</span>
                <h3>Chia sẻ trải nghiệm của bạn</h3>
                <p>Đánh giá của bạn giúp chúng tôi cải thiện chất lượng phục vụ.</p>
              </div>
            </div>

            <?php if ($success !== ''): ?>
              <div class="clinic-review-alert clinic-review-alert-success" role="status"><i class="bi bi-check-circle-fill"></i><?= reviewEscape($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
              <div class="clinic-review-alert clinic-review-alert-error" role="alert"><i class="bi bi-exclamation-circle-fill"></i><?= reviewEscape($error) ?></div>
            <?php endif; ?>
            <?php if (!$dbReady): ?>
              <div class="clinic-review-alert clinic-review-alert-error" role="alert"><i class="bi bi-database-exclamation"></i> Cơ sở dữ liệu đánh giá chưa sẵn sàng. Vui lòng chạy file migration tạo bảng DanhGia.</div>
            <?php endif; ?>

            <form method="post" action="testimonials.php#review-form" class="clinic-review-form">
              <input type="hidden" name="csrf_token" value="<?= reviewEscape($csrfToken) ?>">
              <div class="clinic-review-honeypot" aria-hidden="true">
                <label for="review-website">Website</label>
                <input id="review-website" name="website" type="text" tabindex="-1" autocomplete="off">
              </div>

              <div class="clinic-review-name-row">
                <div class="clinic-review-field clinic-review-name-field">
                  <label for="TenNguoiDanhGia">Tên hiển thị</label>
                  <input id="TenNguoiDanhGia" name="TenNguoiDanhGia" type="text" maxlength="100" value="<?= reviewEscape($formName) ?>" placeholder="Nhập tên của bạn" <?= $formAnonymous ? 'disabled' : '' ?> <?= !$formAnonymous ? 'required' : '' ?>>
                </div>
                <label class="clinic-review-anonymous" for="AnDanh">
                  <input id="AnDanh" name="AnDanh" type="checkbox" value="1" <?= $formAnonymous ? 'checked' : '' ?>>
                  <span class="clinic-review-checkmark"><i class="bi bi-check"></i></span>
                  <span><strong>Đánh giá ẩn danh</strong><small>Không hiển thị tên của bạn</small></span>
                </label>
              </div>

              <fieldset class="clinic-review-field clinic-review-rating-field">
                <legend>Mức độ hài lòng <span>*</span></legend>
                <div class="clinic-review-star-picker" role="radiogroup" aria-label="Chọn số sao đánh giá">
                  <?php for ($star = 1; $star <= 5; $star++): ?>
                    <label class="clinic-review-star-option <?= $formStars >= $star ? 'is-active' : '' ?>">
                      <input type="radio" name="SoSao" value="<?= $star ?>" <?= $formStars === $star ? 'checked' : '' ?> required>
                      <i class="bi bi-star-fill"></i>
                      <span><?= $star ?></span>
                    </label>
                  <?php endfor; ?>
                </div>
                <small class="clinic-review-rating-hint" id="review-rating-hint">Chọn từ 1 đến 5 sao</small>
              </fieldset>

              <div class="clinic-review-field">
                <div class="clinic-review-label-row"><label for="NoiDung">Nội dung đánh giá <span>*</span></label><small><span id="review-char-count"><?= reviewLength($formContent) ?></span>/1.000</small></div>
                <textarea id="NoiDung" name="NoiDung" rows="5" maxlength="1000" placeholder="Hãy chia sẻ điều bạn hài lòng hoặc điều chúng tôi có thể cải thiện..." required><?= reviewEscape($formContent) ?></textarea>
              </div>

              <div class="clinic-review-form-bottom">
                <span><i class="bi bi-lock-fill"></i> Thông tin của bạn được sử dụng để hiển thị đánh giá theo lựa chọn.</span>
                <button type="submit" class="clinic-review-submit" <?= !$dbReady ? 'disabled' : '' ?>>Gửi đánh giá <i class="bi bi-arrow-right"></i></button>
              </div>
            </form>
          </section>

          <aside class="clinic-review-aside">
            <div class="clinic-review-aside-card">
              <div class="clinic-review-aside-icon"><i class="bi bi-heart-pulse-fill"></i></div>
              <h3>Mỗi ý kiến đều có giá trị</h3>
              <p>Chúng tôi trân trọng mọi chia sẻ để cải thiện trải nghiệm thăm khám và hỗ trợ khách hàng.</p>
              <ul>
                <li><i class="bi bi-check-circle-fill"></i> Chia sẻ chân thực</li>
                <li><i class="bi bi-check-circle-fill"></i> Có thể chọn ẩn danh</li>
                <li><i class="bi bi-check-circle-fill"></i> Hiển thị sau khi gửi</li>
              </ul>
            </div>
            <div class="clinic-review-aside-help"><i class="bi bi-chat-dots"></i><div><strong>Cần hỗ trợ thêm?</strong><a href="contact.html">Liên hệ với phòng khám <i class="bi bi-arrow-up-right"></i></a></div></div>
          </aside>
        </div>
      </div>
    </section>

    <section class="clinic-review-list-section section" id="review-list">
      <div class="container" data-aos="fade-up">
        <div class="clinic-review-list-heading">
          <div>
            <span class="clinic-review-eyebrow"><i class="bi bi-chat-square-heart"></i> CẢM NHẬN TỪ KHÁCH HÀNG</span>
            <h2>Những chia sẻ mới nhất</h2>
            <p>Các đánh giá được lấy trực tiếp từ cơ sở dữ liệu của phòng khám.</p>
          </div>
          <div class="clinic-review-list-count"><strong><?= number_format($reviewCount) ?></strong><span>đánh giá</span></div>
        </div>

        <?php if (!$dbReady): ?>
          <div class="clinic-review-empty"><i class="bi bi-database"></i><h3>Chưa thể tải đánh giá</h3><p>Vui lòng kiểm tra kết nối và cập nhật bảng dữ liệu đánh giá.</p></div>
        <?php elseif (!$reviews): ?>
          <div class="clinic-review-empty"><i class="bi bi-chat-square-text"></i><h3>Chưa có đánh giá nào</h3><p>Hãy là người đầu tiên chia sẻ trải nghiệm của bạn với phòng khám.</p><a href="#review-form">Viết đánh giá đầu tiên <i class="bi bi-arrow-up-right"></i></a></div>
        <?php else: ?>
          <div class="clinic-review-grid">
            <?php foreach ($reviews as $review): ?>
              <?php
                $reviewName = !empty($review['AnDanh']) || empty($review['TenNguoiDanhGia']) ? 'Khách ẩn danh' : $review['TenNguoiDanhGia'];
                $reviewInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($reviewName, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($reviewName, 0, 1));
                $reviewDate = date('d/m/Y', strtotime($review['NgayDanhGia']));
              ?>
              <article class="clinic-review-card">
                <div class="clinic-review-card-top">
                  <div class="clinic-review-avatar"><?= reviewEscape($reviewInitial) ?></div>
                  <div class="clinic-review-author"><strong><?= reviewEscape($reviewName) ?></strong><span><?= reviewEscape($reviewDate) ?></span></div>
                  <span class="clinic-review-quote"><i class="bi bi-quote"></i></span>
                </div>
                <div class="clinic-review-card-stars" aria-label="<?= (int)$review['SoSao'] ?> trên 5 sao">
                  <?php for ($star = 1; $star <= 5; $star++): ?>
                    <i class="bi <?= $star <= (int)$review['SoSao'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  <?php endfor; ?>
                </div>
                <p><?= nl2br(reviewEscape($review['NoiDung'])) ?></p>
                <div class="clinic-review-card-bottom"><i class="bi bi-patch-check-fill"></i> Khách hàng chia sẻ</div>
              </article>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <nav class="clinic-review-pagination" aria-label="Phân trang đánh giá">
              <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>#review-list" aria-label="Trang trước"><i class="bi bi-chevron-left"></i></a><?php endif; ?>
              <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <?php if ($pageNumber === $page): ?><span class="is-current" aria-current="page"><?= $pageNumber ?></span><?php else: ?><a href="?page=<?= $pageNumber ?>#review-list"><?= $pageNumber ?></a><?php endif; ?>
              <?php endfor; ?>
              <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>#review-list" aria-label="Trang tiếp theo"><i class="bi bi-chevron-right"></i></a><?php endif; ?>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
    </main>

  <footer id="footer" class="footer-16 footer position-relative">

    <div class="container">

      <div class="footer-main" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-start">

          <!-- Thông tin bệnh viện -->
          <div class="col-lg-5">
            <div class="brand-section">

              <a href="index.php" class="logo d-flex align-items-center mb-4">
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
                  <span>tdtuclinic@gmail.com</span>
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
                      <a href="index.php">Trang chủ</a>
                      <a href="about.html">Giới thiệu</a>
                      <a href="doctors.php">Bác sĩ</a>
                      <a href="service-details.html">Dịch vụ</a>
                      <a href="contact.html">Liên hệ</a>
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
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const anonymous = document.getElementById('AnDanh');
    const nameInput = document.getElementById('TenNguoiDanhGia');
    const ratingInputs = document.querySelectorAll('.clinic-review-star-option input');
    const ratingHint = document.getElementById('review-rating-hint');
    const content = document.getElementById('NoiDung');
    const counter = document.getElementById('review-char-count');

    if (anonymous && nameInput) {
        const updateName = function () {
            nameInput.disabled = anonymous.checked;
            nameInput.required = !anonymous.checked;
            if (anonymous.checked) nameInput.value = '';
        };
        anonymous.addEventListener('change', updateName);
        updateName();
    }

    const ratingLabels = ['Chưa hài lòng', 'Cần cải thiện', 'Bình thường', 'Hài lòng', 'Rất hài lòng'];
    const updateRating = function () {
        let selected = 0;
        ratingInputs.forEach(function (input) {
            if (input.checked) selected = Number(input.value);
        });
        document.querySelectorAll('.clinic-review-star-option').forEach(function (label) {
            const input = label.querySelector('input');
            label.classList.toggle('is-active', Number(input.value) <= selected);
        });
        if (ratingHint) ratingHint.textContent = selected ? ratingLabels[selected - 1] : 'Chọn từ 1 đến 5 sao';
    };
    ratingInputs.forEach(function (input) {
        input.addEventListener('change', updateRating);
    });
    updateRating();

    if (content && counter) {
        content.addEventListener('input', function () {
            counter.textContent = content.value.length;
        });
    }
});
</script>

</body>

</html>