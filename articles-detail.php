<?php
/**
 * Star51 - Article Detail Page
 * Display full article with Hybrid Adaptive Layout
 * Layout: Sidebar(1/3) with Images carousel top + Video below | Content(2/3)
 */

// Include database config
require_once 'nova/legas/nova_config.php';

// Load language file early
require_once 'inc/inc_star51_lang.php';

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$article_id) {
  // Invalid ID - redirect to articles page
  header('Location: articles.php');
  exit();
}

// Fetch article data (optimized - no joins, only used fields)
$query = "
  SELECT
    a.article_title,
    a.article_content,
    a.article_summary,
    a.item_collection,
    a.item_year,
    a.youtube_video,
    a.image_1,
    a.image_2,
    a.publish_date,
    a.updated_at,
    a.show_publish_date
  FROM ns_articles a
  WHERE a.id_article = ?
    AND a.is_active = 1
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
  // Article not found or not active - redirect
  header('Location: articles.php');
  exit();
}

$article = mysqli_fetch_assoc($result);

// Page configuration
$page_title = htmlspecialchars($article['article_title']);
$page_description = htmlspecialchars($article['article_summary']);
$page_image = !empty($article['image_1']) ? 'file_db_med/' . $article['image_1'] : null;
$current_page = 'articles';
$use_glightbox = true;

// Helper function: Extract YouTube video ID from URL or ID string
function get_youtube_id($url_or_id)
{
  // If already just an ID (11 characters), return as is
  if (strlen($url_or_id) == 11 && strpos($url_or_id, '/') === false) {
    return $url_or_id;
  }

  // Extract from various YouTube URL formats
  $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
  if (preg_match($pattern, $url_or_id, $matches)) {
    return $matches[1];
  }

  // If no match, return original (might be already just ID)
  return $url_or_id;
}

// Collect sidebar images (image_1, image_2 for carousel)
$sidebar_images = [];
if (!empty($article['image_1'])) {
  $sidebar_images[] = ['file' => $article['image_1']];
}
if (!empty($article['image_2'])) {
  $sidebar_images[] = ['file' => $article['image_2']];
}

// Sidebar logic: Always show sidebar (placeholder if no images)
$has_video = !empty($article['youtube_video']);
$has_sidebar = true;

