<?php
/**
 * Star51 - Search Page
 * Search article titles and summaries with pagination
 */

// Database connection
require_once 'nova/legas/nova_config.php';

// Reserved IDs constants (CATEGORY_EXTRA, CATEGORY_INFO)
require_once 'inc/inc_reserved_ids.php';

// Load language file early (needed for $page_title)
require_once 'inc/inc_star51_lang.php';

// Get search query from URL
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_query_display = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
$current_page_num = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page_num < 1) {
 $current_page_num = 1;
}

// Search settings
$max_search_length = 255;
if (mb_strlen($search_query) > $max_search_length) {
  $search_query = mb_substr($search_query, 0, $max_search_length);
}
$search_words = preg_split('/[^\p{L}\p{N}]+/u', $search_query, -1, PREG_SPLIT_NO_EMPTY);
$has_valid_search = !empty($search_words);
$results_per_page = 9;
$offset = ($current_page_num - 1) * $results_per_page;

// Initialize variables
$search_results = null;
$total_results = 0;
$total_pages = 0;

if ($has_valid_search) {
  // Require every word in either the title or the summary.
  $search_conditions = [];
  $search_params = [];
  $search_types = '';
  $title_words = 'CONCAT(\' \', REGEXP_REPLACE(a.article_title, \'[^[:alnum:]]+\', \' \'), \' \')';
  $summary_words = 'CONCAT(\' \', REGEXP_REPLACE(a.article_summary, \'[^[:alnum:]]+\', \' \'), \' \')';

  foreach ($search_words as $word) {
    if (mb_strlen($word) <= 2) {
      // Normalize separators with a constant regex, then match the whole short term.
      $search_conditions[] = '(' . $title_words . ' LIKE ? OR ' . $summary_words . ' LIKE ?)';
      $search_pattern = '% ' . $word . ' %';
    } else {
      $search_conditions[] = '(a.article_title LIKE ? OR a.article_summary LIKE ?)';
      $search_pattern = '%' . $word . '%';
    }
    $search_params[] = $search_pattern;
    $search_params[] = $search_pattern;
    $search_types .= 'ss';
  }

  $search_where = implode(' AND ', $search_conditions);

 // Count total results - exclude system reserved categories and orphan articles
 $count_query = "
  SELECT COUNT(a.id_article) AS total
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE a.is_active = 1
    AND s.is_active = 1
    AND c.is_active = 1
    AND c.id_category NOT IN (" . CATEGORY_EXTRA . ", " . CATEGORY_INFO . ")
    AND " . $search_where . "
 ";

 $count_stmt = mysqli_prepare($conn, $count_query);
 mysqli_stmt_bind_param($count_stmt, $search_types, ...$search_params);
 mysqli_stmt_execute($count_stmt);
 $count_result = mysqli_stmt_get_result($count_stmt);
 $count_row = mysqli_fetch_assoc($count_result);
 $total_results = $count_row['total'];
 $total_pages = ceil($total_results / $results_per_page);

 // Fetch search results with pagination - exclude system reserved categories and orphan articles
 $search_sql = "
  SELECT
    a.id_article,
    a.article_title,
    a.article_summary,
    a.item_collection,
    a.item_year,
    a.image_1
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE a.is_active = 1
    AND s.is_active = 1
    AND c.is_active = 1
    AND c.id_category NOT IN (" . CATEGORY_EXTRA . ", " . CATEGORY_INFO . ")
    AND " . $search_where . "
  ORDER BY NULLIF(a.item_year, 0) DESC, a.article_title ASC, a.id_article DESC
  LIMIT ? OFFSET ?
 ";

 $search_stmt = mysqli_prepare($conn, $search_sql);
 $result_params = array_merge($search_params, [$results_per_page, $offset]);
 $result_types = $search_types . 'ii';
 mysqli_stmt_bind_param($search_stmt, $result_types, ...$result_params);
 mysqli_stmt_execute($search_stmt);
 $search_results = mysqli_stmt_get_result($search_stmt);
}

// Page configuration
$page_title = $has_valid_search ? __front('search.title') . ': ' . $search_query : __front('search.title');
$page_description = __front('search.subtitle');
$current_page = 'search';

// Include HEAD comune
include 'inc/inc_head.php';

// Include NAVBAR comune
include 'inc/inc_navbar.php';
?>

  <!-- ========== MAIN CONTENT SECTION ========== -->
  <!-- Search page content -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">

      <!-- Content section header -->
      <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
        <h1 class="display-6 mb-3"><?= __front('search.title') ?></h1>
        <p class="lead"><?= __front('search.subtitle') ?></p>
      </header>

      <!-- Search Form & Results -->
      <section class="mb-5">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <!-- Search Card -->
            <div class="card star51-card">
              <div class="card-body p-4">
                <!-- Search Instructions -->
                <div class="text-center mb-4">
                  <p class="text-muted mb-2" id="searchHint"><?= __front('search.input_hint') ?></p>
                </div>

                <!-- Main Search Form -->
                <form action="search.php" method="GET" class="star51-form">
                  <div class="input-group input-group-lg">
                    <input type="search" class="form-control form-control-lg" name="q" placeholder="<?= __front('search.input_placeholder') ?>" value="<?= $search_query_display ?>" aria-label="<?= __front('search.title') ?>" aria-describedby="searchHint" required aria-required="true" />
                    <button type="submit" class="btn btn-star51 btn-lg btn-pill" aria-label="<?= __front('search.title') ?>">
                      <i class="bi bi-search"></i>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Search Results Section -->
      <?php if ($has_valid_search): ?>
        <section>
          <!-- Results Info -->
          <div class="mb-4">
            <p class="h6">
              <strong><?= $total_results ?></strong> <?= __front('search.results_found') ?> "<span><?= $search_query_display ?></span>"
            </p>
            <hr>
          </div>

          <?php if ($total_results > 0): ?>
            <!-- Results Grid -->
            <div class="row g-4">
              <?php while ($article = mysqli_fetch_assoc($search_results)): ?>
                <div class="col-lg-4 col-md-6">
                  <?php include 'inc/inc_card_article.php'; ?>
                </div>
              <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php
            // Pagination parameters
            $base_url = 'search.php';
            $query_params = ['q' => $search_query];
            $aria_label = __front('search.navigation');
            include 'inc/inc_pagination.php';
            ?>

          <?php else: ?>
            <!-- No results -->
            <div class="alert alert-warning" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <?= __front('search.no_results') ?> "<strong><?= $search_query_display ?></strong>".
              <?= __front('search.no_results_hint') ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

    </div>
  </main>

<?php
// Include FOOTER comune
include 'inc/inc_footer.php';

// Include SCRIPTS comuni
include 'inc/inc_scripts.php';
?>
