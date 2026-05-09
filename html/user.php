<?php
session_start();

// KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    // Nếu chưa đăng nhập -> Chuyển hướng về trang login
    header("Location: ../html/login.php");
    exit();
}

require_once '../task1/config_m1.php';
require_once 'includes/user_logic.php';
?>
<?php include '../php/header.php'; ?>
<link rel="stylesheet" href="../css/user.css" />

<main class="user-page-container">
    <div class="user-profile-wrapper animate__animated animate__fadeIn">

        <?php if ($userMessage !== ''): ?>
        <div class="user-alert <?= $userMessageType === 'error' ? 'user-alert-error' : 'user-alert-success' ?>">
            <?= h($userMessage) ?>
        </div>
        <?php endif; ?>

        <header class="user-header">
            <h1 class="section-title">My Account</h1>
            <p class="section-content">Manage your artisanal collection and personal details.</p>
        </header>

        <section class="profile-grid">
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <img src="<?= h($userProfile['avatar_url']) ?>" alt="User Avatar" class="profile-avatar">
                    <button class="edit-avatar-btn" title="Change Image">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                    </button>
                </div>
                <h2 class="user-display-name"><?= h($userProfile['name']) ?></h2>
                <p class="user-role section-content">Atelier Member since <?= h((string)$userProfile['member_since']) ?></p>
            </div>

            <div class="profile-content">
                <div class="info-group">

                    <!-- Full Name -->
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value-row" id="view-name">
                            <span class="info-text"><?= h($userProfile['name']) ?></span>
                            <button class="edit-icon-btn" type="button" onclick="toggleEdit('name')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                        <form class="inline-edit-form" id="edit-form-name" method="POST">
                            <input type="hidden" name="field" value="name">
                            <input class="inline-edit-input" type="text" name="value" value="<?= h($userProfile['name']) ?>" placeholder="Nhập họ tên" required>
                            <div class="inline-edit-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" onclick="toggleEdit('name')" class="btn btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Email Address -->
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value-row" id="view-email">
                            <span class="info-text"><?= h($userProfile['email']) ?></span>
                            <button class="edit-icon-btn" type="button" onclick="toggleEdit('email')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                        <form class="inline-edit-form" id="edit-form-email" method="POST">
                            <input type="hidden" name="field" value="email">
                            <input class="inline-edit-input" type="email" name="value" value="<?= h($userProfile['email']) ?>" placeholder="Nhập email" required>
                            <div class="inline-edit-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" onclick="toggleEdit('email')" class="btn btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Phone Number -->
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value-row" id="view-phone">
                            <span class="info-text"><?= h($userProfile['phone']) ?></span>
                            <button class="edit-icon-btn" type="button" onclick="toggleEdit('phone')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                        <form class="inline-edit-form" id="edit-form-phone" method="POST">
                            <input type="hidden" name="field" value="phone">
                            <input class="inline-edit-input" type="text" name="value" value="<?= h($userProfile['phone']) ?>" placeholder="Nhập số điện thoại" required>
                            <div class="inline-edit-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" onclick="toggleEdit('phone')" class="btn btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Shipping Address -->
                    <div class="info-item">
                        <div class="info-label">Shipping Address</div>
                        <div class="info-value-row" id="view-address">
                            <span class="info-text"><?= h($userProfile['address']) ?></span>
                            <button class="edit-icon-btn" type="button" onclick="toggleEdit('address')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                        <form class="inline-edit-form" id="edit-form-address" method="POST">
                            <input type="hidden" name="field" value="address">
                            <textarea class="inline-edit-textarea" name="value" placeholder="Nhập địa chỉ giao hàng" required><?= h($userProfile['address']) ?></textarea>
                            <div class="inline-edit-actions">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <button type="button" onclick="toggleEdit('address')" class="btn btn-outline btn-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>

                <div class="profile-actions">
                    <button class="btn btn-outline btn-sm">Change Password</button>
                    <a href="logout.php" class="btn btn-primary btn-sm" style="text-decoration: none;">Sign Out</a>
                </div>
            </div>

            <section class="order-history-section animate__animated animate__fadeInUp">
                <h2 class="section-subtitle">Purchase History</h2>

                <div class="order-list">
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #OA-2026-001</span>
                            <span class="order-status delivered">Delivered</span>
                        </div>
                        <div class="order-body">
                            <div class="order-products">
                                <div class="product-item-summary">
                                    <span class="product-name">Olivewood Dining Chair</span>
                                    <span class="product-qty">x 2</span>
                                </div>
                                <div class="product-item-summary">
                                    <span class="product-name">Minimalist Oak Table</span>
                                    <span class="product-qty">x 1</span>
                                </div>
                            </div>
                            <div class="order-shipping-info">
                                <p class="info-label">Shipping Details</p>
                                <p class="section-content"><strong>Recipient:</strong> <?= h($userProfile['name']) ?></p>
                                <p class="section-content"><strong>Phone:</strong> <?= h($userProfile['phone']) ?></p>
                                <p class="section-content"><strong>Address:</strong> <?= h($userProfile['address']) ?></p>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div class="order-total">
                                <span class="total-label">Total Amount</span>
                                <span class="total-price">$4,100.00</span>
                            </div>
                            <button class="btn btn-outline btn-sm">View Invoice</button>
                        </div>
                    </div>

                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #OA-2026-042</span>
                            <span class="order-status processing">Processing</span>
                        </div>
                        <div class="order-body">
                            <div class="order-products">
                                <div class="product-item-summary">
                                    <span class="product-name">Velvet Lounge Armchair</span>
                                    <span class="product-qty">x 1</span>
                                </div>
                            </div>
                            <div class="order-shipping-info">
                                <p class="info-label">Shipping Details</p>
                                <p class="section-content"><strong>Phone:</strong> <?= h($userProfile['phone']) ?></p>
                                <p class="section-content"><strong>Address:</strong> <?= h($userProfile['address']) ?></p>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div class="order-total">
                                <span class="total-label">Total Amount</span>
                                <span class="total-price">$1,250.00</span>
                            </div>
                            <button class="btn btn-outline btn-sm">Track Order</button>
                        </div>
                    </div>
                </div>
            </section>

        </section>
    </div>
</main>

<script>
    function toggleEdit(field) {
        const form    = document.getElementById('edit-form-' + field);
        const viewRow = document.getElementById('view-' + field);
        if (!form || !viewRow) return;

        const isOpen = form.style.display === 'block';

        // Đóng tất cả, hiện lại tất cả info-value-row
        document.querySelectorAll('.inline-edit-form').forEach(f => f.style.display = 'none');
        document.querySelectorAll('.info-value-row').forEach(r => r.style.display = 'flex');

        if (!isOpen) {
            viewRow.style.display = 'none';
            form.style.display = 'block';
            form.querySelector('input[name="value"], textarea[name="value"]')?.focus();
        }
    }
</script>

<script src="../task1/user.js"></script>
<?php include '../php/footer.php'; ?>
