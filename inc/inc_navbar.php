<?php
/**
 * Star51 - Common NAVIGATION Section
 * Include file for common navbar across all pages
 *
 * Requires: inc_star51_lang.php loaded (via inc_head.php)
 *
 * Optional variables:
 * - $current_page (string): Current page name for active highlight ('index', 'articles', 'about', 'news', 'contact')
 */

// Default value if not specified
$current_page = $current_page ?? '';

// Helper function for active class
function isActive($page, $current) {
  return $page === $current ? ' active' : '';
}
?>

  <!-- ========== NAVIGATION ========== -->
  <!-- Fixed top navigation with search -->
  <nav class="navbar navbar-expand-lg fixed-top star51-nav" role="navigation" aria-label="Main navigation">
    <div class="container">

      <!-- Brand home icon -->
      <a class="navbar-brand" href="index.php" aria-label="Star51 Home">
        <i class="bi bi-house-heart-fill" aria-hidden="true"></i>
      </a>

      <!-- Mobile direct access icons -->
      <div class="d-flex d-lg-none gap-4 mobile-nav-icons ms-auto">
        <a href="articles.php" class="mobile-nav-icon" aria-label="<?= __front('nav.articles') ?>">
          <i class="bi bi-boxes"></i>
        </a>
        <a href="about.php" class="mobile-nav-icon" aria-label="<?= __front('nav.about') ?>">
          <i class="bi bi-people"></i>
        </a>
        <a href="news.php" class="mobile-nav-icon" aria-label="<?= __front('nav.news') ?>">
          <i class="bi bi-newspaper"></i>
        </a>
        <a href="contact.php" class="mobile-nav-icon" aria-label="<?= __front('nav.contact') ?>">
          <i class="bi bi-telephone"></i>
        </a>
        <a href="search.php" class="mobile-nav-icon" aria-label="<?= __front('nav.search') ?>">
          <i class="bi bi-search"></i>
        </a>
      </div>

      <!-- Desktop Menu - always visible on desktop, hidden on mobile -->
      <div class="d-none d-lg-block">
        <ul class="navbar-nav ms-auto d-flex flex-row align-items-center">
          <li class="nav-item me-3">
            <a class="nav-link<?= isActive('articles', $current_page) ?>" href="articles.php"><?= __front('nav.articles') ?></a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link<?= isActive('about', $current_page) ?>" href="about.php"><?= __front('nav.about') ?></a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link<?= isActive('news', $current_page) ?>" href="news.php"><?= __front('nav.news') ?></a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link<?= isActive('contact', $current_page) ?>" href="contact.php"><?= __front('nav.contact') ?></a>
          </li>
          <li class="nav-item">
            <!-- Search form -->
            <form class="position-relative" role="search" aria-label="<?= __front('nav.search') ?>" action="search.php" method="GET">
              <input class="form-control search-input pe-5"
                     type="search"
                     name="q"
                     placeholder="<?= __front('nav.search_placeholder') ?>"
                     aria-label="<?= __front('nav.search') ?>" />
              <button class="btn search-btn-internal position-absolute top-50 end-0 translate-middle-y me-1"
                      type="submit"
                      aria-label="<?= __front('nav.search') ?>">
                <i class="bi bi-search"></i>
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>
