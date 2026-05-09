<?php 
include '../php/header.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "btl_ltw");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$posts = [];

$sql = "
    SELECT 
        rp.review_id,
        rp.title,
        rp.content,
        rp.image,
        rp.status,
        rp.review_type,
        rp.admin_note,
        rp.created_at,
        rp.user_id,
        rp.product_id,
        rp.admin_id,
        COALESCE(cu.user_name, au.user_name, 'Admin') AS author_name,
        p.product_name
    FROM review_post rp
    LEFT JOIN `user` cu ON rp.user_id = cu.user_id
    LEFT JOIN `user` au ON rp.admin_id = au.user_id
    LEFT JOIN product p ON rp.product_id = p.product_id
    WHERE rp.status = 'approved'
    ORDER BY rp.created_at DESC, rp.review_id DESC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

function getReviewImageSrc($imagePath) {
    if (empty($imagePath)) {
        return "../uploads/reviews/default.jpg";
    }

    if (str_starts_with($imagePath, "http://") || str_starts_with($imagePath, "https://")) {
        return $imagePath;
    }

    if (str_starts_with($imagePath, "../")) {
        return $imagePath;
    }

    return "../" . $imagePath;
}

function formatReviewType($type) {
    $labels = [
        "product" => "Product Review",
        "website" => "Website Review",
        "service" => "Service Review",
        "customer_experience" => "Customer Experience",
        "delivery" => "Delivery Review",
        "buying_guide" => "Buying Guide",
        "general" => "General Review"
    ];

    return $labels[$type] ?? ucfirst(str_replace("_", " ", $type));
}

function makeExcerpt($content, $length = 160) {
    $content = trim(strip_tags($content));

    if (mb_strlen($content, "UTF-8") <= $length) {
        return $content;
    }

    return mb_substr($content, 0, $length, "UTF-8") . "...";
}
?>

<link rel="stylesheet" href="../task4/news_all.css" />

<main class="news-page-container">
    <header class="news-header animate__animated animate__fadeIn">
        <h1 class="hero-title">All Articles</h1>
        <p class="section-content">
            Browse all product reviews, website feedback, and customer stories from newest to oldest.
        </p>

        <div class="news-header-actions">
            <a href="../task4/news_create.php" class="create-article-btn">
                + Create New Article
            </a>
        </div>
    </header>

    <section class="all-posts-section active">
        <div class="all-posts-list">
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <article class="all-post-item">
                        <a 
                            href="../task4/news_detail.php?id=<?php echo (int)$post['review_id']; ?>" 
                            class="all-post-image"
                        >
                            <img 
                                src="<?php echo htmlspecialchars(getReviewImageSrc($post['image'])); ?>" 
                                alt="<?php echo htmlspecialchars($post['title']); ?>"
                                onerror="this.onerror=null; this.src='../uploads/reviews/default.jpg';"
                            >
                        </a>

                        <div class="all-post-content">
                            <div class="post-meta-row">
                                <span class="post-category">
                                    <?php echo htmlspecialchars(formatReviewType($post['review_type'])); ?>
                                </span>

                                <span class="news-date">
                                    <?php echo date("F d, Y", strtotime($post['created_at'])); ?>
                                </span>
                            </div>

                            <h2 class="all-post-title">
                                <a href="../task4/news_detail.php?id=<?php echo (int)$post['review_id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>

                            <p class="section-content">
                                <?php echo htmlspecialchars(makeExcerpt($post['content'])); ?>
                            </p>

                            <div class="post-footer">
                                <span class="post-author">
                                    By <?php echo htmlspecialchars($post['author_name']); ?>
                                </span>

                                <?php if (!empty($post['product_name'])): ?>
                                    <span class="post-product">
                                        Product: <?php echo htmlspecialchars($post['product_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a 
                                href="../task4/news_detail.php?id=<?php echo (int)$post['review_id']; ?>" 
                                class="btn btn-link"
                            >
                                View Details
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="section-content">
                    No approved articles are available yet.
                </p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../php/footer.php'; ?>