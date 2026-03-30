<?php 
include '../php/header.php'; 
$isAdmin = false; // Giả lập Admin đang đăng nhập để hiện các nút sửa
?>
<link rel="stylesheet" href="../task1/contact.css" />

<main class="contact-page-container">
    <?php if ($isAdmin): ?>
        <div class="admin-controls">
            <button id="saveContactChanges" class="btn btn-primary btn-sm">Save Contact Info</button>
            <span id="contactStatus"></span>
        </div>
    <?php endif; ?>

    <header class="contact-header animate__animated animate__fadeIn">
        <h1 class="hero-title <?= $isAdmin ? 'editable' : '' ?>" 
            contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
            data-id="contact_title">Connect with the Atelier</h1>
    </header>

    <div class="contact-grid">
        <section class="contact-info">
            <div class="info-block">
                <h3 class="info-label">The Studio</h3>
                <p class="<?= $isAdmin ? 'editable' : '' ?>" 
                   contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
                   data-id="contact_address">123 Dong Khoi Street, District 1<br>Ho Chi Minh City, Vietnam</p>
            </div>

            <div class="info-block">
                <h3 class="info-label">Inquiries</h3>
                <p class="<?= $isAdmin ? 'editable' : '' ?>" 
                   contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
                   data-id="contact_email">atelier@olivewood.com</p>
                <p class="<?= $isAdmin ? 'editable' : '' ?>" 
                   contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
                   data-id="contact_phone">+84 90 123 4567</p>
            </div>

            <div class="info-block">
                <h3 class="info-label">Follow Our Journey</h3>
                <div class="contact-socials">
                    <a href="#" class="footer-link">Instagram</a>
                    <a href="#" class="footer-link">Pinterest</a>
                </div>
            </div>
        </section>

        <section class="contact-form-wrapper">
            <form action="#" class="contact-form">
                <div class="form-group">
                    <input type="text" placeholder="Your Name" required class="form-input">
                </div>
                <div class="form-group">
                    <input type="email" placeholder="Email Address" required class="form-input">
                </div>
                <div class="form-group">
                    <select class="form-input select-input">
                        <option>Subject: Custom Commission</option>
                        <option>Subject: Product Inquiry</option>
                        <option>Subject: Trade Program</option>
                    </select>
                </div>
                <div class="form-group">
                    <textarea placeholder="Your Message" rows="5" class="form-input"></textarea>
                </div>
                <button type="submit" class="btn btn-primary full-width">Send Message</button>
            </form>
        </section>
    </div>

    <section class="contact-map-area">
        <div class="map-placeholder">
            <img id="contactMapImg" src="https://images.pexels.com/photos/20337842/pexels-photo-20337842.jpeg?auto=compress&cs=tinysrgb&w=1500" alt="Atelier Location">
            <?php if ($isAdmin): ?>
                <input type="file" id="uploadMap" hidden accept="image/*">
                <button class="edit-img-btn" onclick="document.getElementById('uploadMap').click()">Update Location Image</button>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="../task2/contact-admin.js"></script>
<?php include '../php/footer.php'; ?>