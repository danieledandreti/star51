<?php
/**
 * Star51 - Homepage
 * Main page with hero carousel, product grid and news sidebar
 */

// Database connection (BEFORE any HTML output)
require_once 'nova/legas/nova_config.php';
require_once 'inc/inc_reserved_ids.php';

// Load language file early (needed for $page_title)
require_once 'inc/inc_star51_lang.php';

// Page configuration
$page_title = __front('nav.home');
$page_description = __front('homepage.page_description');
$current_page = 'index';
$page_preload = [['href' => 'img/ns51_carousel-01.jpg', 'as' => 'image']];

// Include common HEAD (loads language file)
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';

// ============================================
// DATABASE QUERIES
// ============================================

// ============================================
// QUERY NEWS - Last 4 articles from "News" subcategory (RESERVED)
// Using SUBCATEGORY_NEWS constant (value: 2)
// Purpose: Homepage sidebar news feed
// ============================================
$query_news = "
  SELECT
    id_article,
    article_title,
    image_1,
    publish_date
  FROM ns_articles
  WHERE is_active = 1
    AND id_subcategory = " . SUBCATEGORY_NEWS . "
    AND publish_date IS NOT NULL
  ORDER BY publish_date DESC
  LIMIT 4
";

$result_news = mysqli_query($conn, $query_news);

if (!$result_news) {
  error_log("Star51 Homepage News Query Error: " . mysqli_error($conn));
  $news = [];
} else {
  $news = mysqli_fetch_all($result_news, MYSQLI_ASSOC);
}

// ============================================
// QUERY CATEGORIES - All active categories (EXCLUDE system reserved)
// Using CATEGORY_EXTRA and CATEGORY_INFO constants
// Purpose: Homepage category grid - exclude system categories
// ============================================
$query_categories = "
  SELECT
    id_category,
    category_name,
    category_description
  FROM ns_categories
  WHERE is_active = 1
    AND id_category NOT IN (" . CATEGORY_EXTRA . ", " . CATEGORY_INFO . ")
  ORDER BY id_category DESC
";

$result_categories = mysqli_query($conn, $query_categories);

if (!$result_categories) {
  error_log("Star51 Homepage Categories Query Error: " . mysqli_error($conn));
  $categories = [];
} else {
  $categories = mysqli_fetch_all($result_categories, MYSQLI_ASSOC);
}
?>

<!-- ========== HERO CAROUSEL ========== -->
<!-- Bootstrap carousel with dynamic slides from database -->
<section class="hero-section">
  <!-- H1 for SEO/Accessibility (visually hidden) -->
  <h1 class="visually-hidden"><?= __front('nav.home') ?> | Star51</h1>

  <div class="container">
    <div id="heroSlider" class="carousel slide hero-slider" data-bs-ride="carousel" role="region" aria-label="Featured content carousel">

      <!-- Carousel indicators -->
      <div class="carousel-indicators" role="tablist" aria-label="Carousel pagination">
        <button type="button"
                data-bs-target="#heroSlider"
                data-bs-slide-to="0"
                class="active"
                aria-current="true"
                aria-label="Slide 1 of 3"></button>
        <button type="button"
                data-bs-target="#heroSlider"
                data-bs-slide-to="1"
                aria-label="Slide 2 of 3"></button>
        <button type="button"
                data-bs-target="#heroSlider"
                data-bs-slide-to="2"
                aria-label="Slide 3 of 3"></button>
      </div>

      <!-- Carousel slides (static promotional) -->
      <div class="carousel-inner">
        <!-- Slide 1 -->
        <div class="carousel-item active">
          <img src="img/ns51_carousel-01.jpg"
               alt="<?= __front('homepage.slide1_title') ?>" />
          <div class="carousel-caption">
            <h2><?= __front('homepage.slide1_title') ?></h2>
            <p><?= __front('homepage.slide1_subtitle') ?></p>
          </div>
        </div>
        <!-- Slide 2 -->
        <div class="carousel-item">
          <img src="img/ns51_carousel-02.jpg"
               alt="<?= __front('homepage.slide2_title') ?>"
               loading="lazy"
               decoding="async" />
          <div class="carousel-caption">
            <h2><?= __front('homepage.slide2_title') ?></h2>
            <p><?= __front('homepage.slide2_subtitle') ?></p>
          </div>
        </div>
        <!-- Slide 3 -->
        <div class="carousel-item">
          <img src="img/ns51_carousel-03.jpg"
               alt="<?= __front('homepage.slide3_title') ?>"
               loading="lazy"
               decoding="async" />
          <div class="carousel-caption">
            <h2><?= __front('homepage.slide3_title') ?></h2>
            <p><?= __front('homepage.slide3_subtitle') ?></p>
          </div>
        </div>
      </div>

      <!-- Carousel controls -->
      <button class="carousel-control-prev"
              type="button"
              data-bs-target="#heroSlider"
              data-bs-slide="prev"
              aria-label="<?= __front('carousel.previous') ?>">
        <span class="carousel-control-icon-wrapper">
          <i class="bi bi-chevron-left"></i>
        </span>
      </button>
      <button class="carousel-control-next"
              type="button"
              data-bs-target="#heroSlider"
              data-bs-slide="next"
              aria-label="<?= __front('carousel.next') ?>">
        <span class="carousel-control-icon-wrapper">
          <i class="bi bi-chevron-right"></i>
        </span>
      </button>

    </div>
  </div>
