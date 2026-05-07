<?php 
  include '../php/header.php'; 
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  require_once '../task1/config_m1.php';
?>
<link rel="stylesheet" href="../task2/qaa.css" />

<main class="qaa-page">
  <section class="qaa-hero">
    <div class="qaa-hero-content">
      <p class="qaa-kicker">OLIVEWOOD SUPPORT</p>
      <h1 class="qaa-title">Questions & Answers</h1>

    </div>
  </section>

  <section class="qaa-search-section">
    <div class="qaa-search-box">
      <input
        type="text"
        id="qaaSearchInput"
        placeholder="Search questions..."
        class="qaa-search-input"
      />
      <button type="button" class="qaa-search-btn">Search</button>
    </div>

    <div class="qaa-filter-list">
      <button class="qaa-filter active" data-category="all">All</button>
      <button class="qaa-filter" data-category="product">Products</button>
      <button class="qaa-filter" data-category="order">Orders</button>
      <button class="qaa-filter" data-category="shipping">Shipping</button>
      <button class="qaa-filter" data-category="warranty">Warranty</button>
    </div>
  </section>

  <section class="qaa-content">
    <div class="qaa-left">
      <div class="qaa-group">
        <h2 class="qaa-group-title">Frequently Asked Questions</h2>
        <?php
          $qst = $conn->query("SELECT * FROM qaa where status = 'pub'");
          if ($qst->num_rows > 0) {
            while ($row = $qst->fetch_assoc()) {
              echo '<div class="qaa-item" data-category="' . $row['category'] . '">';
              echo '<button class="qaa-question">' . $row['title'] . '<span>+</span></button>';
              echo '<div class="qaa-answer"><p>' . $row['content'] . '</p></div>';
              echo '</div>';
            }
          }
        ?>

      </div>
    </div>

    <aside class="qaa-right">
      <div class="qaa-support-card">
        <h3>Still can't find your answer?</h3>
        <p>
          Send us your question and Olivewood will get back to you as soon as possible.
        </p>
        <form class="qaa-form" method ="POST">
          <input type="text" name="fullname" placeholder="Full name" required />
          <input type="email" name="email" placeholder="Email" required />
          <input type="text" name="title" placeholder="Question title" required />
          <button type="submit" name="qsubmit" class="qaa-submit-btn">Submit Question</button>
        </form>

        <?php
          if (isset($_POST['qsubmit'])) {
            $email = $_POST['email'];
            $title = $_POST['title'];

            $conn->query("INSERT INTO qaa (title, content, category, status) VALUES ('$title', '$email', 'product', 'draft')");
            echo "<p>Your question has been submitted. We will get back to you soon.</p>";
          }
        ?>
      </div>
    </aside>
  </section>
</main>

<script>
  const qaaQuestions = document.querySelectorAll('.qaa-question');
  const qaaFilters = document.querySelectorAll('.qaa-filter');
  const qaaItems = document.querySelectorAll('.qaa-item');
  const qaaSearchInput = document.getElementById('qaaSearchInput');

  qaaQuestions.forEach((btn) => {
    btn.addEventListener('click', () => {
      const item = btn.parentElement;
      item.classList.toggle('open');
      const mark = btn.querySelector('span');
      mark.textContent = item.classList.contains('open') ? '−' : '+';
    });
  });

  qaaFilters.forEach((filter) => {
    filter.addEventListener('click', () => {
      qaaFilters.forEach((btn) => btn.classList.remove('active'));
      filter.classList.add('active');
      const category = filter.dataset.category;

      qaaItems.forEach((item) => {
        if (category === 'all' || item.dataset.category === category) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });

  qaaSearchInput.addEventListener('input', () => {
    const keyword = qaaSearchInput.value.toLowerCase().trim();

    qaaItems.forEach((item) => {
      const text = item.innerText.toLowerCase();
      item.style.display = text.includes(keyword) ? 'block' : 'none';
    });
  });
</script>

<?php include '../php/footer.php'; ?>
