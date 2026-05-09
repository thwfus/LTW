<?php
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
 

  // 1. KIỂM TRA QUYỀN ADMIN (Dùng strtolower để tránh lỗi chữ hoa/thường)
  $isLoggedIn = isset($_SESSION['user_id']);
  $userRole = strtolower($_SESSION['role'] ?? 'guest');

  if (!$isLoggedIn || $userRole !== 'admin') {
      header("Location: ../html/login.php"); 
      exit();
  }

  // 2. NHÚNG CÁC FILE LOGIC
  require_once '../task1/config_m1.php'; 
  require_once '../task3/db.php'; 
  // require_once 'includes/qa_logic.php'; // Nhúng nếu file này đã sẵn sàng
  if (file_exists('includes/qa_logic.php')) {
    require_once 'includes/qa_logic.php';
}
  $qaMessage = "";

  // --- LOGIC XỬ LÝ DỮ LIỆU CỦA HUY (Giữ nguyên) ---
  // $notice = "";

  // // Cập nhật thông tin Website Info
  // if (isset($_POST['update_profile'])) {
  //     $sql = "UPDATE Web_Info SET phone = ?, mail = ?, address = ? WHERE info_id = 1";
  //     $pdo->prepare($sql)->execute([$_POST['phone'], $_POST['mail'], $_POST['address']]);
  //     $notice = "Đã cập nhật thông tin Website thành công!";
  // }

  // // Xử lý trạng thái tin nhắn Liên hệ (Đọc/Xóa)
  // if (isset($_GET['action']) && isset($_GET['msg_id'])) {
  //     $id = (int)$_GET['msg_id'];
  //     if ($_GET['action'] == 'mark_read') {
  //         $pdo->prepare("UPDATE Contact_Messages SET status = 'Đã đọc' WHERE message_id = ?")->execute([$id]);
  //     } elseif ($_GET['action'] == 'delete') {
  //         $pdo->prepare("DELETE FROM Contact_Messages WHERE message_id = ?")->execute([$id]);
  //     }
  //     header("Location: admin_dashboard.php#contacts");
  //     exit();
  // }

  

  // // 3. LẤY DỮ LIỆU HIỂN THỊ
  // $company = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1")->fetch();
  // $messages = $pdo->query("SELECT * FROM Contact_Messages ORDER BY created_at DESC")->fetchAll();
  // $unreadCount = count(array_filter($messages, fn($m) => $m['status'] == 'Chưa đọc'));



  // // Giả lập số liệu cho phần Stats (Sau này Huy thay bằng Query COUNT thực tế)
  // $totalProducts = 128; 
  // $totalOrders = 24;


    // --- LOGIC XỬ LÝ DỮ LIỆU CỦA HUY ---
  $notice = $_SESSION['admin_notice'] ?? "";
  unset($_SESSION['admin_notice']);

  // Helper chống lỗi XSS
  if (!function_exists('h')) {
      function h($value) {
          return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
      }
  }

  // Cập nhật thông tin Website Info
  if (isset($_POST['update_profile'])) {
      $sql = "UPDATE Web_Info SET phone = ?, mail = ?, address = ? WHERE info_id = 1";
      $pdo->prepare($sql)->execute([$_POST['phone'], $_POST['mail'], $_POST['address']]);

      $_SESSION['admin_notice'] = "Đã cập nhật thông tin Website thành công!";
      header("Location: admin_dashboard.php#pages-manager");
      exit();
  }

  // Xử lý trạng thái tin nhắn Liên hệ
  if (isset($_GET['action']) && isset($_GET['msg_id'])) {
      $id = (int)$_GET['msg_id'];

      if ($_GET['action'] == 'mark_read') {
          $pdo->prepare("UPDATE Contact_Messages SET status = 'Đã đọc' WHERE message_id = ?")->execute([$id]);
          $_SESSION['admin_notice'] = "Đã đánh dấu liên hệ là đã đọc.";
      } elseif ($_GET['action'] == 'delete') {
          $pdo->prepare("DELETE FROM Contact_Messages WHERE message_id = ?")->execute([$id]);
          $_SESSION['admin_notice'] = "Đã xóa liên hệ.";
      }

      header("Location: admin_dashboard.php#contacts");
      exit();
  }

  // ===============================
  // XỬ LÝ DUYỆT / GỠ DUYỆT / XÓA BÀI REVIEW
  // ===============================
  // ===============================
