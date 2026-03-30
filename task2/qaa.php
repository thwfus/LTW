<?php include '../php/header.php'; ?>
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

        <div class="qaa-item" data-category="product">
          <button class="qaa-question">
            Does Olivewood offer custom interior design services?
            <span>+</span>
          </button>
          <div class="qaa-answer">
            <p>
              Yes. We offer design support and customization for dimensions, materials,
              colors, and surface finishes based on each customer's needs.
            </p>
          </div>
        </div>

        <div class="qaa-item" data-category="order">
          <button class="qaa-question">
            Can I place an order directly on the website?
            <span>+</span>
          </button>
          <div class="qaa-answer">
            <p>
              Yes. You can add products to your cart, fill in your contact information,
              confirm your order, and wait for our customer support team to contact you.
            </p>
          </div>
        </div>

        <div class="qaa-item" data-category="shipping">
          <button class="qaa-question">
            How long does delivery usually take?
            <span>+</span>
          </button>
          <div class="qaa-answer">
            <p>
              Delivery usually takes 3 to 7 business days for in-stock items.
              For custom-made products, the lead time may be longer depending on complexity.
            </p>
          </div>
        </div>

        <div class="qaa-item" data-category="warranty">
          <button class="qaa-question">
            What is Olivewood's warranty policy?
            <span>+</span>
          </button>
          <div class="qaa-answer">
            <p>
              Our products are covered for technical defects caused by manufacturing issues.
              The warranty period may vary depending on the product line.
            </p>
          </div>
        </div>

        <div class="qaa-item" data-category="product">
          <button class="qaa-question">
            Where can I view the products in person?
            <span>+</span>
          </button>
          <div class="qaa-answer">
            <p>
              You can visit the Olivewood showroom to see the materials,
              colors, and furniture collections in person.
            </p>
          </div>
        </div>
      </div>
    </div>

    <aside class="qaa-right">
      <div class="qaa-support-card">
        <h3>Still can't find your answer?</h3>
        <p>
          Send us your question and Olivewood will get back to you as soon as possible.
        </p>
        <form class="qaa-form">
          <input type="text" placeholder="Full name" required />
          <input type="email" placeholder="Email" required />
          <input type="text" placeholder="Question title" required />
          <textarea rows="5" placeholder="Enter your question..." required></textarea>
          <button type="submit" class="qaa-submit-btn">Submit Question</button>
        </form>
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
