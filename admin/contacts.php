<?php
require_once __DIR__ . '/../auth/check.php';
requireRole('Admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/portal-layout.php';
require_once __DIR__ . '/../helpers/contact-mail.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function adminContactEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminContactLength(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);
    return $count === false ? strlen($value) : $count;
}

function adminContactRedirect(int $id, string $keyword, string $status, int $page, string $notice): void
{
    $params = [
        'q' => $keyword,
        'status' => $status,
        'page' => max(1, $page),
        'notice' => $notice
    ];
    if ($id > 0) {
        $params['id'] = $id;
    }
    header('Location: contacts.php?' . http_build_query($params));
    exit;
}

$statusLabels = [
    'Moi' => ['Mới', 'status-pending'],
    'DangXuLy' => ['Đang xử lý', 'status-confirmed'],
    'DaPhanHoi' => ['Đã phản hồi', 'status-completed'],
    'DaDong' => ['Đã đóng', 'status-neutral']
];
$emailStatusLabels = [
    'ChoGui' => ['Chờ gửi', 'status-pending'],
    'DaGui' => ['Đã gửi', 'status-completed'],
    'ThatBai' => ['Gửi thất bại', 'status-cancelled']
];

if (empty($_SESSION['admin_contacts_csrf'])) {
    $_SESSION['admin_contacts_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_contacts_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $contactId = filter_var($_POST['MaLienHe'] ?? 0, FILTER_VALIDATE_INT);
    $contactId = $contactId !== false && $contactId > 0 ? (int)$contactId : 0;
    $postedKeyword = $_POST['return_q'] ?? '';
    $keyword = is_string($postedKeyword) ? trim($postedKeyword) : '';
    $postedStatus = $_POST['return_status'] ?? '';
    $filterStatus = is_string($postedStatus) ? $postedStatus : '';
    $pageNumber = max(1, (int)($_POST['return_page'] ?? 1));

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'csrf_error');
    }

    try {
        if ($action === 'update_status') {
            $postedNewStatus = $_POST['TrangThai'] ?? '';
            $newStatus = is_string($postedNewStatus) ? $postedNewStatus : '';
            if (!$contactId || !isset($statusLabels[$newStatus])) {
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'invalid');
            }
            $stmt = $pdo->prepare('UPDATE LienHe SET TrangThai = ?, NgayCapNhat = NOW() WHERE MaLienHe = ?');
            $stmt->execute([$newStatus, $contactId]);
            adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'updated');
        }

        if ($action === 'delete') {
            if (!$contactId) {
                adminContactRedirect(0, $keyword, $filterStatus, $pageNumber, 'invalid');
            }
            $stmt = $pdo->prepare('DELETE FROM LienHe WHERE MaLienHe = ?');
            $stmt->execute([$contactId]);
            adminContactRedirect(0, $keyword, $filterStatus, $pageNumber, 'deleted');
        }

        if ($action === 'reply') {
            $postedReply = $_POST['NoiDungPhanHoi'] ?? '';
            $reply = is_string($postedReply) ? trim($postedReply) : '';
            if (!$contactId || adminContactLength($reply) < 5 || adminContactLength($reply) > 5000) {
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'invalid_reply');
            }

            $stmt = $pdo->prepare('SELECT * FROM LienHe WHERE MaLienHe = ? LIMIT 1');
            $stmt->execute([$contactId]);
            $contact = $stmt->fetch();
            if (!$contact) {
                adminContactRedirect(0, $keyword, $filterStatus, $pageNumber, 'not_found');
            }

            $adminName = (string)($_SESSION['user']['TenDangNhap'] ?? 'Quản trị viên');
            $adminId = (int)($_SESSION['user']['MaTK'] ?? 0);
            $insert = $pdo->prepare(
                "INSERT INTO LienHePhanHoi (MaLienHe, MaTK, TenNguoiPhanHoi, NoiDung, TrangThaiEmail)\n                 VALUES (?, ?, ?, ?, 'ChoGui')"
            );
            $insert->execute([$contactId, $adminId > 0 ? $adminId : null, $adminName, $reply]);
            $replyId = (int)$pdo->lastInsertId();

            try {
                clinicContactSendReply($contact, $reply);
                $updateReply = $pdo->prepare("UPDATE LienHePhanHoi SET TrangThaiEmail = 'DaGui', ThongBaoLoi = NULL WHERE MaPhanHoi = ?");
                $updateReply->execute([$replyId]);
                $updateContact = $pdo->prepare("UPDATE LienHe SET TrangThai = 'DaPhanHoi', NgayCapNhat = NOW() WHERE MaLienHe = ?");
                $updateContact->execute([$contactId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'sent');
            } catch (Throwable $mailError) {
                $updateReply = $pdo->prepare("UPDATE LienHePhanHoi SET TrangThaiEmail = 'ThatBai', ThongBaoLoi = ? WHERE MaPhanHoi = ?");
                $updateReply->execute([substr($mailError->getMessage(), 0, 500), $replyId]);
                $updateContact = $pdo->prepare("UPDATE LienHe SET TrangThai = 'DangXuLy', NgayCapNhat = NOW() WHERE MaLienHe = ? AND TrangThai <> 'DaDong'");
                $updateContact->execute([$contactId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'mail_failed');
            }
        }

        if ($action === 'resend_notification' || $action === 'resend_confirmation') {
            $stmt = $pdo->prepare('SELECT * FROM LienHe WHERE MaLienHe = ? LIMIT 1');
            $stmt->execute([$contactId]);
            $contact = $stmt->fetch();
            if (!$contact) {
                adminContactRedirect(0, $keyword, $filterStatus, $pageNumber, 'not_found');
            }

            if ($action === 'resend_notification') {
                try {
                    clinicContactSendNotification($contact);
                    $update = $pdo->prepare("UPDATE LienHe SET TrangThaiThongBao = 'DaGui' WHERE MaLienHe = ?");
                    $update->execute([$contactId]);
                    adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'notification_resent');
                } catch (Throwable $mailError) {
                    $update = $pdo->prepare("UPDATE LienHe SET TrangThaiThongBao = 'ThatBai' WHERE MaLienHe = ?");
                    $update->execute([$contactId]);
                    adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'notification_failed');
                }
            }

            try {
                clinicContactSendConfirmation($contact);
                $update = $pdo->prepare("UPDATE LienHe SET TrangThaiXacNhan = 'DaGui' WHERE MaLienHe = ?");
                $update->execute([$contactId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'confirmation_resent');
            } catch (Throwable $mailError) {
                $update = $pdo->prepare("UPDATE LienHe SET TrangThaiXacNhan = 'ThatBai' WHERE MaLienHe = ?");
                $update->execute([$contactId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'confirmation_failed');
            }
        }

        if ($action === 'resend_reply') {
            $replyId = filter_var($_POST['MaPhanHoi'] ?? 0, FILTER_VALIDATE_INT);
            $replyId = $replyId !== false && $replyId > 0 ? (int)$replyId : 0;
            $stmt = $pdo->prepare(
                'SELECT lh.*, lhp.NoiDung AS NoiDungPhanHoi
                 FROM LienHePhanHoi lhp
                 JOIN LienHe lh ON lh.MaLienHe = lhp.MaLienHe
                 WHERE lhp.MaPhanHoi = ? AND lhp.MaLienHe = ? LIMIT 1'
            );
            $stmt->execute([$replyId, $contactId]);
            $replyRecord = $stmt->fetch();
            if (!$replyRecord) {
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'not_found');
            }

            try {
                clinicContactSendReply($replyRecord, $replyRecord['NoiDungPhanHoi']);
                $updateReply = $pdo->prepare("UPDATE LienHePhanHoi SET TrangThaiEmail = 'DaGui', ThongBaoLoi = NULL WHERE MaPhanHoi = ?");
                $updateReply->execute([$replyId]);
                $updateContact = $pdo->prepare("UPDATE LienHe SET TrangThai = 'DaPhanHoi', NgayCapNhat = NOW() WHERE MaLienHe = ?");
                $updateContact->execute([$contactId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'resent');
            } catch (Throwable $mailError) {
                $updateReply = $pdo->prepare("UPDATE LienHePhanHoi SET TrangThaiEmail = 'ThatBai', ThongBaoLoi = ? WHERE MaPhanHoi = ?");
                $updateReply->execute([substr($mailError->getMessage(), 0, 500), $replyId]);
                adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'resend_failed');
            }
        }

        adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'invalid');
    } catch (PDOException $exception) {
        adminContactRedirect($contactId, $keyword, $filterStatus, $pageNumber, 'database_error');
    }
}

