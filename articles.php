<?php
/**
 * Star51 - Articles Page
 * Dynamic article listing with category/subcategory filters
 */

// Database connection
require_once 'nova/legas/nova_config.php';

// Include reserved IDs constants
require_once 'inc/inc_reserved_ids.php';

// Load language file early (needed for $page_title)
require_once 'inc/inc_star51_lang.php';

// Get filter parameters from URL
$filter_category = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$filter_subcategory = isset($_GET['subcat']) ? intval($_GET['subcat']) : 0;
$current_page_num = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($current_page_num < 1) {
  $current_page_num = 1;
}

// Pagination settings
$articles_per_page = 9;
$offset = ($current_page_num - 1) * $articles_per_page;

// Build query based on filters
$where_conditions = [
  'c.is_active = 1 AND s.is_active = 1 AND a.is_active = 1',
  // Exclude system reserved categories (Extra backup + Info/News system)
  'c.id_category NOT IN (' . CATEGORY_EXTRA . ', ' . CATEGORY_INFO . ')',
];
$params = [];
$param_types = '';

if ($filter_subcategory > 0) {
  // Filter by subcategory (most specific)
  $where_conditions[] = 'a.id_subcategory = ?';
  $params[] = $filter_subcategory;
  $param_types .= 'i';
} elseif ($filter_category > 0) {
  // Filter by category (get all subcategories in this category)
  $where_conditions[] = 's.id_category = ?';
  $params[] = $filter_category;
  $param_types .= 'i';
}

$where_clause = implode(' AND ', $where_conditions);

// Count total articles for pagination
$count_query = "
  SELECT COUNT(a.id_article) AS total
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE " . $where_clause;

$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
  mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_articles = $count_row['total'];
$total_pages = ceil($total_articles / $articles_per_page);

// Fetch articles with pagination
$articles_query = "
  SELECT
    a.id_article,
    a.article_title,
    a.article_summary,
    a.item_collection,
    a.item_year,
    a.image_1,
    s.subcategory_name,
    c.category_name
  FROM ns_articles a
  LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  WHERE " . $where_clause . "
  ORDER BY a.id_article DESC
  LIMIT ? OFFSET ?
";

$articles_stmt = mysqli_prepare($conn, $articles_query);
$params[] = $articles_per_page;
$params[] = $offset;
$param_types .= 'ii';
mysqli_stmt_bind_param($articles_stmt, $param_types, ...$params);
mysqli_stmt_execute($articles_stmt);
$articles_result = mysqli_stmt_get_result($articles_stmt);

// Fetch all categories with their subcategories for sidebar
// EXCLUDE: Category 1 (Extra - system backup) and Category 2 (Info - system with News)
// Sidebar categories query - Exclude system reserved categories
$categories_query = "
  SELECT
    c.id_category,
    c.category_name,
    s.id_subcategory,
    s.subcategory_name
  FROM ns_categories c
  LEFT JOIN ns_subcategories s ON c.id_category = s.id_category AND s.is_active = 1
  WHERE c.is_active = 1
    AND c.id_category NOT IN (" . CATEGORY_EXTRA . ", " . CATEGORY_INFO . ")
  ORDER BY c.category_name, s.subcategory_name
";

$categories_result = mysqli_query($conn, $categories_query);

// Organize categories with subcategories
$categories = [];
while ($row = mysqli_fetch_assoc($categories_result)) {
  $cat_id = $row['id_category'];
  if (!isset($categories[$cat_id])) {
    $categories[$cat_id] = [
      'id' => $cat_id,
      'name' => $row['category_name'],
      'subcategories' => [],
    ];
  }
  if (!empty($row['id_subcategory'])) {
    $categories[$cat_id]['subcategories'][] = [
      'id' => $row['id_subcategory'],
      'name' => $row['subcategory_name'],
    ];
  }
}

