<?php 
require_once '../task1/config_m1.php'; // Kết nối database[cite: 13]

// --- PHẦN 1: XỬ LÝ KHI NGƯỜI DÙNG NHẤN GỬI (INSERT) ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $name = $_POST['name'] ?? '';
    $mail = $_POST['mail'] ?? '';
    $question = $_POST['question'] ?? '';

    // Kiểm tra dữ liệu đầu vào (Server-side validation)
    if (!empty($name) && !empty($mail) && !empty($question)) {
        // Sử dụng Prepared Statement để chống SQL Injection
        $sql = "INSERT INTO Contact_Messages (name, mail, question) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$name, $mail, $question])) {
            $message = "<p style='color: #4CAF50;'>Cảm ơn bạn! Tin nhắn đã được gửi thành công.</p>";
        } else {
            $message = "<p style='color: #f44336;'>Có lỗi xảy ra, vui lòng thử lại sau.</p>";
        }
    } else {
        $message = "<p style='color: #ff9800;'>Vui lòng điền đầy đủ thông tin!</p>";
    }
}

// --- PHẦN 2: LẤY THÔNG TIN CÔNG TY ĐỂ HIỂN THỊ (SELECT) ---
$stmt = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1");
$company = $stmt->fetch(PDO::FETCH_ASSOC);

include '../php/header.php'; 
?>
<link rel="stylesheet" href="../task1/contact.css" />

<main class="contact-page-container">
    <header class="contact-header">
        <h1 class="hero-title">Connect with the Atelier</h1>
        <?php echo $message; // Hiển thị thông báo gửi thành công/thất bại ?>
    </header>

    <div class="contact-grid">
        <!-- HIỂN THỊ THÔNG TIN TỪ SQL -->
        <section class="contact-info">
            <div class="info-block">
                <h3 class="info-label">The Studio</h3>
                <!-- In địa chỉ từ bảng Web_Info[cite: 12] -->
                <p><?php echo $company['address'] ?? '123 Dong Khoi Street, District 1, HCM'; ?></p>
            </div>

            <div class="info-block">
                <h3 class="info-label">Inquiries</h3>
                <!-- In Mail và SĐT[cite: 12, 15] -->
                <p><?php echo htmlspecialchars($company['mail'] ?? 'atelier@olivewood.com'); ?></p>
                <p><?php echo htmlspecialchars($company['phone'] ?? '+84 90 123 4567'); ?></p>
            </div>

            <div class="info-block">
                <h3 class="info-label">Follow Our Journey</h3>
                <div class="contact-socials">
                    <a href="#" class="footer-link">Instagram</a>
                    <a href="#" class="footer-link">Pinterest</a>
                </div>
            </div>
        </section>

        <!-- FORM GỬI THÔNG TIN LÊN SQL -->
        <section class="contact-form-wrapper">
            <form action="contact.php" method="POST" class="contact-form">
                <div class="form-group">
                    <!-- Thuộc tính name phải khớp với biến $_POST trong PHP[cite: 1] -->
                    <input type="text" name="name" placeholder="Your Name" required class="form-input">
                </div>
                <div class="form-group">
                    <input type="email" name="mail" placeholder="Email Address" required class="form-input">
                </div>
                <div class="form-group">
                    <select class="form-input select-input">
                        <option>Subject: Custom Commission</option>
                        <option>Subject: Product Inquiry</option>
                    </select>
                </div>
                <div class="form-group">
                    <textarea name="question" placeholder="Your Message" rows="5" required class="form-input"></textarea>
                </div>
                <button type="submit" class="btn btn-primary full-width">Send Message</button>
            </form>
        </section>
    </div>
</main>

<?php include '../php/footer.php'; ?>