$queryKeyword = $_GET['q'] ?? '';
$keyword = is_string($queryKeyword) ? trim($queryKeyword) : '';
$queryStatus = $_GET['status'] ?? '';
$filterStatus = is_string($queryStatus) ? $queryStatus : '';
if (!isset($statusLabels[$filterStatus])) {
    $filterStatus = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$where = [];
$params = [];

if ($filterStatus !== '') {
    $where[] = 'TrangThai = :status';
    $params[':status'] = $filterStatus;
}
if ($keyword !== '') {
    $where[] = '(HoTen LIKE :keyword1 OR Email LIKE :keyword2 OR SoDienThoai LIKE :keyword3 OR ChuDe LIKE :keyword4 OR NoiDung LIKE :keyword5)';
    $searchValue = '%' . $keyword . '%';
    $params[':keyword1'] = $searchValue;
    $params[':keyword2'] = $searchValue;
    $params[':keyword3'] = $searchValue;
    $params[':keyword4'] = $searchValue;
    $params[':keyword5'] = $searchValue;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM LienHe' . $whereSql);
$totalStmt->execute($params);
$totalRows = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    'SELECT MaLienHe, HoTen, Email, SoDienThoai, ChuDe, NgayGui, TrangThai, TrangThaiThongBao, TrangThaiXacNhan
     FROM LienHe' . $whereSql . '
     ORDER BY FIELD(TrangThai, \'Moi\', \'DangXuLy\', \'DaPhanHoi\', \'DaDong\'), NgayGui DESC, MaLienHe DESC
     LIMIT :limit OFFSET :offset'
);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$contacts = $listStmt->fetchAll();

$stats = $pdo->query(
    "SELECT COUNT(*) AS total,
        SUM(TrangThai = 'Moi') AS new_count,
        SUM(TrangThai = 'DangXuLy') AS processing_count,
        SUM(TrangThai = 'DaPhanHoi') AS replied_count,
        SUM(TrangThai = 'DaDong') AS closed_count
     FROM LienHe"
)->fetch();

$selectedId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
$selectedId = $selectedId !== false && $selectedId > 0 ? (int)$selectedId : 0;
$selectedContact = null;
$replyHistory = [];
if ($selectedId > 0) {
    $detailStmt = $pdo->prepare('SELECT * FROM LienHe WHERE MaLienHe = ? LIMIT 1');
    $detailStmt->execute([$selectedId]);
    $selectedContact = $detailStmt->fetch() ?: null;

    if ($selectedContact) {
        $replyStmt = $pdo->prepare('SELECT * FROM LienHePhanHoi WHERE MaLienHe = ? ORDER BY NgayPhanHoi DESC, MaPhanHoi DESC');
        $replyStmt->execute([$selectedId]);
        $replyHistory = $replyStmt->fetchAll();
    }
}

$notice = (string)($_GET['notice'] ?? '');
$noticeMessages = [
    'updated' => ['Đã cập nhật trạng thái liên hệ.', 'success'],
    'deleted' => ['Đã xóa liên hệ và lịch sử phản hồi liên quan.', 'success'],
    'sent' => ['Đã gửi phản hồi đến email khách hàng.', 'success'],
    'resent' => ['Đã gửi lại email phản hồi.', 'success'],
    'mail_failed' => ['Không gửi được email. Nội dung phản hồi đã được lưu để bạn có thể gửi lại.', 'danger'],
    'resend_failed' => ['Gửi lại email chưa thành công. Bạn có thể thử lại sau.', 'danger'],
    'notification_resent' => ['Đã gửi lại email thông báo đến bệnh viện.', 'success'],
    'notification_failed' => ['Không gửi lại được email thông báo đến bệnh viện.', 'danger'],
    'confirmation_resent' => ['Đã gửi lại email xác nhận cho khách hàng.', 'success'],
    'confirmation_failed' => ['Không gửi lại được email xác nhận cho khách hàng.', 'danger'],
    'invalid' => ['Thao tác không hợp lệ.', 'warning'],
    'invalid_reply' => ['Nội dung phản hồi phải có từ 5 đến 5.000 ký tự.', 'warning'],
    'not_found' => ['Không tìm thấy liên hệ hoặc phản hồi cần xử lý.', 'warning'],
    'csrf_error' => ['Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'warning'],
    'database_error' => ['Không thể hoàn tất thao tác do lỗi cơ sở dữ liệu.', 'danger']
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quản lý liên hệ</title>
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/contact-admin.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/img/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/img/apple-touch-icon.png">
</head>
<body class="clinic-dashboard-page">
<?php clinicPortalShellStart('Admin', 'Quản lý liên hệ', 'contacts'); ?>
<div class="container-fluid contact-admin-wrap py-4">
    <?php if (isset($noticeMessages[$notice])): ?>
        <div class="alert alert-<?= adminContactEscape($noticeMessages[$notice][1]) ?>" role="status"><?= adminContactEscape($noticeMessages[$notice][0]) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="contact-admin-title">Hộp thư liên hệ</h2>
            <p class="text-muted mb-0">Tiếp nhận, theo dõi và phản hồi các yêu cầu từ khách hàng.</p>
        </div>
        <span class="contact-total"><i class="bi bi-inbox me-2"></i><?= (int)($stats['total'] ?? 0) ?> yêu cầu</span>
    </div>

    <div class="contact-stat-grid">
        <a class="contact-stat-card" href="contacts.php?status=Moi">
            <span class="contact-stat-icon stat-blue"><i class="bi bi-envelope"></i></span>
            <span><small>Mới tiếp nhận</small><strong><?= (int)($stats['new_count'] ?? 0) ?></strong></span>
        </a>
        <a class="contact-stat-card" href="contacts.php?status=DangXuLy">
            <span class="contact-stat-icon stat-amber"><i class="bi bi-hourglass-split"></i></span>
            <span><small>Đang xử lý</small><strong><?= (int)($stats['processing_count'] ?? 0) ?></strong></span>
        </a>
        <a class="contact-stat-card" href="contacts.php?status=DaPhanHoi">
            <span class="contact-stat-icon stat-green"><i class="bi bi-reply-all"></i></span>
            <span><small>Đã phản hồi</small><strong><?= (int)($stats['replied_count'] ?? 0) ?></strong></span>
        </a>
        <a class="contact-stat-card" href="contacts.php?status=DaDong">
            <span class="contact-stat-icon stat-gray"><i class="bi bi-archive"></i></span>
            <span><small>Đã đóng</small><strong><?= (int)($stats['closed_count'] ?? 0) ?></strong></span>
        </a>
    </div>

    <div class="contact-admin-panel">
        <form method="get" class="contact-filter-form">
            <div class="contact-search-box">
                <i class="bi bi-search"></i>
                <input type="search" name="q" value="<?= adminContactEscape($keyword) ?>" placeholder="Tìm tên, email, số điện thoại, chủ đề...">
            </div>
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <?php foreach ($statusLabels as $value => [$label, $class]): ?>
                    <option value="<?= adminContactEscape($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= adminContactEscape($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button>
            <a href="contacts.php" class="btn btn-light">Xóa lọc</a>
        </form>

        <div class="table-responsive">
            <table class="table contact-admin-table align-middle">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Người gửi</th>
                        <th>Chủ đề</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <?php [$statusLabel, $statusClass] = $statusLabels[$contact['TrangThai']] ?? [$contact['TrangThai'], 'status-neutral']; ?>
                        <tr class="<?= $selectedId === (int)$contact['MaLienHe'] ? 'selected-row' : '' ?>">
                            <td><strong>#<?= (int)$contact['MaLienHe'] ?></strong></td>
                            <td>
                                <strong><?= adminContactEscape($contact['HoTen']) ?></strong>
                                <small><?= adminContactEscape($contact['Email']) ?></small>
                                <?php if (!empty($contact['SoDienThoai'])): ?><small><?= adminContactEscape($contact['SoDienThoai']) ?></small><?php endif; ?>
                            </td>
                            <td><?= adminContactEscape($contact['ChuDe']) ?></td>
                            <td><?= adminContactEscape(date('d/m/Y H:i', strtotime($contact['NgayGui']))) ?></td>
                            <td><span class="status-pill <?= adminContactEscape($statusClass) ?>"><?= adminContactEscape($statusLabel) ?></span></td>
                            <td><a class="contact-open-link" href="?<?= adminContactEscape(http_build_query(['id' => (int)$contact['MaLienHe'], 'q' => $keyword, 'status' => $filterStatus, 'page' => $page])) ?>">Chi tiết <i class="bi bi-arrow-up-right"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$contacts): ?>
                        <tr><td colspan="6"><div class="contact-empty"><i class="bi bi-inbox"></i><strong>Chưa có yêu cầu liên hệ</strong><span>Các tin nhắn khách hàng gửi sẽ xuất hiện tại đây.</span></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="contact-pagination" aria-label="Phân trang liên hệ">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="<?= $p === $page ? 'active' : '' ?>" href="?<?= adminContactEscape(http_build_query(['q' => $keyword, 'status' => $filterStatus, 'page' => $p, 'id' => $selectedId ?: null])) ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
        <p class="contact-list-meta">Hiển thị <?= count($contacts) ? $offset + 1 : 0 ?>–<?= min($offset + count($contacts), $totalRows) ?> trong tổng số <?= $totalRows ?> yêu cầu</p>
    </div>

    <?php if ($selectedContact): ?>
        <?php [$selectedStatusLabel, $selectedStatusClass] = $statusLabels[$selectedContact['TrangThai']] ?? [$selectedContact['TrangThai'], 'status-neutral']; ?>
        <section class="contact-detail-panel" id="contact-detail">
            <div class="contact-detail-heading">
                <div>
                    <span class="contact-detail-kicker">CHI TIẾT YÊU CẦU #<?= (int)$selectedContact['MaLienHe'] ?></span>
                    <h3><?= adminContactEscape($selectedContact['ChuDe']) ?></h3>
                    <p>Gửi lúc <?= adminContactEscape(date('d/m/Y H:i', strtotime($selectedContact['NgayGui']))) ?></p>
                </div>
                <span class="status-pill <?= adminContactEscape($selectedStatusClass) ?>"><?= adminContactEscape($selectedStatusLabel) ?></span>
            </div>

            <div class="contact-detail-grid">
                <div class="contact-message-card">
                    <h4>Thông tin người gửi</h4>
                    <dl>
                        <div><dt>Họ và tên</dt><dd><?= adminContactEscape($selectedContact['HoTen']) ?></dd></div>
                        <div><dt>Email</dt><dd><a href="mailto:<?= adminContactEscape($selectedContact['Email']) ?>"><?= adminContactEscape($selectedContact['Email']) ?></a></dd></div>
                        <div><dt>Số điện thoại</dt><dd><?= adminContactEscape($selectedContact['SoDienThoai'] ?: 'Không cung cấp') ?></dd></div>
                        <div><dt>Chủ đề</dt><dd><?= adminContactEscape($selectedContact['ChuDe']) ?></dd></div>
                    </dl>
                    <h4 class="mt-4">Nội dung khách hàng</h4>
                    <div class="contact-message-body"><?= nl2br(adminContactEscape($selectedContact['NoiDung'])) ?></div>

                    <div class="contact-email-status">
                        <div><span>Email thông báo bệnh viện</span><?php [$label, $class] = $emailStatusLabels[$selectedContact['TrangThaiThongBao']] ?? [$selectedContact['TrangThaiThongBao'], 'status-neutral']; ?><span class="status-pill <?= adminContactEscape($class) ?>"><?= adminContactEscape($label) ?></span></div>
                        <?php if ($selectedContact['TrangThaiThongBao'] !== 'DaGui'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                                <input type="hidden" name="action" value="resend_notification">
                                <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                                <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                                <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                                <input type="hidden" name="return_page" value="<?= $page ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Gửi lại email thông báo</button>
                            </form>
                        <?php endif; ?>
                        <div><span>Email xác nhận khách hàng</span><?php [$label, $class] = $emailStatusLabels[$selectedContact['TrangThaiXacNhan']] ?? [$selectedContact['TrangThaiXacNhan'], 'status-neutral']; ?><span class="status-pill <?= adminContactEscape($class) ?>"><?= adminContactEscape($label) ?></span></div>
                        <?php if ($selectedContact['TrangThaiXacNhan'] !== 'DaGui'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                                <input type="hidden" name="action" value="resend_confirmation">
                                <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                                <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                                <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                                <input type="hidden" name="return_page" value="<?= $page ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Gửi lại email xác nhận</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="contact-action-column">
                    <div class="contact-action-card">
                        <h4>Cập nhật trạng thái</h4>
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                            <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                            <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                            <input type="hidden" name="return_page" value="<?= $page ?>">
                            <select name="TrangThai" class="form-select">
                                <?php foreach ($statusLabels as $value => [$label, $class]): ?>
                                    <option value="<?= adminContactEscape($value) ?>" <?= $selectedContact['TrangThai'] === $value ? 'selected' : '' ?>><?= adminContactEscape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
                        </form>
                    </div>

                    <div class="contact-action-card">
                        <h4>Gửi phản hồi qua email</h4>
                        <p class="contact-help-text">Nội dung sẽ được gửi đến <?= adminContactEscape($selectedContact['Email']) ?> và lưu vào lịch sử.</p>
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                            <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                            <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                            <input type="hidden" name="return_page" value="<?= $page ?>">
                            <textarea name="NoiDungPhanHoi" class="form-control" rows="6" maxlength="5000" placeholder="Nhập nội dung phản hồi..." required></textarea>
                            <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Gửi phản hồi</button>
                        </form>
                    </div>

                    <div class="contact-action-card contact-danger-card">
                        <h4>Xóa yêu cầu</h4>
                        <p class="contact-help-text">Xóa liên hệ sẽ xóa cả lịch sử phản hồi liên quan. Thao tác này không thể hoàn tác.</p>
                        <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa yêu cầu liên hệ này và toàn bộ lịch sử phản hồi?');">
                            <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                            <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                            <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                            <input type="hidden" name="return_page" value="<?= $page ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">Xóa liên hệ</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="contact-history-card">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0">Lịch sử phản hồi</h4>
                    <span class="text-muted small"><?= count($replyHistory) ?> phản hồi</span>
                </div>
                <?php if ($replyHistory): ?>
                    <div class="contact-history-list">
                        <?php foreach ($replyHistory as $replyRecord): ?>
                            <?php [$mailLabel, $mailClass] = $emailStatusLabels[$replyRecord['TrangThaiEmail']] ?? [$replyRecord['TrangThaiEmail'], 'status-neutral']; ?>
                            <article class="contact-history-item">
                                <div class="contact-history-meta">
                                    <strong><?= adminContactEscape($replyRecord['TenNguoiPhanHoi']) ?></strong>
                                    <span><?= adminContactEscape(date('d/m/Y H:i', strtotime($replyRecord['NgayPhanHoi']))) ?></span>
                                    <span class="status-pill <?= adminContactEscape($mailClass) ?>"><?= adminContactEscape($mailLabel) ?></span>
                                </div>
                                <div class="contact-history-body"><?= nl2br(adminContactEscape($replyRecord['NoiDung'])) ?></div>
                                <?php if ($replyRecord['TrangThaiEmail'] !== 'DaGui'): ?>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="csrf_token" value="<?= adminContactEscape($csrfToken) ?>">
                                        <input type="hidden" name="action" value="resend_reply">
                                        <input type="hidden" name="MaLienHe" value="<?= (int)$selectedContact['MaLienHe'] ?>">
                                        <input type="hidden" name="MaPhanHoi" value="<?= (int)$replyRecord['MaPhanHoi'] ?>">
                                        <input type="hidden" name="return_q" value="<?= adminContactEscape($keyword) ?>">
                                        <input type="hidden" name="return_status" value="<?= adminContactEscape($filterStatus) ?>">
                                        <input type="hidden" name="return_page" value="<?= $page ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i>Gửi lại email này</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="contact-empty contact-empty-small"><i class="bi bi-chat-left-text"></i><strong>Chưa có phản hồi</strong><span>Phản hồi của Admin sẽ được lưu tại đây.</span></div>
                <?php endif; ?>
            </div>
        </section>
    <?php elseif ($selectedId > 0): ?>
        <div class="alert alert-warning mt-4">Không tìm thấy yêu cầu liên hệ này.</div>
    <?php endif; ?>
</div>
<?php clinicPortalShellEnd(); ?>
</body>
</html>
