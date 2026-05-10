<?php 
ob_start();

include '../php/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "btl_ltw");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/*
    User hiện tại.
    Nếu hệ thống login của bạn đã có session user_id thì nó sẽ lấy session.
    Nếu chưa có login, tạm dùng user_id = 11 để test.
*/
$currentUserId = $_SESSION['user_id'] ?? 11;

$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentPost = null;
$comments = [];
$commentErrors = [];
$commentSuccess = "";

/*
    Nếu submit comment
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comment_content"])) {
    $commentContent = trim($_POST["comment_content"] ?? "");
    $postedReviewId = (int)($_POST["review_id"] ?? 0);

    if ($postedReviewId <= 0) {
        $commentErrors[] = "Invalid review.";
    }

    if ($commentContent === "") {
        $commentErrors[] = "Comment content is required.";
    }

    if (empty($commentErrors)) {
        $insertCommentSql = "
            INSERT INTO review_cmt
            (
                content,
                status,
                admin_note,
                created_at,
                review_id,
                user_id,
                admin_id
            )
            VALUES
            (
                ?,
                'approved',
                NULL,
                NOW(),
                ?,
                ?,
                NULL
            )
        ";

        $stmt = $conn->prepare($insertCommentSql);

        if (!$stmt) {
            $commentErrors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param(
                "sii",
                $commentContent,
                $postedReviewId,
                $currentUserId
            );

            if ($stmt->execute()) {
                /*
                    Redirect lại để tránh submit lặp khi refresh.
                */
                header("Location: ../task4/news_detail.php?id=" . $postedReviewId . "&comment=success");
                exit;
            } else {
                $commentErrors[] = "Could not save comment: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

if (isset($_GET["comment"]) && $_GET["comment"] === "success") {
    $commentSuccess = "Your comment has been posted successfully.";
}

/*
    Lấy bài review hiện tại
*/
if ($reviewId > 0) {
    $postSql = "
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
            u.user_name AS author_name,
            p.product_name
        FROM review_post rp
        JOIN user u ON rp.user_id = u.user_id
        LEFT JOIN product p ON rp.product_id = p.product_id
        WHERE rp.review_id = ?
          AND rp.status IN ('approved', 'pending')
        LIMIT 1
    ";

    $stmt = $conn->prepare($postSql);

    if ($stmt) {
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentPost = $result->fetch_assoc();
        $stmt->close();
    }
}

/*
    Lấy comment của bài review.
    Vì comment không cần admin duyệt nên chỉ cần lấy approved.
*/
if ($currentPost) {
    $commentSql = "
        SELECT
            rc.review_cmt_id,
            rc.content,
            rc.status,
            rc.admin_note,
            rc.created_at,
            rc.review_id,
            rc.user_id,
            u.user_name AS author_name,
            p.avatar_url
        FROM review_cmt rc
        JOIN user u ON rc.user_id = u.user_id
        LEFT JOIN profile p ON rc.user_id = p.user_id
        WHERE rc.review_id = ?
          AND rc.status = 'approved'
        ORDER BY rc.created_at ASC, rc.review_cmt_id ASC
    ";

    $stmt = $conn->prepare($commentSql);

    if ($stmt) {
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }

        $stmt->close();
    }
}

/*
    Hàm xử lý đường dẫn ảnh.
    DB lưu: uploads/reviews/abc.jpg
    Trang này ở task4/news_detail.php nên cần ../uploads/reviews/abc.jpg
*/
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
?>

<link rel="stylesheet" href="../task4/news_detail.css?v=3" />

