<?php 
// 1. Kết nối cơ sở dữ liệu và lấy dữ liệu 6 sản phẩm tồn kho cao nhất
require_once '../task1/config_m1.php'; // Đảm bảo đường dẫn tới file kết nối đúng[cite: 4]

try {
    // Truy vấn lấy 6 sản phẩm có stock_quantity lớn nhất
    $stmt = $pdo->query("SELECT * FROM Product ORDER BY stock_quantity DESC LIMIT 6");
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featuredProducts = []; // Nếu lỗi thì mảng rỗng để không crash trang
}
include '../php/header.php'; ?>
<section class="hero-showcase">
          <div class="hero-showcase-background">
            <img
              src="https://images.pexels.com/photos/27164972/pexels-photo-27164972.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1500"
              alt="Luxury Minimalist Interior"
              class="hero-showcase-media"
            />
            <div class="hero-showcase-overlay"></div>
          </div>
          <div class="hero-showcase-content-wrapper">
            <div class="hero-showcase-text-block">
              <h1 class="hero-showcase-title hero-title">
                Sculpting Space with Timeless Elegance
              </h1>
              <p class="hero-showcase-subtitle hero-subtitle">
                Discover curated luxury furniture where artisanal woodcraft
                meets modern minimalist design. Handcrafted pieces for the
                discerning home.
              </p>
              <div class="hero-showcase-actions">
                <a href="../task3/products.php">
                  <div class="hero-showcase-cta btn btn-primary btn-lg">
                    <span>Shop Now</span>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="24"
                      height="24"
                      viewBox="0 0 24 24"
                    >
                      <path
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 12h14m-7-7l7 7l-7 7"
                      ></path>
                    </svg>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </section>
        <section id="featured-products" class="featured-products">
          <div class="featured-products-container">
            <div class="featured-products-header">
              <h2 class="section-title">Curated Collection</h2>
              <p class="section-content">
                Signature pieces defining the Olivewood Atelier aesthetic.
              </p>
            </div>
            
            <div class="featured-products-grid">
              <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                  <div class="product-card">
                    <div class="product-card-media">
                      <!-- Lấy đường dẫn ảnh từ cột url, nếu rỗng dùng ảnh mặc định[cite: 3] -->
                      <img
                        src="<?php echo htmlspecialchars($product['url'] ?: 'uploads/products/default.jpg'); ?>"
                        alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                      />
                    </div>
                    <div class="product-card-info">
                      <h3 class="product-card-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                      <!-- Định dạng giá tiền chuyên nghiệp[cite: 3] -->
                      <p class="product-card-price">$<?php echo number_format($product['price'], 2); ?></p>
                      <!-- Hiển thị số lượng tồn kho (Tùy chọn nếu bạn muốn khoe hàng nhiều) -->
                      <p style="font-size: 11px; color: #888;">Stock: <?php echo $product['stock_quantity']; ?></p>
                      
                      <a href="../task3/product-detail.php?id=<?php echo $product['product_id']; ?>" class="product-card-btn btn btn-outline btn-sm">
                        View Detail
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p>Hiện chưa có sản phẩm nào nổi bật.</p>
              <?php endif; ?>
            </div>
          </div>
        </section>
        <section class="room-showcase">
          <div id="roomCarousel" class="room-showcase-carousel">
            <div class="room-slide">
              <img
                src="https://images.pexels.com/photos/3356416/pexels-photo-3356416.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1500"
                alt="The Sanctuary Living Room"
                class="room-slide-img"
              />
              <div class="room-slide-content">
                <span class="room-slide-tag">Living Room</span>
                <h3 class="section-title">The Sanctuary Suite</h3>
                <p class="section-content">
                  Soft textures meet rigid wood forms for a balanced retreat.
                </p>
                <a href="../task3/products.php">
                  <div class="btn btn-link">
                    <span>Explore Collection</span>
                  </div>
                </a>
              </div>
            </div>
            <div class="room-slide">
              <img
                src="https://images.pexels.com/photos/7195591/pexels-photo-7195591.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1500"
                alt="The Atelier Dining Area"
                class="room-slide-img"
              />
              <div class="room-slide-content">
                <span class="room-slide-tag">Dining Room</span>
                <h3 class="section-title">The Atelier Dining</h3>
                <p class="section-content">
                  Where culinary art meets architectural precision.
                </p>
                <a href="#">
                  <div class="btn btn-link">
                    <span>Explore Collection</span>
                  </div>
                </a>
              </div>
            </div>
            <div class="room-slide">
              <img
                src="https://images.pexels.com/photos/20337842/pexels-photo-20337842.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1500"
                alt="The Minimalist Study"
                class="room-slide-img"
              />
              <div class="room-slide-content">
                <span class="room-slide-tag">Study &amp; Office</span>
                <h3 class="section-title">The Focus Study</h3>
                <p class="section-content">
                  Minimal distractions, maximum inspiration for your craft.
                </p>
                <a href="#">
                  <div class="btn btn-link">
                    <span>Explore Collection</span>
                  </div>
                </a>
              </div>
            </div>
          </div>
          <div class="room-carousel-controls">
            <button id="prevSlide" class="carousel-nav prev">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
              >
                <path
                  fill="none"
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 12H5m7-7l-7 7l7 7"
                ></path>
              </svg>
            </button>
            <button id="nextSlide" class="carousel-nav next">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
              >
                <path
                  fill="none"
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 12h14m-7-7l7 7l-7 7"
                ></path>
              </svg>
            </button>
          </div>
        </section>
        <section class="about-teaser">
          <div class="about-teaser-container">
            <div class="about-teaser-content">
              <h2 class="section-title">
                Rooted in Tradition, Designed for Tomorrow
              </h2>
              <p class="section-content">
                At Olivewood Atelier, we believe that luxury lies in the
                details—the grain of the wood, the precision of the joint, and
                the soul of the artisan. Our pieces are more than furniture;
                they are heirlooms crafted to anchor your space with warmth and
                quiet sophistication.
              </p>
              <p class="section-content">
                Every piece is sustainably sourced and meticulously finished
                with natural oils to highlight the raw beauty of the materials.
              </p>
              <div class="about-teaser-cta-wrap">
                <a href="../task2/about.php">
                  <div class="btn btn-secondary"><span>Our Heritage</span></div>
                </a>
              </div>
            </div>
            <div class="about-teaser-media">
              <img
                src="https://images.pexels.com/photos/433200/pexels-photo-433200.jpeg?auto=compress&amp;cs=tinysrgb&amp;w=1500"
                alt="Artisan craftsmanship at Olivewood Atelier"
                class="about-teaser-img"
              />
            </div>
          </div>
        </section>
        <section class="client-testimonials">
          <div class="testimonials-header">
            <h2 class="section-title">Voices of Refinement</h2>
          </div>
          <div class="testimonials-rail">
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path>
                </svg>
              </div>
              <p class="section-content">
                "The Walnut Dining Table is a masterpiece. It transformed our
                dining room into a gallery. The wood feels alive."
              </p>
              <div class="testimonial-author">
                <span class="author-name">Eleanor Vance</span>
                <span class="author-title">Interior Designer</span>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path>
                </svg>
              </div>
              <p class="section-content">
                "Minimalism done right. The quality of the joinery is
                exceptional. A truly premium experience from order to delivery."
              </p>
              <div class="testimonial-author">
                <span class="author-name">Julian Thorne</span>
                <span class="author-title">Architect</span>
              </div>
            </div>
            <div class="testimonial-card">
              <div class="testimonial-rating">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path></svg
                ><svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.12 2.12 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.12 2.12 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.12 2.12 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.12 2.12 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.12 2.12 0 0 0 1.597-1.16z"
                  ></path>
                </svg>
              </div>
              <p class="section-content">
                "Finally found a brand that respects both the environment and
                the aesthetic. The Olivewood chair is as comfortable as it is
                beautiful."
              </p>
              <div class="testimonial-author">
                <span class="author-name">Sienna Miller</span>
                <span class="author-title">Homeowner</span>
              </div>
            </div>
          </div>
        </section>
        <section class="newsletter-cta">
          <div class="newsletter-cta-container">
            <div class="newsletter-cta-content">
              <h2 class="section-title">Join the Atelier</h2>
              <p class="section-content">
                Receive exclusive previews of new collections, artisanal design
                tips, and invitations to our private studio launches.
              </p>
              <form
                action="#"
                method="POST"
                data-form-id="601f62fe-be16-4eec-963e-bfdb23cd394f"
                class="newsletter-form"
              >
                <div class="newsletter-input-group">
                  <input
                    type="email"
                    name="email"
                    placeholder="Your email address"
                    required="true"
                    id="thq_email_7lgA"
                    data-form-field-id="thq_email_7lgA"
                    class="newsletter-input"
                  />
                  <button
                    type="submit"
                    id="thq_button_ELDz"
                    name="button"
                    data-form-field-id="thq_button_ELDz"
                    class="newsletter-btn btn btn-accent"
                  >
                    Subscribe
                  </button>
                </div>
                <p class="newsletter-disclaimer">
                  By subscribing, you agree to our privacy policy and terms of
                  service.
                </p>
              </form>
            </div>
          </div>
        </section>
        <div class="home-container2">
          <div class="home-container3">
            <script defer="" data-name="olivewood-logic">
              document.addEventListener('DOMContentLoaded', function() {
                (function(){
                  const carousel = document.getElementById("roomCarousel")
                  const slides = document.querySelectorAll(".room-slide")
                  const prevBtn = document.getElementById("prevBtn") // Fixed ID from HTML
                  const nextBtn = document.getElementById("nextBtn") // Fixed ID from HTML

                  // Using IDs defined in HTML
                  const prevSlideBtn = document.getElementById("prevSlide")
                  const nextSlideBtn = document.getElementById("nextSlide")

                  let currentSlide = 0
                  const totalSlides = slides.length

                  function updateCarousel() {
                    carousel.style.transform = `translateX(-${currentSlide * 100}%)`
                  }

                  if (nextSlideBtn) {
                    nextSlideBtn.addEventListener("click", () => {
                      currentSlide = (currentSlide + 1) % totalSlides
                      updateCarousel()
                    })
                  }

                  if (prevSlideBtn) {
                    prevSlideBtn.addEventListener("click", () => {
                      currentSlide = (currentSlide - 1 + totalSlides) % totalSlides
                      updateCarousel()
                    })
                  }

                  // Auto-play functionality
                  let carouselInterval = setInterval(() => {
                    currentSlide = (currentSlide + 1) % totalSlides
                    updateCarousel()
                  }, 5000)

                  // Pause auto-play on interaction
                  const stopAutoPlay = () => clearInterval(carouselInterval)
                  if (carousel) {
                    carousel.addEventListener("mouseenter", stopAutoPlay)
                    carousel.addEventListener("touchstart", stopAutoPlay)
                  }

                  // Intersection Observer for scroll animations
                  const observerOptions = {
                    threshold: 0.1,
                    rootMargin: "0px 0px -50px 0px",
                  }

                  const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                      if (entry.isIntersecting) {
                        entry.target.style.opacity = "1"
                        entry.target.style.transform = "translateY(0)"
                        observer.unobserve(entry.target)
                      }
                    })
                  }, observerOptions)

                  document.querySelectorAll(".product-card, .testimonial-card, .about-teaser-content").forEach((el) => {
                    el.style.opacity = "0"
                    el.style.transform = "translateY(30px)"
                    el.style.transition = "all 0.6s ease-out"
                    observer.observe(el)
                  })

                  // Horizontal scroll snap behavior for testimonials
                  const rail = document.querySelector(".testimonials-rail")
                  if (rail) {
                    let isDown = false
                    let startX
                    let scrollLeft

                    rail.addEventListener("mousedown", (e) => {
                      isDown = true
                      rail.classList.add("active")
                      startX = e.pageX - rail.offsetLeft
                      scrollLeft = rail.scrollLeft
                    })

                    rail.addEventListener("mouseleave", () => {
                      isDown = false
                    })

                    rail.addEventListener("mouseup", () => {
                      isDown = false
                    })

                    rail.addEventListener("mousemove", (e) => {
                      if (!isDown) return
                      e.preventDefault()
                      const x = e.pageX - rail.offsetLeft
                      const walk = (x - startX) * 2
                      rail.scrollLeft = scrollLeft - walk
                    })
                  }
                })()
              });
            </script>
          </div>
        </div>
<?php include '../php/footer.php'; ?>