// XỬ LÝ THÊM / SỬA / DUYỆT / GỠ DUYỆT / XÓA BÀI REVIEW
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    $action = $_POST['review_action'];
    $adminId = (int)$_SESSION['user_id'];

    // Kiểm tra adminId có tồn tại trong bảng admin không để tránh lỗi khóa ngoại
    $checkAdmin = $pdo->prepare("
        SELECT user_id 
        FROM admin 
        WHERE user_id = ? 
        LIMIT 1
    ");
    $checkAdmin->execute([$adminId]);
    $validAdminId = $checkAdmin->fetchColumn() ? $adminId : null;

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $reviewType = trim($_POST['review_type'] ?? 'website');
    $adminNote = trim($_POST['admin_note'] ?? '');

    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, ['pending', 'approved'])) {
        $status = 'pending';
    }

    $productIdRaw = trim($_POST['product_id'] ?? '');
    $productId = ($productIdRaw === '') ? null : (int)$productIdRaw;

    // Admin thêm bài review mới
    if ($action === 'add') {
        if ($title === '' || $content === '') {
            $_SESSION['admin_notice'] = "Vui lòng nhập tiêu đề và nội dung bài review.";
            header("Location: admin_dashboard.php#reviews");
            exit();
        }

        $stmt = $pdo->prepare("
    INSERT INTO review_post
        (title, content, image, status, review_type, admin_note, created_at, user_id, product_id, admin_id)
    VALUES
        (?, ?, ?, ?, ?, ?, NOW(), NULL, ?, ?)
");

$stmt->execute([
    $title,
    $content,
    $image,
    $status,
    $reviewType,
    $adminNote,
    $productId,
    $validAdminId
]);

        $_SESSION['admin_notice'] = "Đã thêm bài review mới.";
        header("Location: admin_dashboard.php#reviews");
        exit();
    }

    // Admin sửa bài review
    if ($action === 'edit' && isset($_POST['review_id'])) {
        $reviewId = (int)$_POST['review_id'];

        if ($title === '' || $content === '') {
            $_SESSION['admin_notice'] = "Vui lòng nhập tiêu đề và nội dung bài review.";
            header("Location: admin_dashboard.php#reviews");
            exit();
        }

        $stmt = $pdo->prepare("
            UPDATE review_post
            SET title = ?,
                content = ?,
                image = ?,
                status = ?,
                review_type = ?,
                admin_note = ?,
                product_id = ?,
                admin_id = ?
            WHERE review_id = ?
        ");

        $stmt->execute([
            $title,
            $content,
            $image,
            $status,
            $reviewType,
            $adminNote,
            $productId,
            $validAdminId,
            $reviewId
        ]);

        $_SESSION['admin_notice'] = "Đã cập nhật bài review.";
        header("Location: admin_dashboard.php#reviews");
        exit();
    }

    // Duyệt bài
    if ($action === 'approve' && isset($_POST['review_id'])) {
        $reviewId = (int)$_POST['review_id'];

        $stmt = $pdo->prepare("
            UPDATE review_post
            SET status = 'approved',
                admin_note = ?,
                admin_id = ?
            WHERE review_id = ?
        ");

        $stmt->execute([
            $adminNote !== '' ? $adminNote : 'Approved by admin.',
            $validAdminId,
            $reviewId
        ]);

        $_SESSION['admin_notice'] = "Đã duyệt bài review.";
        header("Location: admin_dashboard.php#reviews");
        exit();
    }

    // Gỡ duyệt bài
    if ($action === 'pending' && isset($_POST['review_id'])) {
        $reviewId = (int)$_POST['review_id'];

        $stmt = $pdo->prepare("
            UPDATE review_post
            SET status = 'pending',
                admin_note = ?,
                admin_id = ?
            WHERE review_id = ?
        ");

        $stmt->execute([
            $adminNote !== '' ? $adminNote : 'Moved back to pending.',
            $validAdminId,
            $reviewId
        ]);

        $_SESSION['admin_notice'] = "Đã gỡ duyệt bài review.";
        header("Location: admin_dashboard.php#reviews");
        exit();
    }

    // Xóa bài
    if ($action === 'delete' && isset($_POST['review_id'])) {
        $reviewId = (int)$_POST['review_id'];

        $pdo->prepare("DELETE FROM review_cmt WHERE review_id = ?")->execute([$reviewId]);
        $pdo->prepare("DELETE FROM review_post WHERE review_id = ?")->execute([$reviewId]);

        $_SESSION['admin_notice'] = "Đã xóa bài review.";
        header("Location: admin_dashboard.php#reviews");
        exit();
    }
}

  // ===============================
  // XỬ LÝ DUYỆT / ẨN / XÓA BÌNH LUẬN REVIEW
  // ===============================
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'], $_POST['review_cmt_id'])) {
      $commentId = (int)$_POST['review_cmt_id'];
      $action = $_POST['comment_action'];
      $adminId = (int)$_SESSION['user_id'];
      $adminNote = trim($_POST['admin_note'] ?? '');

      if ($action === 'approve') {
          $stmt = $pdo->prepare("
              UPDATE review_cmt
              SET status = 'approved',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_cmt_id = ?
          ");
          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Approved by admin.',
              $adminId,
              $commentId
          ]);

          $_SESSION['admin_notice'] = "Đã duyệt bình luận.";
      }

      if ($action === 'pending') {
          $stmt = $pdo->prepare("
              UPDATE review_cmt
              SET status = 'pending',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_cmt_id = ?
          ");
          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Hidden by admin.',
              $adminId,
              $commentId
          ]);

          $_SESSION['admin_notice'] = "Đã ẩn bình luận.";
      }

      if ($action === 'delete') {
          $pdo->prepare("DELETE FROM review_cmt WHERE review_cmt_id = ?")->execute([$commentId]);

          $_SESSION['admin_notice'] = "Đã xóa bình luận.";
      }

      header("Location: admin_dashboard.php#reviews");
      exit();
  }

  // 3. LẤY DỮ LIỆU HIỂN THỊ
  $company = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1")->fetch();
  $messages = $pdo->query("SELECT * FROM Contact_Messages ORDER BY created_at DESC")->fetchAll();
  $unreadCount = count(array_filter($messages, fn($m) => $m['status'] == 'Chưa đọc'));

  // Giả lập số liệu cho phần Stats
  $totalProducts = 128; 
  $totalOrders = 24;

  // Lấy danh sách bài review
  // Lấy danh sách bài review, có tìm kiếm
