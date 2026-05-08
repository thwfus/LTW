<?php
require_once __DIR__ . '/db.php';

$customerId = current_customer_id($conn);
$productId = (int)($_GET['id'] ?? 0);
$notice = '';
$noticeType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    $productId = (int)($_POST['product_id'] ?? $productId);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    $ok = add_to_cart($conn, $customerId, $productId, $quantity, $notice);
    $noticeType = $ok ? 'success' : 'error';
}

$stmt = $conn->prepare('
    SELECT
        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.material,
        p.color,
        p.warranty_period,
        p.url,
        c.category_id,
        c.category_name
    FROM product p
    JOIN category c ON p.category_id = c.category_id
    WHERE p.product_id = ?
    LIMIT 1
');
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

$relatedProducts = [];

if ($product) {
    $stmt = $conn->prepare('
        SELECT product_id, product_name, price, url
        FROM product
        WHERE category_id = ? AND product_id <> ?
        ORDER BY product_id ASC
        LIMIT 3
    ');
    $stmt->bind_param('ii', $product['category_id'], $productId);
    $stmt->execute();
    $relatedProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$cart = get_cart_items($conn, $customerId);

include_site_header($product ? $product['product_name'] : 'Product Detail');
?>

<link rel="stylesheet" href="../task3/product.css" />

<main class="product-detail-container">
    <?php if ($notice !== ''): ?>
        <div class="task-alert task-alert-<?= h($noticeType) ?>">
            <?= h($notice) ?>
        </div>
    <?php endif; ?>

    <?php if (!$product): ?>
        <section class="empty-state product-not-found">
            <h1>Không tìm thấy sản phẩm</h1>
            <p>Sản phẩm bạn đang xem không tồn tại hoặc đã bị xóa.</p>
            <a class="product-card-btn" href="../task3/products.php">
                Quay lại danh sách sản phẩm
            </a>
        </section>
    <?php else: ?>
        <section class="product-essentials">
            <div class="product-gallery gallery-spacing">
                <div class="main-image-wrapper">
                    <img
                        id="mainProductImage"
                        src="<?= h(asset_path($product['url'])) ?>"
                        alt="<?= h($product['product_name']) ?>"
                    />
                </div>

                <div class="thumbnail-grid">
                    <img
                        class="thumb active"
                        src="<?= h(asset_path($product['url'])) ?>"
                        alt="Thumbnail"
                        onclick="setMainImage(this)"
                    />
                    <img
                        class="thumb"
                        src="<?= h(asset_path($product['url'])) ?>"
                        alt="Thumbnail"
                        onclick="setMainImage(this)"
                    />
                    <img
                        class="thumb"
                        src="<?= h(asset_path($product['url'])) ?>"
                        alt="Thumbnail"
                        onclick="setMainImage(this)"
                    />
                </div>
            </div>

            <div class="product-info-panel">
                <p class="breadcrumb">
                    Collections / <?= h($product['category_name']) ?> / #<?= (int)$product['product_id'] ?>
                </p>

                <h1 class="product-title"><?= h($product['product_name']) ?></h1>
                <p class="product-price"><?= money_vnd($product['price']) ?></p>

                <div class="detail-status-row">
                    <span class="stock-pill <?= (int)$product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                        <?= (int)$product['stock_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                    </span>

                    <span>Tồn kho: <?= (int)$product['stock_quantity'] ?></span>
                </div>

                <div class="quick-spec-list">
                    <p><strong>Danh mục:</strong> <?= h($product['category_name']) ?></p>
                    <p><strong>Vật liệu:</strong> <?= h($product['material'] ?: 'Chưa cập nhật') ?></p>
                    <p><strong>Màu sắc:</strong> <?= h($product['color'] ?: 'Chưa cập nhật') ?></p>
                    <p><strong>Bảo hành:</strong> <?= h($product['warranty_period'] ?: 'Chưa cập nhật') ?></p>
                </div>

                <form method="post" class="detail-cart-form">
                    <input type="hidden" name="action" value="add_to_cart" />
                    <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>" />

                    <label class="qty-label">Quantity</label>

                    <div class="qty-selector">
                        <button type="button" onclick="changeQty(-1)">−</button>

                        <input
                            id="quantityInput"
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= max(1, (int)$product['stock_quantity']) ?>"
                        />

                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>

                    <button
                        class="product-card-btn full-width"
                        type="submit"
                        <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                    >
                        Add to Collection
                    </button>

                    <a class="product-card-btn secondary full-width" href="../task3/cart.php">
                        Xem giỏ hàng
                    </a>
                </form>

                <div class="service-notes">
                    <p>✓ Handcrafted from sustainable materials</p>
                    <p>✓ Bảo hành theo thông tin từng sản phẩm</p>
                    <p>✓ Hỗ trợ kiểm tra giỏ hàng trước khi đặt</p>
                </div>
            </div>
        </section>

        <section class="product-specs-section">
            <div class="specs-tabs">
                <button class="tab-link active" type="button" data-tab="story">
                    Concept & Story
                </button>

                <button class="tab-link" type="button" data-tab="material">
                    Dimensions & Material
                </button>

                <button class="tab-link" type="button" data-tab="cart">
                    Cart Preview
                </button>
            </div>

            <div id="story" class="tab-pane active">
                <div class="editorial-grid">
                    <div>
                        <h2 class="editorial-heading">Pure Form</h2>

                        <p class="editorial-body">
                            <?= h($product['product_name']) ?> được thiết kế theo tinh thần tối giản,
                            tập trung vào chất liệu, màu sắc và khả năng phối hợp trong không gian sống hiện đại.
                        </p>
                    </div>

                    <blockquote class="editorial-quote">
                        “Design is not just what it looks like; it's how the material breathes in your space.”
                    </blockquote>
                </div>
            </div>

            <div id="material" class="tab-pane">
                <div class="specs-table">
                    <div class="specs-row">
                        <span class="label">Product ID</span>
                        <span class="value">#<?= (int)$product['product_id'] ?></span>
                    </div>

                    <div class="specs-row">
                        <span class="label">Category</span>
                        <span class="value"><?= h($product['category_name']) ?></span>
                    </div>

                    <div class="specs-row">
                        <span class="label">Primary Material</span>
                        <span class="value"><?= h($product['material'] ?: 'Chưa cập nhật') ?></span>
                    </div>

                    <div class="specs-row">
                        <span class="label">Color</span>
                        <span class="value"><?= h($product['color'] ?: 'Chưa cập nhật') ?></span>
                    </div>

                    <div class="specs-row">
                        <span class="label">Warranty</span>
                        <span class="value"><?= h($product['warranty_period'] ?: 'Chưa cập nhật') ?></span>
                    </div>
                </div>
            </div>

            <div id="cart" class="tab-pane">
                <div class="cart-preview-box">
                    <h2>Giỏ hàng của bạn</h2>

                    <?php if (empty($cart['items'])): ?>
                        <p>Giỏ hàng đang trống.</p>
                    <?php else: ?>
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="cart-preview-row">
                                <span>
                                    <?= h($item['product_name']) ?> × <?= (int)$item['quantity'] ?>
                                </span>

                                <strong><?= money_vnd($item['line_total']) ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <div class="cart-preview-total">
                            <span>Tổng tạm tính</span>
                            <strong><?= money_vnd($cart['total']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <a class="product-card-btn" href="../task3/cart.php">
                        Đi đến trang giỏ hàng
                    </a>
                </div>
            </div>
        </section>

        <?php if (!empty($relatedProducts)): ?>
            <section class="related-products-section">
                <h2>Sản phẩm cùng danh mục</h2>

                <div class="related-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <a
                            class="related-card"
                            href="../task3/product-detail.php?id=<?= (int)$related['product_id'] ?>"
                        >
                            <img
                                src="<?= h(asset_path($related['url'])) ?>"
                                alt="<?= h($related['product_name']) ?>"
                            />

                            <span><?= h($related['product_name']) ?></span>
                            <strong><?= money_vnd($related['price']) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
function setMainImage(img) {
    const main = document.getElementById('mainProductImage');

    if (!main) {
        return;
    }

    main.src = img.src;

    document.querySelectorAll('.thumb').forEach(item => {
        item.classList.remove('active');
    });

    img.classList.add('active');
}

function changeQty(delta) {
    const input = document.getElementById('quantityInput');

    if (!input) {
        return;
    }

    const min = Number(input.min || 1);
    const max = Number(input.max || 999);
    const next = Math.min(max, Math.max(min, Number(input.value || 1) + delta));

    input.value = next;
}

document.querySelectorAll('.tab-link').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-link').forEach(item => {
            item.classList.remove('active');
        });

        document.querySelectorAll('.tab-pane').forEach(item => {
            item.classList.remove('active');
        });

        button.classList.add('active');

        const tab = document.getElementById(button.dataset.tab);

        if (tab) {
            tab.classList.add('active');
        }
    });
});
</script>

<?php
include_site_footer();
?>