<main class="news-page-container">
    <?php if ($currentPost): ?>
        <article class="article-detail">
            <header class="article-detail-header">
                <span class="post-category">
                    <?php echo htmlspecialchars(formatReviewType($currentPost['review_type'])); ?>
                </span>

                <h1 class="hero-title">
                    <?php echo htmlspecialchars($currentPost['title']); ?>
                </h1>

                <div class="article-detail-meta">
                    <span>
                        By <?php echo htmlspecialchars($currentPost['author_name']); ?>
                    </span>

                    <span>
                        <?php echo date("F d, Y", strtotime($currentPost['created_at'])); ?>
                    </span>

                    <?php if (!empty($currentPost['product_name'])): ?>
                        <span>
                            Product: <?php echo htmlspecialchars($currentPost['product_name']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </header>

            <div class="article-detail-image">
                <img 
                    src="<?php echo htmlspecialchars(getReviewImageSrc($currentPost['image'])); ?>" 
                    alt="<?php echo htmlspecialchars($currentPost['title']); ?>"
                    onerror="this.onerror=null; this.src='../uploads/reviews/default.jpg';"
                >
            </div>

            <div class="article-detail-content">
                <?php
                    $paragraphs = preg_split("/\r\n|\n|\r/", trim($currentPost['content']));
                ?>

                <?php foreach ($paragraphs as $paragraph): ?>
                    <?php if (trim($paragraph) !== ""): ?>
                        <p>
                            <?php echo nl2br(htmlspecialchars($paragraph)); ?>
                        </p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <section class="review-comments-section">
                <div class="comments-header">
                    <h2 class="section-subtitle">Comments</h2>
                    <p class="section-content">
                        Share your thoughts about this review.
                    </p>
                </div>

                <?php if (!empty($commentSuccess)): ?>
                    <div class="review-comment-notice success">
                        <p><?php echo htmlspecialchars($commentSuccess); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($commentErrors)): ?>
                    <div class="review-comment-notice error">
                        <?php foreach ($commentErrors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="review-comment-form">
                    <input 
                        type="hidden" 
                        name="review_id" 
                        value="<?php echo (int)$currentPost['review_id']; ?>"
                    >

                    <div class="review-comment-field">
                        <label for="comment_content">Write a Comment</label>
                        <textarea 
                            id="comment_content" 
                            name="comment_content" 
                            rows="5" 
                            placeholder="Write your comment here..."
                            required
                        ></textarea>
                    </div>

                    <div class="review-comment-actions">
                        <button type="submit" class="submit-comment-btn">
                            Post Comment
                        </button>
                    </div>
                </form>

                <div class="comments-list">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <article class="review-comment-card">
                                <div class="review-comment-avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                    <?php 
                                        // Xác định đường dẫn ảnh: nếu có avatar_url thì dùng, không thì dùng ảnh mặc định
                                        // Lưu ý: Đường dẫn có thể cần điều chỉnh là '../uploads/avatar/default.jpg' tùy vào cấu trúc thư mục của bạn
                                        $avatarSrc = !empty($comment['avatar_url']) ? $comment['avatar_url'] : '../uploads/avatar/default.jpg';
                                    ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($avatarSrc); ?>" 
                                        alt="Avatar" 
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        onerror="this.onerror=null; this.src='../uploads/avatar/default.jpg';"
                                    >
                                </div>

                                <div class="review-comment-body">
                                    <div class="review-comment-meta">
                                        <strong>
                                            <?php echo htmlspecialchars($comment['author_name']); ?>
                                        </strong>
                                        <span>
                                            <?php echo date("F d, Y H:i", strtotime($comment['created_at'])); ?>
                                        </span>
                                    </div>

                                    <p>
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-comments">
                            No comments yet. Be the first to comment.
                        </p>
                    <?php endif; ?>
                </div>
            </section>

            <div class="article-detail-actions">
                <a href="../task4/news_all.php" class="btn btn-link">
                    Back to All Articles
                </a>
            </div>
        </article>
    <?php else: ?>
        <section class="article-not-found">
            <h1 class="hero-title">Article Not Found</h1>
            <p class="section-content">
                The article you are looking for does not exist or may have been removed.
            </p>
            <a href="../task4/news_all.php" class="view-all-posts-btn">
                Back to All Articles
            </a>
        </section>
    <?php endif; ?>
</main>

<?php 
include '../php/footer.php'; 
ob_end_flush();
?>