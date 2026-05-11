<?php
include '../php/header.php';
require_once '../task3/db.php';

$cntRow = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM about_content"));
if ((int)($cntRow[0] ?? 0) === 0) {
    mysqli_query($conn, "INSERT INTO about_content (content) VALUES ('')");
}

$contentRow  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT content FROM about_content WHERE id = 1 LIMIT 1"));
$rawContent  = $contentRow['content'] ?? '';

// Pre-load tất cả ảnh được tham chiếu trong content
$imgCache = [];
if (preg_match_all('/\/image\((\d+)\)/', $rawContent, $imgMatches) && !empty($imgMatches[1])) {
    $ids       = implode(',', array_map('intval', $imgMatches[1]));
    $imgResult = mysqli_query($conn, "SELECT image_id, path, alt_text FROM about_images WHERE image_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($imgResult)) {
        $imgCache[(int)$row['image_id']] = $row;
    }
}

function renderAboutContent($raw, $imgCache) {
    if (trim($raw) === '') return '';

    $lines      = explode("\n", $raw);
    $html       = '';
    $paraLines  = [];

    $flushPara = function () use (&$paraLines, &$html) {
        $text = trim(implode("\n", $paraLines));
        if ($text !== '') {
            $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $html .= '<p class="about-content-text">' . nl2br($safe) . '</p>';
        }
        $paraLines = [];
    };

    foreach ($lines as $line) {
        $trimmed = rtrim($line);
        if (preg_match('/^\/image\((\d+)\)\s*$/', $trimmed, $m)) {
            $flushPara();
            $imgId = (int)$m[1];
            if (isset($imgCache[$imgId])) {
                $img  = $imgCache[$imgId];
                $src  = htmlspecialchars('../' . $img['path'], ENT_QUOTES);
                $alt  = htmlspecialchars($img['alt_text'], ENT_QUOTES);
                $html .= '<figure class="about-content-image"><img src="' . $src . '" alt="' . $alt . '"></figure>';
            }
        } elseif (preg_match('/^header\((.+)\)\s*$/', $trimmed, $m)) {
            $flushPara();
            $html .= '<h1 class="about-content-header">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</h1>';
        } elseif (preg_match('/^subheader\((.+)\)\s*$/', $trimmed, $m)) {
            $flushPara();
            $html .= '<h2 class="about-content-subheader">' . htmlspecialchars(trim($m[1]), ENT_QUOTES, 'UTF-8') . '</h2>';
        } elseif (trim($trimmed) === '') {
            $flushPara();
        } else {
            $paraLines[] = $trimmed;
        }
    }
    $flushPara();

    return $html;
}

$renderedContent = renderAboutContent($rawContent, $imgCache);
?>
<link rel="stylesheet" href="../task2/about.css" />

<main class="about-page-container">

    <?php if ($renderedContent): ?>
        <section class="about-dynamic-content">
            <?= $renderedContent ?>
        </section>
    <?php else: ?>
        <section class="about-split-section">
            <div class="split-image">
                <img src="../uploads/homepage/5.jpeg" alt="Workshop">
            </div>
            <div class="split-text">
                <h2 class="section-title">Our Heritage</h2>
                <p class="section-content">
                    At Olivewood Atelier, we believe that furniture is more than just utility—it is an anchor for the home.
                </p>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php include '../php/footer.php'; ?>
