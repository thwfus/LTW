<?php
// TODO: Khởi tạo session để kiểm tra trạng thái đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra login và vai trò
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'guest';

// Logic điều hướng User Icon: Khách -> login | Admin -> Dashboard | User -> Profile
$userLink = "../html/login.php";
if ($isLoggedIn) {
    $userLink = ($userRole === 'admin') ? "../html/admin_dashboard.php" : "../html/user.php";
}

// Logic điều hướng Cart Icon: Khách -> login | Đã đăng nhập -> Cart
$cartLink = $isLoggedIn ? "../html/cart.php" : "../html/login.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Trustworthy Opulent Anteater</title>
    <meta property="og:title" content="Trustworthy Opulent Anteater" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="utf-8" />
    <meta property="twitter:card" content="summary_large_image" />

    <style data-tag="reset-style-sheet">
      html {  line-height: 1.15;}body {  margin: 0;}* {  box-sizing: border-box;  border-width: 0;  border-style: solid;  -webkit-font-smoothing: antialiased;}p,li,ul,pre,div,h1,h2,h3,h4,h5,h6,figure,blockquote,figcaption {  margin: 0;  padding: 0;}button {  background-color: transparent;}button,input,optgroup,select,textarea {  font-family: inherit;  font-size: 100%;  line-height: 1.15;  margin: 0;}button,select {  text-transform: none;}button,[type="button"],[type="reset"],[type="submit"] {  -webkit-appearance: button;  color: inherit;}button::-moz-focus-inner,[type="button"]::-moz-focus-inner,[type="reset"]::-moz-focus-inner,[type="submit"]::-moz-focus-inner {  border-style: none;  padding: 0;}button:-moz-focus,[type="button"]:-moz-focus,[type="reset"]:-moz-focus,[type="submit"]:-moz-focus {  outline: 1px dotted ButtonText;}a {  color: inherit;  text-decoration: inherit;}pre {  white-space: normal;}input {  padding: 2px 4px;}img {  display: block;}details {  display: block;  margin: 0;  padding: 0;}summary::-webkit-details-marker {  display: none;}[data-thq="accordion"] [data-thq="accordion-content"] {  max-height: 0;  overflow: hidden;  transition: max-height 0.3s ease-in-out;  padding: 0;}[data-thq="accordion"] details[data-thq="accordion-trigger"][open] + [data-thq="accordion-content"] {  max-height: 1000vh;}details[data-thq="accordion-trigger"][open] summary [data-thq="accordion-icon"] {  transform: rotate(180deg);}html { scroll-behavior: smooth  }
    </style>
    <style data-tag="default-style-sheet">
      html {
        font-family: "Libre Franklin";
        font-size: 1rem;
      }

      body {
        font-weight: 400;
        font-style:normal;
        text-decoration: undefined;
        text-transform: undefined;
        letter-spacing: 0.02em;
        line-height: 1.6;
        color: var(--color-on-surface);
        background: var(--color-surface);

        fill: var(--color-on-surface);
      }
    </style>
    <link
      rel="stylesheet"
      href="https://unpkg.com/animate.css@4.1.1/animate.css"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
      data-tag="font"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=STIX+Two+Text:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&amp;display=swap"
      data-tag="font"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
      data-tag="font"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Libre+Franklin:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
      data-tag="font"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
      data-tag="font"
    />
  </head>
  <body>
    <link rel="stylesheet" href="../css/style.css" />
    <div>
      <link href="../task1/index.css" rel="stylesheet" />

      <div class="home-container1">
        <navigation-wrapper class="navigation-wrapper">
          <!--Navigation component-->
          <div class="navigation-container1">
            <nav class="navigation-wrapper">
              <div class="navigation-container">
                <div class="navigation-brand">
                  <a href="../task1/index.php">
                    <div class="navigation-logo-link">
                      <span class="section-title">Olivewood</span>
                    </div>
                  </a>
                </div>
                <div class="navigation-desktop-menu">
                  <ul class="navigation-links">
                    <li>
                      <a href="../task2/about.php">
                        <div class="navigation-link"><span>About</span></div>
                      </a>
                    </li>
                    <li>
                      <a href="../task3/products.php">
                        <div class="navigation-link"><span>Products</span></div>
                      </a>
                    </li>
                    <li>
                      <a href="../task1/contact.php">
                        <div class="navigation-link"><span>Contact</span></div>
                      </a>
                    </li>
                    <li>
                      <a href="../task4/news.php">
                        <div class="navigation-link"><span>News</span></div>
                      </a>
                    </li>
                    <li>
                      <a href="../task1/prices.php">
                        <div class="navigation-link"><span>Prices</span></div>
                      </a>
                    </li>
                    <li>
                      <a href="../task2/qaa.php">
                        <div class="navigation-link"><span>Q & A</span></div>
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="navigation-actions">
                  <div class="navigation-search-wrapper">
                    <button
                      id="searchToggle"
                      aria-label="Open search"
                      class="navigation-action-btn"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                      >
                        <g
                          fill="none"
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                        >
                          <path d="m21 21l-4.34-4.34"></path>
                          <circle cx="11" cy="11" r="8"></circle>
                        </g>
                      </svg>
                    </button>
                    <div id="searchDropdown" class="navigation-search-dropdown">
                      <form
                        action="/search"
                        method="GET"
                        data-form-id="ab630534-f5c4-485c-b952-60d32f0984fc"
                        class="navigation-search-form"
                      >
                        <input
                          type="search"
                          placeholder="Search collection..."
                          required="true"
                          id="thq_textinput_0pF1"
                          name="textinput"
                          data-form-field-id="thq_textinput_0pF1"
                          class="navigation-search-input"
                        />
                        <button
                          type="submit"
                          id="thq_button_QvbA"
                          name="button"
                          data-form-field-id="thq_button_QvbA"
                          class="navigation-search-submit"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                          >
                            <g
                              fill="none"
                              stroke="currentColor"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                            >
                              <path d="m21 21l-4.34-4.34"></path>
                              <circle cx="11" cy="11" r="8"></circle>
                            </g>
                          </svg>
                        </button>
                      </form>
                    </div>
                  </div>
                  <a href="<?= $userLink ?>">
                    <div
                      aria-label="User profile"
                      class="navigation-action-btn"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                      >
                        <g
                          fill="none"
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                        >
                          <path
                            d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"
                          ></path>
                          <circle cx="12" cy="7" r="4"></circle>
                        </g>
                      </svg>
                    </div>
                  </a>
                  <a href="<?= $cartLink ?>">
                    <div
                      aria-label="Shopping cart"
                      class="navigation-action-btn navigation-cart-btn"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                      >
                        <g
                          fill="none"
                          stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                        >
                          <path
                            d="M16 10a4 4 0 0 1-8 0M3.103 6.034h17.794"
                          ></path>
                          <path
                            d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"
                          ></path>
                        </g>
                      </svg>
                      <span class="navigation-cart-count">0</span>
                    </div>
                  </a>
                  <button
                    id="mobileMenuOpen"
                    aria-label="Open menu"
                    class="navigation-mobile-toggle"
                  >
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
                        d="M4 8h16M4 16h16"
                      ></path>
                    </svg>
                  </button>
                </div>
              </div>
            </nav>
            <div id="mobileOverlay" class="navigation-mobile-overlay">
              <div class="navigation-mobile-header">
                <div class="navigation-brand">
                  <span class="section-title">Olivewood</span>
                </div>
                <button
                  id="mobileMenuClose"
                  aria-label="Close menu"
                  class="navigation-mobile-close"
                >
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
                      d="M18 6L6 18M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>
              <div class="navigation-mobile-content">
                <ul class="navigation-mobile-links">
                  <li class="navigation-mobile-item">
                    <a href="../task2/about.php">
                      <div class="navigation-mobile-link">
                        <span>About</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                  <li class="navigation-mobile-item">
                    <a href="../task3/products.php">
                      <div class="navigation-mobile-link">
                        <span>Products</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                  <li class="navigation-mobile-item">
                    <a href="../task1/contact.php">
                      <div class="navigation-mobile-link">
                        <span>Contact</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                  <li class="navigation-mobile-item">
                    <a href="../task4/news.php">
                      <div class="navigation-mobile-link">
                        <span>News</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                  <li class="navigation-mobile-item">
                    <a href="../task4/prices.php">
                      <div class="navigation-mobile-link">
                        <span>Prices</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                  <li class="navigation-mobile-item">
                    <a href="../task2/qaa.php">
                      <div class="navigation-mobile-link">
                        <span>Q & A</span>
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
                            d="m9 18l6-6l-6-6"
                          ></path>
                        </svg>
                      </div>
                    </a>
                  </li>
                </ul>
                <div class="navigation-mobile-footer">
                  <a href="../task3/products.php">
                    <div class="navigation-mobile-cta btn btn-primary">
                      <span>Shop All Products</span>
                    </div>
                  </a>
                  <div class="navigation-mobile-util">
                    <a href="Homepage">
                      <div class="navigation-util-link">
                        <span>Track Order</span>
                      </div>
                    </a>
                    <a href="Homepage">
                      <div class="navigation-util-link">
                        <span>Support</span>
                      </div>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            <div class="navigation-container2">
              <div class="navigation-container3">
                <script defer="" data-name="navigation-logic">
                  document.addEventListener('DOMContentLoaded', function() {
                    (function(){
                      const searchToggle = document.getElementById("searchToggle")
                      const searchDropdown = document.getElementById("searchDropdown")
                      const mobileMenuOpen = document.getElementById("mobileMenuOpen")
                      const mobileMenuClose = document.getElementById("mobileMenuClose")
                      const mobileOverlay = document.getElementById("mobileOverlay")

                      if (searchToggle && searchDropdown) {
                        searchToggle.addEventListener("click", (e) => {
                          e.stopPropagation()
                          searchDropdown.classList.toggle("active")
                        })

                        document.addEventListener("click", (e) => {
                          if (!searchDropdown.contains(e.target) && e.target !== searchToggle) {
                            searchDropdown.classList.remove("active")
                          }
                        })
                      }

                      if (mobileMenuOpen && mobileOverlay) {
                        mobileMenuOpen.addEventListener("click", () => {
                          mobileOverlay.classList.add("active")
                          document.body.style.overflow = "hidden"
                        })
                      }

                      if (mobileMenuClose && mobileOverlay) {
                        mobileMenuClose.addEventListener("click", () => {
                          mobileOverlay.classList.remove("active")
                          document.body.style.overflow = ""
                        })
                      }

                      // Handle escape key for all overlays
                      document.addEventListener("keydown", (e) => {
                        if (e.key === "Escape") {
                          if (searchDropdown) searchDropdown.classList.remove("active")
                          if (mobileOverlay) {
                            mobileOverlay.classList.remove("active")
                            document.body.style.overflow = ""
                          }
                        }
                      })

                      // Scroll behavior for nav bar
                      let lastScroll = 0
                      const nav = document.querySelector(".navigation-wrapper")

                      window.addEventListener("scroll", () => {
                        const currentScroll = window.pageYOffset

                        if (currentScroll <= 0) {
                          nav.style.transform = "translateY(0)"
                          return
                        }

                        if (currentScroll > lastScroll && !mobileOverlay.classList.contains("active")) {
                          // Scrolling down
                          nav.style.transform = "translateY(-100%)"
                        } else {
                          // Scrolling up
                          nav.style.transform = "translateY(0)"
                        }
                        lastScroll = currentScroll
                      })
                    })()
                  });
                </script>
              </div>
            </div>
          </div>
        </navigation-wrapper>