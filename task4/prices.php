<?php 
include '../php/header.php'; 

// Giả lập dữ liệu sản phẩm từ server
$product_prices = [
    ['name' => 'Olivewood Dining Chair', 'cat' => 'Chairs', 'price' => '$850.00', 'status' => 'In Stock'],
    ['name' => 'Minimalist Oak Table', 'cat' => 'Tables', 'price' => '$2,400.00', 'status' => 'In Stock'],
    ['name' => 'Velvet Lounge Armchair', 'cat' => 'Sofas', 'price' => '$1,250.00', 'status' => 'Out of Stock'],
    ['name' => 'Artisan Sideboard', 'cat' => 'Storage', 'price' => '$3,100.00', 'status' => 'In Stock'],
    ['name' => 'Marble Top Console', 'cat' => 'Tables', 'price' => '$1,850.00', 'status' => 'Limited'],
    ['name' => 'Curved Walnut Desk', 'cat' => 'Workplace', 'price' => '$2,200.00', 'status' => 'In Stock'],
];
?>
<link rel="stylesheet" href="../task4/prices.css" />

<main class="prices-page-container">
    <header class="prices-header animate__animated animate__fadeIn">
        <h1 class="hero-title">Price Guide</h1>
        <p class="section-content">Reference pricing for our handcrafted collection. Prices may vary based on custom finishes.</p>
    </header>

    <section class="price-list-section">
        <div class="price-table-wrapper">
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Reference Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($product_prices as $item): ?>
                    <tr>
                        <td class="product-name-cell"><?= $item['name'] ?></td>
                        <td><?= $item['cat'] ?></td>
                        <td class="price-cell"><?= $item['price'] ?></td>
                        <td>
                            <span class="status-badge <?= strtolower(str_replace(' ', '-', $item['status'])) ?>">
                                <?= $item['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="price-disclaimer">
            <p>* All prices are in USD and exclude shipping fees. Contact us for custom dimensions and bespoke material requests.</p>
        </div>
    </section>
</main>

<?php include '../php/footer.php'; ?>