// Page configuration
$page_title = __front('articles.page_title');
if ($filter_subcategory > 0 || $filter_category > 0) {
  // Update title based on filter
  mysqli_data_seek($articles_result, 0);
  if ($first_article = mysqli_fetch_assoc($articles_result)) {
    if ($filter_subcategory > 0) {
      $page_title = htmlspecialchars($first_article['subcategory_name']) . ' - Star51';
    } elseif ($filter_category > 0) {
      $page_title = htmlspecialchars($first_article['category_name']) . ' - Star51';
    }
    mysqli_data_seek($articles_result, 0);
  }
}
$page_description = __front('articles.page_description');
$current_page = 'articles';

// Include common HEAD
include 'inc/inc_head.php';

// Include common NAVBAR
include 'inc/inc_navbar.php';
?>

  <!-- ========== MAIN CONTENT SECTION ========== -->
  <!-- Products grid + category sidebar layout -->
  <main class="content-section py-5 star51-main-no-carousel" role="main" id="main-content">
    <div class="container">

      <!-- Content section header -->
      <header class="section-title text-center mb-5 rounded-4 p-4 star51-section-header">
        <h1 class="display-6 mb-3"><?= __front('articles.page_title') ?></h1>
        <p class="lead"><?= __front('articles.page_subtitle') ?></p>
      </header>

      <!-- Main layout: 9/3 column split -->
      <div class="row g-4">
        <!-- ========== CATEGORY NAVIGATION SIDEBAR ========== -->
        <aside class="col-lg-3">
          <div class="card star51-card">
            <div class="card-body">
              <h2 class="card-title">
                <a href="articles.php"
                   class="category-link <?= $filter_category == 0 && $filter_subcategory == 0 ? 'active' : '' ?>">
                  <?= __front('articles.all_articles') ?>
                </a>
              </h2>

              <nav aria-label="Category navigation">
                <ul class="category-navigation-list">
                  <?php foreach ($categories as $category): ?>
                    <li class="category-item <?= $filter_category == $category['id'] && $filter_subcategory == 0 ? 'active' : '' ?>">
                      <a href="articles.php?cat=<?= $category['id'] ?>" class="category-link">
                        <?= htmlspecialchars($category['name']) ?>
                      </a>
                      <?php if (!empty($category['subcategories'])): ?>
                        <ul class="subcategory-list">
                          <?php foreach ($category['subcategories'] as $subcategory): ?>
                            <li>
                              <a href="articles.php?cat=<?= $category['id'] ?>&subcat=<?= $subcategory['id'] ?>"
                                 class="subcategory-link <?= $filter_subcategory == $subcategory['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($subcategory['name']) ?>
                                <?php if ($filter_subcategory == $subcategory['id']): ?>
                                  <i class="bi bi-arrow-right ms-2"></i>
                                <?php endif; ?>
                              </a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </nav>
            </div>
          </div>
        </aside>

        <!-- Products grid (9 columns) -->
        <div class="col-lg-9">

          <?php if (mysqli_num_rows($articles_result) === 0): ?>
            <!-- Empty state -->
            <div class="alert alert-info" role="alert">
              <i class="bi bi-info-circle me-2"></i>
              <?= __front('articles.no_articles') ?>
              <a href="articles.php" class="alert-link"><?= __front('articles.view_all') ?></a>
            </div>
          <?php else: ?>

            <div class="row g-4">
              <?php while ($article = mysqli_fetch_assoc($articles_result)): ?>
                <div class="col-lg-4">
                  <?php include 'inc/inc_card_article.php'; ?>
                </div>
              <?php endwhile; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- ========== PAGINATION ========== -->
      <?php
      // Pagination parameters
      $base_url = 'articles.php';
      $query_params = [];
      if ($filter_category > 0) {
        $query_params['cat'] = $filter_category;
      }
      if ($filter_subcategory > 0) {
        $query_params['subcat'] = $filter_subcategory;
      }
      $aria_label = __front('articles.navigation');
      $total_items = $total_articles;
      $items_per_page = $articles_per_page;
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