// Load Nova config (needed for $site_name)
require_once 'nova/conf/nova_config_values.php';
$site_name = !empty($nova_settings['site_name']) ? $nova_settings['site_name'] : 'NovaStar51';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';
?>

  <!-- ========== MAIN CONTENT SECTION ========== -->
  <!-- Article detail page content - Hybrid Adaptive Layout -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">

      <!-- ========== HEADER SECTION ========== -->
      <header class="star51-section-header text-center mb-5 rounded-4 p-4">
        <h1 class="display-6 mb-3"><?= htmlspecialchars($article['article_title']) ?></h1>
        <?php if (!empty($article['article_summary'])): ?>
          <p class="lead"><?= htmlspecialchars($article['article_summary']) ?></p>
        <?php endif; ?>
      </header>

      <!-- ========== ADAPTIVE LAYOUT SECTION ========== -->
      <!-- Sidebar(1/3): Images carousel + Video | Content(2/3): Badges + Text -->
      <section class="mb-5">
        <div class="row g-4">

          <?php if ($has_sidebar): ?>
          <!-- SIDEBAR (1/3) - Sticky on desktop -->
          <div class="col-lg-4">
            <div class="sticky-sidebar">

              <!-- Image section: Carousel (2+), Single image (1), or Placeholder (0) -->
              <?php if (count($sidebar_images) >= 2): ?>
              <!-- Carousel (2+ images) -->
              <div class="card star51-card mb-3">
                <div class="card-body p-0">
                  <div id="imageCarousel" class="carousel slide" data-bs-ride="false">
                    <!-- Carousel Indicators -->
                    <div class="carousel-indicators">
                      <?php foreach ($sidebar_images as $index => $img): ?>
                      <button type="button"
                              data-bs-target="#imageCarousel"
                              data-bs-slide-to="<?= $index ?>"
                              <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                              aria-label="Slide <?= $index + 1 ?>"></button>
                      <?php endforeach; ?>
                    </div>

                    <!-- Carousel Items -->
                    <div class="carousel-inner">
                      <?php foreach ($sidebar_images as $index => $img): ?>
                      <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <a href="file_db_max/<?= $img['file'] ?: 'nova-01-max.jpg' ?>"
                           class="glightbox"
                           data-gallery="article-images">
                          <img src="file_db_med/<?= $img['file'] ?: 'nova-01-med.jpg' ?>"
                               class="d-block w-100 star51-carousel-img star51-modal-trigger"
                               alt="<?= htmlspecialchars($article['article_title']) ?>"
                               loading="lazy" decoding="async" />
                        </a>
                      </div>
                      <?php endforeach; ?>
                    </div>

                    <!-- Carousel Controls -->
                    <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                      <span class="carousel-control-icon-wrapper">
                        <i class="bi bi-chevron-left"></i>
                      </span>
                      <span class="visually-hidden"><?= __front('carousel.previous') ?></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                      <span class="carousel-control-icon-wrapper">
                        <i class="bi bi-chevron-right"></i>
                      </span>
                      <span class="visually-hidden"><?= __front('carousel.next') ?></span>
                    </button>
                  </div>
                </div>
              </div>
              <?php elseif (count($sidebar_images) === 1): ?>
              <!-- Single image -->
              <div class="card star51-card mb-3">
                <div class="card-body p-0">
                  <a href="file_db_max/<?= $sidebar_images[0]['file'] ?>"
                     class="glightbox"
                     data-gallery="article-images">
                    <img src="file_db_med/<?= $sidebar_images[0]['file'] ?>"
                         class="d-block w-100 star51-carousel-img star51-modal-trigger"
                         alt="<?= htmlspecialchars($article['article_title']) ?>"
                         loading="lazy" decoding="async" />
                  </a>
                </div>
              </div>
              <?php else: ?>
              <!-- Placeholder image -->
              <div class="card star51-card mb-3">
                <div class="card-body p-0">
                  <img src="file_db_med/nova-01-med.jpg"
                       class="d-block w-100 star51-carousel-img"
                       alt="<?= htmlspecialchars($article['article_title']) ?>"
                       loading="lazy" decoding="async" />
                </div>
              </div>
              <?php endif; ?>

              <!-- Priority 2: Video YouTube (below carousel) -->
              <?php if (!empty($article['youtube_video'])): ?>
              <div class="card star51-card">
                <div class="card-body p-0">
                  <div class="ratio ratio-16x9">
                    <iframe
                      src="https://www.youtube.com/embed/<?= htmlspecialchars(get_youtube_id($article['youtube_video'])) ?>"
                      title="<?= htmlspecialchars($article['article_title']) ?> - Video"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowfullscreen
                      loading="lazy">
                    </iframe>
                  </div>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>
          <?php endif; ?>

          <!-- MAIN CONTENT (2/3 or full width if no sidebar) -->
          <div class="<?= $has_sidebar ? 'col-lg-8' : 'col-12' ?>">
            <div class="card star51-card">
              <div class="card-body p-4">

                <!-- Metadata Badges -->
                <div class="mb-4">
                  <?php if (!empty($article['item_year'])): ?>
                    <span class="badge star51-highlight me-2 mb-2"><?= htmlspecialchars($article['item_year']) ?></span>
                  <?php endif; ?>

                  <?php if (!empty($article['item_collection'])): ?>
                    <span class="badge bg-secondary me-2 mb-2">
                      <i class="bi bi-collection me-1"></i><?= htmlspecialchars($article['item_collection']) ?>
                    </span>
                  <?php endif; ?>
                </div>

                <!-- Publish Date -->
                <?php if ($article['show_publish_date'] == 1 && !empty($article['publish_date'])): ?>
                  <p class="text-muted small mb-4">
                    <i class="bi bi-calendar-event me-2"></i>
                    <?= __front('article.published_on') ?>
                    <?= format_date_i18n($article['publish_date']) ?>
                  </p>
                <?php endif; ?>

                <!-- Quill Content Display -->
                <div class="ql-container ql-snow">
                  <div class="ql-editor">
                    <?php if (!empty($article['article_content'])): ?>
                      <?= $article['article_content']
                      // Quill HTML - trusted admin content
                      ?>
                    <?php else: ?>
                      <p class="text-muted"><?= __front('article.no_content') ?></p>
                    <?php endif; ?>
                  </div>
                </div>

              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ========== NAVIGATION BUTTONS ========== -->
      <!-- Back button - returns to previous page -->
      <section class="text-center mt-5">
        <a href="articles.php"
         onclick="event.preventDefault(); history.back();"
         class="btn btn-star51 btn-pill">
        <i class="bi bi-arrow-left me-2"></i>
        <span class="btn-text"><?= __front('buttons.back') ?></span>
      </a>
      </section>

    </div>
  </main>

<?php
// Include common FOOTER
include 'inc/inc_footer.php';

// Include common SCRIPTS
include 'inc/inc_scripts.php';
?>

  <!-- GLightbox initialization - article image gallery -->
  <script>
    var lightbox = GLightbox({
      selector: '.glightbox',
      touchNavigation: true,
      loop: true,
      openEffect: 'fade',
      closeEffect: 'fade',
      slideEffect: 'slide',
      closeButton: true
    });
  </script>