</section>

<!-- ========== MAIN CONTENT SECTION ========== -->
<!-- Products grid + news sidebar layout -->
<main class="content-section py-5" role="main" id="main-content">
  <div class="container">

    <!-- Section header -->
    <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
      <h2 class="display-6 mb-3"><?= __front('homepage.categories_title') ?></h2>
      <p class="lead"><?= __front('homepage.categories_subtitle') ?></p>
    </header>

    <!-- Main layout: 9/3 column split -->
    <div class="row g-4">

      <!-- Products grid (9 columns) -->
      <div class="col-lg-9">
        <div class="row g-4">

          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $category): ?>
              <!-- Category card -->
              <div class="col-lg-4">
                <article class="card star51-card star51-card-fixed h-100">
                  <div class="star51-card-category-header">
                    <h3 class="card-title h5"><?= htmlspecialchars($category['category_name']) ?></h3>
                  </div>
                  <div class="card-body d-flex flex-column">
                    <p class="card-text flex-grow-1">
                      <?php
                      $description = !empty($category['category_description'])
                        ? $category['category_description']
                        : __front('homepage.category_fallback');
                      $desc_length = mb_strlen($description);
                      if ($desc_length > 120) {
                        echo htmlspecialchars(mb_substr($description, 0, 117)) . '...';
                      } else {
                        echo htmlspecialchars($description);
                      }
                      ?>
                    </p>
                    <div class="d-flex justify-content-center mt-auto">
                      <a href="articles.php?cat=<?= $category['id_category'] ?>"
                         class="btn btn-star51 btn-pill"
                         aria-label="<?= __front('buttons.go') ?>: <?= htmlspecialchars($category['category_name']) ?>">
                        <span class="btn-text"><?= __front('buttons.go') ?></span>
                        <i class="bi bi-arrow-right btn-icon"></i>
                      </a>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback: no categories found -->
            <div class="col-12">
              <p class="text-muted text-center"><?= __front('messages.no_categories') ?></p>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- ========== NEWS SIDEBAR ========== -->
      <!-- News sidebar (3 columns) with 4 latest articles -->
      <aside class="col-lg-3">
        <div class="news-sidebar">
          <h3 class="news-title mb-4"><i class="bi bi-newspaper me-2" aria-hidden="true"></i><?= __front('homepage.news_title') ?></h3>

          <?php if (!empty($news)): ?>
            <?php foreach ($news as $news_item): ?>
              <!-- News card -->
              <a href="articles-detail.php?id=<?= $news_item['id_article'] ?>" class="card news-card mb-3 text-decoration-none" aria-label="<?= __front('homepage.read_article') ?>: <?= htmlspecialchars($news_item['article_title']) ?>">
                <div class="row g-0">
                  <div class="col-4">
                    <img src="file_db_min/<?= $news_item['image_1'] ? $news_item['image_1'] : 'nova-01-min.jpg' ?>"
                         class="img-fluid news-img rounded-start"
                         alt="<?= htmlspecialchars($news_item['article_title']) ?>"
                         loading="lazy" decoding="async" />
                  </div>
                  <div class="col-8">
                    <div class="card-body p-3">
                      <h4 class="card-title mb-2 news-card-title h6"><?= htmlspecialchars($news_item['article_title']) ?></h4>
                      <small class="text-muted news-date">
                        <i class="bi bi-calendar-event me-1"></i><?= format_date_i18n($news_item['publish_date']) ?>
                      </small>
                    </div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback: no news found -->
            <p class="text-muted"><?= __front('messages.no_news') ?></p>
          <?php endif; ?>

          <!-- View all news CTA -->
          <div class="text-center mt-4">
            <a href="news.php" class="btn btn-star51 btn-pill news-all-btn" aria-label="<?= __front('buttons.view_all') ?> news">
              <span class="btn-text"><?= __front('buttons.view_all') ?></span>
              <i class="bi bi-arrow-right btn-icon"></i>
            </a>
          </div>
        </div>
      </aside>

    </div>
  </div>
</main>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>
