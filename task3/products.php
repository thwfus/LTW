<?php include '../php/header.php'; ?>
<link rel="stylesheet" href="../task3/products.css" />

<main class="products-page-container">
    <header class="shop-header animate__animated animate__fadeIn">
        <h1 class="hero-title">Shop All Pieces</h1>
        <p class="section-content">Discover our complete range of handcrafted artisanal furniture.</p>
    </header>

    <div class="shop-layout">
        <aside class="filter-sidebar">
            <div class="filter-section">
                <h3 class="filter-title section-subtitle">Categories</h3>
                <ul class="filter-list">
                    <li><label class="section-content"><input type="checkbox" checked> All Pieces</label></li>
                    <li><label class="section-content"><input type="checkbox"> Chairs</label></li>
                    <li><label class="section-content"><input type="checkbox"> Tables</label></li>
                    <li><label class="section-content"><input type="checkbox"> Sofas</label></li>
                    <li><label class="section-content"><input type="checkbox"> Shelves</label></li>
                </ul>
            </div>

            <div class="filter-section">
                <h3 class="filter-title section-subtitle">Price Range</h3>
                <div class="price-range-inputs">
                    <input type="number" placeholder="Min $" class="price-input">
                    <span class="divider">—</span>
                    <input type="number" placeholder="Max $" class="price-input">
                </div>
                <button class="btn btn-outline btn-sm full-width mt-md">Apply Filter</button>
            </div>
        </aside>

        <section class="products-content">
            <div class="products-toolbar">
                <p class="section-content">Showing 12 products</p>
                <select class="sort-select">
                    <option>Sort by: Featured</option>
                    <option>Price: Low to High</option>
                    <option>Price: High to Low</option>
                    <option>Newest Arrivals</option>
                </select>
            </div>

            <div class="featured-products-grid shop-grid">
                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/5440404/pexels-photo-5440404.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Olivewood Dining Chair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Olivewood Dining Chair</h3>
                        <p class="product-card-price">$850.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/3773579/pexels-photo-3773579.png?auto=compress&cs=tinysrgb&w=800" alt="Minimalist Oak Table" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Minimalist Oak Table</h3>
                        <p class="product-card-price">$2,400.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/6707628/pexels-photo-6707628.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Velvet Lounge Armchair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Velvet Lounge Armchair</h3>
                        <p class="product-card-price">$1,250.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/6707628/pexels-photo-6707628.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Velvet Lounge Armchair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Velvet Lounge Armchair</h3>
                        <p class="product-card-price">$1,250.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/6707628/pexels-photo-6707628.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Velvet Lounge Armchair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Velvet Lounge Armchair</h3>
                        <p class="product-card-price">$1,250.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/6707628/pexels-photo-6707628.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Velvet Lounge Armchair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Velvet Lounge Armchair</h3>
                        <p class="product-card-price">$1,250.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

                <div class="product-card">
                    <div class="product-card-media">
                        <img src="https://images.pexels.com/photos/6707628/pexels-photo-6707628.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Velvet Lounge Armchair" />
                    </div>
                    <div class="product-card-info">
                        <h3 class="product-card-name">Velvet Lounge Armchair</h3>
                        <p class="product-card-price">$1,250.00</p>
                        <div class="product-card-actions">
                          <a href="product-detail.php" class="product-card-btn btn btn-outline btn-sm">View Detail</a>
                          <button class="product-card-btn btn btn-primary btn-sm btn-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></g></svg>
                            Add
                          </button>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>
</main>

<?php include '../php/footer.php'; ?>