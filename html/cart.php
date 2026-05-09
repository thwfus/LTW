<?php include '../php/header.php'; ?>
<link rel="stylesheet" href="../css/cart.css" />
      <main class="cart-main-container">
          <div class="cart-header-wrapper">
            <h1 class="cart-title">Your Cart</h1>
            <p class="section-content">Review your selection before checkout.</p>
          </div>

          <div class="cart-grid-layout">
            <div class="cart-items-list">
                <div class="cart-product-item">
                    <img src="https://images.pexels.com/photos/5440404/pexels-photo-5440404.jpeg?auto=compress&cs=tinysrgb&w=300" class="cart-product-img" />
                    <div class="cart-product-info">
                        <h3 class="product-name">Olivewood Dining Chair</h3>
                        <p class="product-price">$850.00</p>
                        <div class="product-controls">
                            <div class="qty-control">
                                <button class="qty-btn">-</button>
                                <span class="qty-num">1</span>
                                <button class="qty-btn">+</button>
                            </div>
                            <button class="remove-link">Remove</button>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="cart-summary-card">
                <h2 class="summary-title">Order Summary</h2>
                <div class="summary-details">
                    <div class="summary-row"><span>Subtotal</span><span>$850.00</span></div>
                    <div class="summary-row"><span>Shipping</span><span>Calculated at checkout</span></div>
                    <div class="summary-total"><span>Total</span><span>$850.00</span></div>
                </div>
                <button class="btn btn-primary btn-lg checkout-btn">Proceed to Checkout</button>
            </aside>
          </div>
      </main>
<?php include '../php/footer.php'; ?>