<?php
/**
 * Star51 - News Page
 * News page with 6-card grid (2 per row)
 */

// Database connection (BEFORE any HTML output)
require_once 'nova/legas/nova_config.php';
require_once 'inc/inc_reserved_ids.php';

// Load language file early (needed for $page_title)
require_once 'inc/inc_star51_lang.php';

// Page configuration
$page_title = __front('nav.news');
$page_description = __front('news.subtitle');
$current_page = 'news';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';

// ============================================
// DATABASE QUERIES
// ============================================

// Pagination setup
$news_per_page = 4;
$current_page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page_num - 1) * $news_per_page;

// Count total news articles (using SUBCATEGORY_NEWS constant)
// Purpose: Pagination for dedicated news page
$count_query = "
  SELECT COUNT(id_article) AS total
  FROM ns_articles
  WHERE is_active = 1
    AND id_subcategory = " . SUBCATEGORY_NEWS . "
";
$count_result = mysqli_query($conn, $count_query);
$total_news = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_news / $news_per_page);

// Query news articles (using SUBCATEGORY_NEWS constant)
// Purpose: Fetch news articles for dedicated news page with pagination
$query_news = "
  SELECT
    id_article,
    article_title,
    article_summary,
    image_1,
    publish_date,
    show_publish_date
  FROM ns_articles
  WHERE is_active = 1
    AND id_subcategory = " . SUBCATEGORY_NEWS . "
  ORDER BY publish_date DESC
  LIMIT ? OFFSET ?
";

$stmt_news = mysqli_prepare($conn, $query_news);
mysqli_stmt_bind_param($stmt_news, 'ii', $news_per_page, $offset);
mysqli_stmt_execute($stmt_news);
$result_news = mysqli_stmt_get_result($stmt_news);

if (!$result_news) {
  error_log("Star51 News Page Query Error: " . mysqli_error($conn));
  $news_articles = [];
} else {
  $news_articles = mysqli_fetch_all($result_news, MYSQLI_ASSOC);
}
?>

<!-- ========== MAIN CONTENT SECTION ========== -->
<main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
  <div class="container">

    <!-- Section header -->
    <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
      <h1 class="display-6 mb-3"><?= __front('news.title') ?></h1>
      <p class="lead"><?= __front('news.subtitle') ?></p>
    </header>

    <!-- ========== NEWS GRID - Dynamic from database ========== -->
    <section class="news-grid">
      <div class="row g-4">

        <?php if (!empty($news_articles)): ?>
          <?php foreach ($news_articles as $news): ?>
            <!-- News article card -->
            <div class="col-lg-6">
              <article class="card star51-card h-100">
                <img src="file_db_med/<?= $news['image_1'] ? $news['image_1'] : 'nova-01-med.jpg' ?>"
                     class="card-img-top"
                     alt="<?= htmlspecialchars($news['article_title']) ?>"
                     loading="lazy" decoding="async" />
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge bg-star51-orange text-white"><?= __front('news.badge') ?></span>
                    <small class="text-muted"><?= format_date_i18n($news['publish_date']) ?></small>
                  </div>
                  <h3 class="card-title h5 mb-3"><?= htmlspecialchars($news['article_title']) ?></h3>
                  <p class="card-text flex-grow-1"><?= htmlspecialchars($news['article_summary']) ?></p>
                  <div class="d-flex justify-content-end mt-auto">
                    <a href="articles-detail.php?id=<?= $news['id_article'] ?>" class="btn btn-star51 btn-pill">
                      <span class="btn-text"><?= __front('buttons.read_more') ?></span>
                      <i class="bi bi-arrow-right btn-icon"></i>
                    </a>
                  </div>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- Fallback: no news found -->
          <div class="col-12">
            <div class="alert alert-info text-center" role="alert">
              <i class="bi bi-info-circle me-2"></i>
              <?= __front('news.no_news') ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </section>

    <!-- Pagination -->
    <?php
    // Pagination parameters
    $base_url = 'news.php';
    $query_params = []; // No filters for news
    $aria_label = __front('news.navigation');
    $total_items = $total_news;
    $items_per_page = $news_per_page;
    include 'inc/inc_pagination.php';
    ?>

  </div>
</main>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>