$reviewKeyword = trim($_GET['review_keyword'] ?? '');
$reviewStatus = trim($_GET['review_status'] ?? '');

$whereReview = [];
$paramsReview = [];

if ($reviewKeyword !== '') {
    $whereReview[] = "
        (
            rp.title LIKE ?
            OR rp.content LIKE ?
            OR rp.review_type LIKE ?
            OR rp.admin_note LIKE ?
        )
    ";

    $likeKeyword = '%' . $reviewKeyword . '%';
    $paramsReview[] = $likeKeyword;
    $paramsReview[] = $likeKeyword;
    $paramsReview[] = $likeKeyword;
    $paramsReview[] = $likeKeyword;
}

if (in_array($reviewStatus, ['pending', 'approved'])) {
    $whereReview[] = "rp.status = ?";
    $paramsReview[] = $reviewStatus;
}

$whereReviewSql = "";
if (!empty($whereReview)) {
    $whereReviewSql = "WHERE " . implode(" AND ", $whereReview);
}

$stmtReviews = $pdo->prepare("
    SELECT 
        rp.*,
        u.user_name,
        COALESCE(cmt.total_comments, 0) AS total_comments
    FROM review_post rp
    LEFT JOIN `User` u ON u.user_id = rp.user_id
    LEFT JOIN (
        SELECT review_id, COUNT(*) AS total_comments
        FROM review_cmt
        GROUP BY review_id
    ) cmt ON cmt.review_id = rp.review_id
    $whereReviewSql
    ORDER BY 
        FIELD(rp.status, 'pending', 'approved'),
        rp.created_at DESC
");

$stmtReviews->execute($paramsReview);
$reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

  // Lấy danh sách bình luận review
  $reviewComments = $pdo->query("
      SELECT 
          rc.*,
          rp.title AS review_title,
          u.user_name
      FROM review_cmt rc
      LEFT JOIN review_post rp ON rp.review_id = rc.review_id
      LEFT JOIN `User` u ON u.user_id = rc.user_id
      ORDER BY 
          FIELD(rc.status, 'pending', 'approved'),
          rc.created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $pendingReviewCount = count(array_filter($reviews, fn($r) => $r['status'] === 'pending'));
  $pendingCommentCount = count(array_filter($reviewComments, fn($c) => $c['status'] === 'pending'));
?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Olivewood Atelier | Hệ thống Quản trị</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="../css/admin_dashboard.css?v=review_fix_1">
</head>
<body class="admin-body">

  <div class="admin-wrapper">
    
    <aside class="admin-sidebar">
      <div class="sidebar-brand">
        <a href="../task1/index.php" class="brand-logo">OLIVEWOOD<span>.</span></a>
        <p class="brand-sub">Management System</p>
      </div>

      <nav class="sidebar-menu">
        <div class="menu-group">Tổng quan</div>
        <a href="#overview" class="menu-link active" data-tab="overview">
          <span class="menu-icon">📊</span> Tổng quan hệ thống
        </a>
        <a href="#pages-manager" class="menu-link" data-tab="pages-manager">
          <span class="menu-icon">⚙️</span> Cấu hình Website
        </a>

        <div class="menu-group">Kinh doanh</div>
        <a href="#products" class="menu-link" data-tab="products">
          <span class="menu-icon">🪑</span> Quản lý Sản phẩm
        </a>
        <a href="#orders" class="menu-link" data-tab="orders">
          <span class="menu-icon">📦</span> Đơn hàng & Giỏ hàng
        </a>

        <div class="menu-group">Tương tác</div>
        <a href="#contacts" class="menu-link" data-tab="contacts">
          <span class="menu-icon">✉️</span> Liên hệ khách hàng
          <?php if($unreadCount > 0): ?>
            <span class="menu-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
        <a href="#qa" class="menu-link" data-tab="qa">
          <span class="menu-icon">❓</span> Hỏi đáp (Q&A)
        </a>
        <a href="#reviews" class="menu-link" data-tab="reviews">
          <span class="menu-icon">📝</span> Bài review
          <?php if(($pendingReviewCount + $pendingCommentCount) > 0): ?>
            <span class="menu-badge"><?= $pendingReviewCount + $pendingCommentCount ?></span>
          <?php endif; ?>
        </a>
        <a href="#users" class="menu-link" data-tab="users">
          <span class="menu-icon">👥</span> Thành viên
        </a>
        
      </nav>

      <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">Đăng xuất</a>
      </div>
    </aside>

    <main class="admin-main">
      
      <header class="admin-topbar">
        <div class="topbar-left">
            <span class="breadcrumb">Admin / <strong id="active-tab-name">Tổng quan</strong></span>
        </div>
        <div class="topbar-right">
          <div class="admin-info">
            <p class="admin-name">Chào Huy, <strong>Admin</strong></p>
            <img src="https://ui-avatars.com/api/?name=Admin&background=1e4e36&color=fff" alt="Avatar" class="admin-avatar">
          </div>
        </div>
      </header>

      <div class="admin-container">
        
        <?php if ($notice): ?>
            <div class="admin-alert animate__animated animate__fadeInDown">
                <?= h($notice) ?>
            </div>
        <?php endif; ?>

        <section id="overview" class="admin-section active">
          <div class="section-header-wrap">
            <h1 class="section-title">Bảng tin hệ thống</h1>
            <p class="section-desc">Dữ liệu tổng hợp từ các hoạt động của Atelier.</p>
          </div>

          <div class="stat-grid">
            <div class="stat-item">
              <span class="stat-label">Sản phẩm</span>
              <div class="stat-main">
                <span class="stat-number"><?= $totalProducts ?></span>
                <span class="stat-tag">Active</span>
              </div>
            </div>
            <div class="stat-item">
              <span class="stat-label">Đơn hàng mới</span>
              <div class="stat-main">
                <span class="stat-number"><?= $totalOrders ?></span>
                <span class="stat-tag positive">+5%</span>
              </div>
            </div>
            <div class="stat-item">
              <span class="stat-label">Liên hệ chờ</span>
              <div class="stat-main">
                <span class="stat-number"><?= $unreadCount ?></span>
                <span class="stat-tag <?= $unreadCount > 0 ? 'warning' : '' ?>">
                    <?= $unreadCount > 0 ? 'Cần xử lý' : 'Đã xong' ?>
                </span>
              </div>
            </div>
          </div>
        </section>

        <section id="pages-manager" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
              <h2>Cấu hình thông tin Website</h2>
              <p>Các thông tin này hiển thị tại Footer và trang Liên hệ.</p>
            </div>
            <form method="POST" action="#pages-manager" class="admin-form">
              <div class="form-grid">
                <div class="form-group">
                  <label>Số điện thoại hotline</label>
                  <input type="text" name="phone" value="<?= h($company['phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                  <label>Email liên hệ</label>
                  <input type="email" name="mail" value="<?= h($company['mail'] ?? ''); ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label>Địa chỉ văn phòng / Showroom</label>
                <textarea name="address" rows="3" required><?= h($company['address'] ?? ''); ?></textarea>
              </div>
              <div class="form-footer">
                <button type="submit" name="update_profile" class="btn-submit">Lưu cấu hình</button>
              </div>
            </form>
          </div>
        </section>

        <section id="contacts" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
                <h2>Danh sách khách hàng liên hệ</h2>
            </div>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Khách hàng</th>
                    <th>Nội dung tin nhắn</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                      <td>
                        <strong><?= h($msg['name']); ?></strong><br>
                        <small><?= h($msg['mail']); ?></small>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 300px;"><?= h($msg['question']); ?></div>
                      </td>
                      <td><?= date('d/m/Y', strtotime($msg['created_at'])); ?></td>
                      <td>
                        <span class="status-badge <?= ($msg['status'] == 'Chưa đọc') ? 'status-unread' : 'status-read' ?>">
                          <?= $msg['status']; ?>
                        </span>
                      </td>
                      <td>
                        <div class="action-btns">
                          <?php if ($msg['status'] == 'Chưa đọc'): ?>
                            <a href="?action=mark_read&msg_id=<?= $msg['message_id']; ?>#contacts" class="btn-table">Đọc</a>
                          <?php endif; ?>
                          <a href="?action=delete&msg_id=<?= $msg['message_id']; ?>#contacts" 
                             class="btn-table btn-danger" 
                             onclick="return confirm('Xác nhận xóa liên hệ này?')">Xóa</a>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="5" class="empty-row">Hiện chưa có liên hệ nào.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="qa" class="admin-section">
            <div class="admin-card">
                <?php include 'includes/qa_view.php'; ?>
            </div>
        </section>

        <section id="reviews" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
              <h2>Quản lý bài review</h2>
              <p>Admin duyệt bài review trước khi bài được hiển thị ngoài trang người dùng.</p>
            </div>
            <div class="review-admin-toolbar">
  <form method="GET" action="admin_dashboard.php#reviews" class="review-search-form">
    <div class="review-search-left">
      <label>Tìm kiếm bài review</label>
      <input 
        type="text" 
        name="review_keyword" 
        value="<?= h($reviewKeyword ?? ''); ?>" 
        placeholder="Nhập tiêu đề, nội dung, loại review..."
      >
    </div>

    <div class="review-search-status">
      <label>Trạng thái</label>
      <select name="review_status">
        <option value="">Tất cả</option>
        <option value="pending" <?= ($reviewStatus ?? '') === 'pending' ? 'selected' : ''; ?>>
          Chờ duyệt
        </option>
        <option value="approved" <?= ($reviewStatus ?? '') === 'approved' ? 'selected' : ''; ?>>
          Đã duyệt
        </option>
      </select>
    </div>

    <div class="review-search-actions">
      <button type="submit" class="review-btn review-btn-primary">
        Tìm kiếm
      </button>

      <a href="admin_dashboard.php#reviews" class="review-btn review-btn-light">
        Làm mới
      </a>
    </div>
  </form>
</div>

<details class="review-create-panel">
  <summary>
    <span class="review-plus-icon">+</span>
    Thêm bài review mới
  </summary>

  <div class="review-create-body">
    <form method="POST" action="admin_dashboard.php#reviews">
      <input type="hidden" name="review_action" value="add">

      <div class="review-create-grid">
        <div class="review-form-group review-form-wide">
          <label>Tiêu đề bài review</label>
          <input 
            type="text" 
            name="title" 
            placeholder="Ví dụ: Trải nghiệm mua bàn gỗ rất tốt" 
            required
          >
        </div>

        <div class="review-form-group">
          <label>Loại review</label>
          <select name="review_type">
            <option value="website">Website</option>
            <option value="product">Sản phẩm</option>
            <option value="service">Dịch vụ</option>
            <option value="delivery">Giao hàng</option>
          </select>
        </div>

        <div class="review-form-group">
          <label>ID sản phẩm nếu có</label>
          <input 
            type="number" 
            name="product_id" 
            placeholder="Có thể bỏ trống"
          >
        </div>

        <div class="review-form-group">
          <label>Trạng thái</label>
          <select name="status">
            <option value="approved">Đăng ngay</option>
            <option value="pending">Lưu chờ duyệt</option>
          </select>
        </div>

        <div class="review-form-group review-form-full">
          <label>Ảnh bài review</label>
          <input 
            type="text" 
            name="image" 
            placeholder="Ví dụ: uploads/reviews/my-review.jpg"
          >
        </div>

        <div class="review-form-group review-form-full">
          <label>Nội dung bài review</label>
          <textarea 
            name="content" 
            rows="6" 
            placeholder="Nhập nội dung bài review..."
            required
          ></textarea>
        </div>

        <div class="review-form-group review-form-full">
          <label>Ghi chú admin</label>
          <textarea 
            name="admin_note" 
            rows="2" 
            placeholder="Ghi chú nội bộ nếu có..."
          ></textarea>
        </div>
      </div>

      <div class="review-create-actions">
        <button type="submit" class="review-btn review-btn-primary">
          Thêm bài review
        </button>
      </div>
    </form>
  </div>
</details>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Bài review</th>
                    <th>Người viết</th>
                    <th>Loại</th>
                    <th>Sản phẩm</th>
                    <th>Bình luận</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Quản lý</th>
                  </tr>
                </thead>

                <tbody>
  <?php if (!empty($reviews)): ?>
    <?php foreach ($reviews as $review): ?>
      <tr>
        <td>
          <div class="review-main-title">
            <?= h($review['title']); ?>
          </div>

          <div class="review-short-content">
            <?= h(mb_substr($review['content'], 0, 120)); ?>
            <?= mb_strlen($review['content']) > 120 ? '...' : ''; ?>
          </div>
        </td>

        <td>
          <?= h($review['user_name'] ?? 'Admin đăng'); ?>
        </td>

        <td>
          <?= h($review['review_type']); ?>
        </td>

        <td>
          <?= !empty($review['product_id']) ? '#' . (int)$review['product_id'] : 'Không có'; ?>
        </td>

        <td>
          <?= (int)$review['total_comments']; ?>
        </td>

        <td>
          <?= date('d/m/Y H:i', strtotime($review['created_at'])); ?>
        </td>

        <td>
          <?php if ($review['status'] === 'approved'): ?>
            <span class="status-badge status-read">Đã duyệt</span>
          <?php else: ?>
            <span class="status-badge status-unread">Chờ duyệt</span>
          <?php endif; ?>
        </td>

        <td class="review-actions-cell">
          <form method="POST" action="admin_dashboard.php#reviews">
            <input type="hidden" name="review_id" value="<?= (int)$review['review_id']; ?>">
            <input type="hidden" name="admin_note" value="<?= h($review['admin_note'] ?? ''); ?>">

            <div class="review-inline-actions">
              <?php if ($review['status'] !== 'approved'): ?>
                <button type="submit" name="review_action" value="approve" class="review-btn review-btn-primary">
                  Duyệt
                </button>
              <?php else: ?>
                <button type="submit" name="review_action" value="pending" class="review-btn review-btn-light">
                  Gỡ duyệt
                </button>
              <?php endif; ?>

              <button 
                type="submit" 
                name="review_action" 
                value="delete" 
                class="review-btn review-btn-danger"
                onclick="return confirm('Xóa bài review này?')"
              >
                Xóa
              </button>
            </div>
          </form>
        </td>
      </tr>

      <tr class="review-detail-row">
        <td colspan="8">
          <details class="review-detail-panel">
  <summary>Đọc chi tiết / Sửa bài</summary>

  <div class="review-detail-body">

    <div class="review-full-content-box">
      <div class="review-full-content-title">
        Nội dung đầy đủ
      </div>

      <div class="review-full-content-text">
        <?= nl2br(h($review['content'])); ?>
      </div>

      <?php if (!empty($review['admin_note'])): ?>
        <div class="review-admin-note">
          <strong>Ghi chú admin:</strong>
          <?= h($review['admin_note']); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="review-edit-card">
      <div class="review-edit-title">
        Sửa thông tin bài review
      </div>

      <form method="POST" action="admin_dashboard.php#reviews">
        <input type="hidden" name="review_action" value="edit">
        <input type="hidden" name="review_id" value="<?= (int)$review['review_id']; ?>">

        <div class="review-edit-grid">
          <div class="review-edit-group">
            <label>Tiêu đề</label>
            <input 
              type="text" 
              name="title" 
              value="<?= h($review['title']); ?>" 
              required
            >
          </div>

          <div class="review-edit-group">
            <label>Loại review</label>
            <select name="review_type">
              <option value="website" <?= $review['review_type'] === 'website' ? 'selected' : ''; ?>>
                Website
              </option>
              <option value="product" <?= $review['review_type'] === 'product' ? 'selected' : ''; ?>>
                Sản phẩm
              </option>
              <option value="service" <?= $review['review_type'] === 'service' ? 'selected' : ''; ?>>
                Dịch vụ
              </option>
              <option value="delivery" <?= $review['review_type'] === 'delivery' ? 'selected' : ''; ?>>
                Giao hàng
              </option>
            </select>
          </div>

          <div class="review-edit-group">
            <label>ID sản phẩm</label>
            <input 
              type="number" 
              name="product_id" 
              value="<?= h($review['product_id'] ?? ''); ?>"
              placeholder="Bỏ trống"
            >
          </div>

          <div class="review-edit-group">
            <label>Trạng thái</label>
            <select name="status">
              <option value="pending" <?= $review['status'] === 'pending' ? 'selected' : ''; ?>>
                Chờ duyệt
              </option>
              <option value="approved" <?= $review['status'] === 'approved' ? 'selected' : ''; ?>>
                Đã duyệt
              </option>
            </select>
          </div>

          <div class="review-edit-group review-edit-full">
            <label>Ảnh</label>
            <input 
              type="text" 
              name="image" 
              value="<?= h($review['image'] ?? ''); ?>"
              placeholder="Ví dụ: uploads/reviews/review-1.jpg"
            >
          </div>

          <div class="review-edit-group review-edit-full">
            <label>Nội dung</label>
            <textarea name="content" rows="6" required><?= h($review['content']); ?></textarea>
          </div>

          <div class="review-edit-group review-edit-full">
            <label>Ghi chú admin</label>
            <textarea name="admin_note" rows="2"><?= h($review['admin_note'] ?? ''); ?></textarea>
          </div>
        </div>

        <div class="review-save-row">
          <button type="submit" class="review-btn review-btn-primary">
            Lưu sửa bài review
          </button>
        </div>
      </form>
    </div>

  </div>
</details>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr>
      <td colspan="8" class="empty-row">
        Chưa có bài review nào.
      </td>
    </tr>
  <?php endif; ?>
</tbody>
              </table>
            </div>
          </div>


          <div class="admin-card" style="margin-top: 24px;">
            <div class="card-header">
              <h2>Kiểm soát bình luận review</h2>
              <p>Admin có thể duyệt, ẩn hoặc xóa bình luận trong các bài review.</p>
            </div>

            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Bài review</th>
                    <th>Người bình luận</th>
                    <th>Nội dung</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Quản lý</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (!empty($reviewComments)): ?>
                    <?php foreach ($reviewComments as $comment): ?>
                      <tr>
                        <td style="min-width: 220px;">
                          <strong><?= h($comment['review_title'] ?? 'Bài review đã bị xóa'); ?></strong>
                        </td>

                        <td>
                          <?= h($comment['user_name'] ?? ('User #' . $comment['user_id'])); ?>
                        </td>

                        <td style="min-width: 300px;">
                          <details>
                            <summary style="cursor:pointer; color:#1e4e36; font-weight:600;">
                              Đọc bình luận
                            </summary>

                            <p style="margin-top:8px; line-height:1.6;">
                              <?= nl2br(h($comment['content'])); ?>
                            </p>

                            <?php if (!empty($comment['admin_note'])): ?>
                              <p>
                                <strong>Ghi chú admin:</strong>
                                <?= h($comment['admin_note']); ?>
                              </p>
                            <?php endif; ?>
                          </details>
                        </td>

                        <td>
                          <?= date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                        </td>

                        <td>
                          <?php if ($comment['status'] === 'approved'): ?>
                            <span class="status-badge status-read">Hiển thị</span>
                          <?php else: ?>
                            <span class="status-badge status-unread">Chờ duyệt / Đang ẩn</span>
                          <?php endif; ?>
                        </td>

                        <td style="min-width: 230px;">
                          <form method="POST" action="admin_dashboard.php#reviews">
                            <input type="hidden" name="review_cmt_id" value="<?= (int)$comment['review_cmt_id']; ?>">

                            <textarea 
                              name="admin_note" 
                              rows="2" 
                              placeholder="Ghi chú admin..."
                              style="width:100%; margin-bottom:8px;"
                            ><?= h($comment['admin_note'] ?? ''); ?></textarea>

                            <div class="action-btns">
                              <?php if ($comment['status'] !== 'approved'): ?>
                                <button type="submit" name="comment_action" value="approve" class="btn-table">
                                  Duyệt
                                </button>
                              <?php else: ?>
                                <button type="submit" name="comment_action" value="pending" class="btn-table">
                                  Ẩn
                                </button>
                              <?php endif; ?>

                              <button 
                                type="submit" 
                                name="comment_action" 
                                value="delete" 
                                class="btn-table btn-danger"
                                onclick="return confirm('Xóa bình luận này?')"
                              >
                                Xóa
                              </button>
                            </div>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="empty-row">
                        Chưa có bình luận review nào.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="products" class="admin-section placeholder-view">
            <div class="placeholder-content">
                <h2>Quản lý sản phẩm</h2>
                <p>Chức năng đang được chuẩn bị để tích hợp cơ sở dữ liệu Task 3.</p>
            </div>
        </section>

        <section id="orders" class="admin-section placeholder-view">
            <div class="placeholder-content">
                <h2>Quản lý Đơn hàng & Giỏ hàng</h2>
                <p>Chức năng theo dõi tiến độ đơn hàng và thanh toán đang được phát triển.</p>
            </div>
        </section>

        

        <section id="users" class="admin-section placeholder-view">
            <div class="placeholder-content">
                <h2>Quản lý thành viên</h2>
                <p>Phân quyền và quản lý tài khoản khách hàng.</p>
            </div>
        </section>

      </div>
    </main>
  </div>

  <script>
    // Logic Tab Switching (Chuyển trang mượt mà không cần load lại trang)
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('.menu-link');
        const sections = document.querySelectorAll('.admin-section');
        const tabName = document.getElementById('active-tab-name');

        function showSection(id) {
            sections.forEach(s => s.classList.remove('active'));
            const target = document.querySelector(id);
            if(target) target.classList.add('active');
            
            // Cập nhật Breadcrumb
            const activeLink = document.querySelector(`.menu-link[href="${id}"]`);
            if(activeLink) tabName.innerText = activeLink.innerText.replace(/[^\w\sàáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ]/gi, '').trim();
        }

        links.forEach(link => {
            link.addEventListener('click', function(e) {
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                showSection(this.getAttribute('href'));
            });
        });

        // Xử lý khi load trang có sẵn Hash (ví dụ: admin_dashboard.php#contacts)
        const currentHash = window.location.hash || '#overview';
        const initialLink = document.querySelector(`.menu-link[href="${currentHash}"]`);
        if(initialLink) {
            links.forEach(l => l.classList.remove('active'));
            initialLink.classList.add('active');
            showSection(currentHash);
        }
    });
  </script>
</body>
</html>