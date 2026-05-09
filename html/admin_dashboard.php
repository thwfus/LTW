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
  $notice = "";

  // Cập nhật thông tin Website Info
  if (isset($_POST['update_profile'])) {
      $sql = "UPDATE Web_Info SET phone = ?, mail = ?, address = ? WHERE info_id = 1";
      $pdo->prepare($sql)->execute([$_POST['phone'], $_POST['mail'], $_POST['address']]);
      $notice = "Đã cập nhật thông tin Website thành công!";
  }

  // Xử lý trạng thái tin nhắn Liên hệ (Đọc/Xóa)
  if (isset($_GET['action']) && isset($_GET['msg_id'])) {
      $id = (int)$_GET['msg_id'];
      if ($_GET['action'] == 'mark_read') {
          $pdo->prepare("UPDATE Contact_Messages SET status = 'Đã đọc' WHERE message_id = ?")->execute([$id]);
      } elseif ($_GET['action'] == 'delete') {
          $pdo->prepare("DELETE FROM Contact_Messages WHERE message_id = ?")->execute([$id]);
      }
      header("Location: admin_dashboard.php#contacts");
      exit();
  }

  // 3. LẤY DỮ LIỆU HIỂN THỊ
  $company = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1")->fetch();
  $messages = $pdo->query("SELECT * FROM Contact_Messages ORDER BY created_at DESC")->fetchAll();
  $unreadCount = count(array_filter($messages, fn($m) => $m['status'] == 'Chưa đọc'));

  // Giả lập số liệu cho phần Stats (Sau này Huy thay bằng Query COUNT thực tế)
  $totalProducts = 128; 
  $totalOrders = 24;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Olivewood Atelier | Hệ thống Quản trị</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="../css/admin_dashboard.css">
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