<?php include '../php/header.php'; ?>
<link rel="stylesheet" href="../css/user.css" />

<main class="user-page-container">
    <div class="user-profile-wrapper animate__animated animate__fadeIn">
        <header class="user-header">
            <h1 class="section-title">My Account</h1>
            <p class="section-content">Manage your artisanal collection and personal details.</p>
        </header>

        <section class="profile-grid">
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <img src="https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=300" alt="User Avatar" class="profile-avatar">
                    <button class="edit-avatar-btn" title="Change Image">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                    </button>
                </div>
                <h2 class="user-display-name">Huy Nguyen</h2>
                <p class="user-role section-content">Atelier Member since 2026</p>
            </div>

            <div class="profile-content">
                <div class="info-group">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value-row">
                            <span class="info-text">Huy Nguyen</span>
                            <button class="edit-icon-btn" onclick="editField('name')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value-row">
                            <span class="info-text">huy.nguyen@example.com</span>
                            <button class="edit-icon-btn" onclick="editField('email')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value-row">
                            <span class="info-text">+84 90 123 4567</span>
                            <button class="edit-icon-btn" onclick="editField('phone')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Shipping Address</div>
                        <div class="info-value-row">
                            <span class="info-text">123 Dong Khoi, District 1, Ho Chi Minh City, Vietnam</span>
                            <button class="edit-icon-btn" onclick="editField('address')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn btn-outline btn-sm">Change Password</button>
                    <button class="btn btn-primary btn-sm">Sign Out</button>
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
                    <p class="section-content"><strong>Recipient:</strong> Huy Nguyen</p>
                    <p class="section-content"><strong>Phone:</strong> +84 90 123 4567</p>
                    <p class="section-content"><strong>Address:</strong> 123 Dong Khoi, District 1, Ho Chi Minh City</p>
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
                    <p class="section-content"><strong>Phone:</strong> +84 90 123 4567</p>
                    <p class="section-content"><strong>Address:</strong> 123 Dong Khoi, District 1, Ho Chi Minh City</p>
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
    function editField(field) {
        console.log("Edit requested for: " + field);
    }
</script>

<script src="../task1/user.js"></script>
<?php include '../php/footer.php'; ?>