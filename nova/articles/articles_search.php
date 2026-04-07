<?php
// Nova Articles Search - Search results with same layout as articles_list
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('articles.page.title_search') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('articles.page.desc_search');

// Search variables
$search_query = '';
$search_results = [];
$total_results = 0;
$error_message = '';
$success_message = '';

// Pagination configuration
$articles_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $articles_per_page;

// Search logic
if (isset($_GET['as']) && !empty(trim($_GET['as']))) {
  $search_query = trim($_GET['as']);

  // Validate minimum 3 characters
  if (strlen($search_query) < 3) {
    $error_message = __admin('articles.search.min_chars');
  } else {
    // Split search query into words
    $words = explode(' ', $search_query);

    // Build WHERE clause for LIKE search
    // Search in: article_title, article_summary, article_content
    $where_conditions = [];
    $params = [];
    $types = '';

    foreach ($words as $word) {
      $word = trim($word);
      if (!empty($word)) {
        $where_conditions[] =
          '(a.article_title LIKE ? OR a.article_summary LIKE ? OR a.article_content LIKE ?)';
        $search_param = '%' . $word . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'sss';
      }
    }

    if (!empty($where_conditions)) {
      $where_clause = implode(' AND ', $where_conditions);

      // Count total results for pagination
      // Using COUNT(a.id_article) for better performance (uses PK index)
      $count_query =
        'SELECT COUNT(a.id_article) as total FROM ns_articles a WHERE ' . $where_clause;
      $count_stmt = mysqli_prepare($conn, $count_query);
      if ($count_stmt) {
        // Bind parameters for count query
        if (!empty($params)) {
          mysqli_stmt_bind_param($count_stmt, $types, ...$params);
        }
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        $total_results = mysqli_fetch_assoc($count_result)['total'];
        mysqli_stmt_close($count_stmt);
      }

      // Calculate total pages
      $total_pages = ceil($total_results / $articles_per_page);

      // Fetch search results with pagination
      $query = 'SELECT
          a.id_article,
          a.article_title,
          a.article_summary,
          a.item_collection,
          a.item_year,
          a.image_1,
          a.image_2,
          a.youtube_video,
          a.publish_date,
          a.show_publish_date,
          a.is_active,
          a.created_at,
          a.updated_at,
          s.subcategory_name,
          c.category_name
        FROM ns_articles a
        LEFT JOIN ns_subcategories s ON a.id_subcategory = s.id_subcategory
        LEFT JOIN ns_categories c ON s.id_category = c.id_category
        WHERE ' .
        $where_clause .
        '
        ORDER BY a.article_title ASC
        LIMIT ? OFFSET ?';

      $stmt = mysqli_prepare($conn, $query);
      if ($stmt) {
        // Bind parameters for main query (search params + pagination)
        $main_params = array_merge($params, [$articles_per_page, $offset]);
        $main_types = $types . 'ii';
        mysqli_stmt_bind_param($stmt, $main_types, ...$main_params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
          $search_results = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }

        mysqli_stmt_close($stmt);
      }

      // Set success message with result count
      if ($total_results > 0) {
        $success_message = str_replace(['{count}', '{query}'], [$total_results, htmlspecialchars($search_query)], __admin('articles.search.results_found'));
      } else {
        $error_message = str_replace('{query}', htmlspecialchars($search_query), __admin('articles.search.no_results'));
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
  <!-- Navigation -->
  <?php include '../inc/inc_nova_navigation.php'; ?>

  <!-- MAIN CONTENT WRAPPER -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- PAGE HEADER SECTION -->
      <header class="page-header mb-4">
        <h1 class="page-title">
          <i class="bi bi-search me-2"></i>
          <?= __admin('articles.search.title') ?>
        </h1>
      </header>
      <!-- END: PAGE HEADER SECTION -->

      <!-- SEARCH FORM - Top Right -->
      <div class="row mb-4">
        <div class="col-md-8"></div>
        <div class="col-md-4">
          <?php
          $form_action = 'articles_search.php';
          // $search_query already set above
          include '../inc/inc_nova_search_articles.php';
          ?>
        </div>
      </div>
      <!-- END: SEARCH FORM -->

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['articles', 'nova'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- SEARCH ERROR MESSAGES -->
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?= $error_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- SUCCESS MESSAGE (Result Count) -->
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i>
          <?= $success_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- SEARCH RESULTS TABLE SECTION -->
      <section class="content-section mb-5">
        <?php if (count($search_results) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('articles.search.results_title') ?>
                </h5>
                <p class="page-subtitle mb-3">
                  <?= __admin('articles.search.results_subtitle') ?>
                </p>

                <div class="table-responsive">
                  <table class="table table-hover nova-table nova-table-compact">
                    <thead class="table-dark">
                      <tr>
                        <th scope="col" class="nova-col-w100"><?= __admin('articles.list.col_image') ?></th>
                        <th scope="col"><?= __admin('articles.list.col_article') ?></th>
                        <th scope="col" class="text-center d-none d-xl-table-cell nova-col-w120"><?= __admin('articles.list.col_media') ?></th>
                        <th scope="col" class="d-none d-lg-table-cell nova-col-w130"><?= __admin('articles.list.col_publish_date') ?></th>
                        <th scope="col" class="text-center nova-col-w100"><?= __admin('articles.list.col_status') ?></th>
                        <th scope="col" class="d-none d-xl-table-cell nova-col-w180"><?= __admin('articles.list.col_created_by') ?></th>
                        <th scope="col" class="text-center nova-col-w120"><?= __admin('articles.list.col_actions') ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($search_results as $article): ?>
                        <tr>
                          <!-- THUMBNAIL (image_1) -->
                          <td>
                            <?php if (!empty($article['image_1'])): ?>
                              <img src="../../file_db_min/<?= $article['image_1'] ?>"
                                   alt="<?= htmlspecialchars($article['article_title']) ?>"
                                   class="article-thumb-main"
                                   title="<?= htmlspecialchars($article['article_title']) ?>">
                            <?php else: ?>
                              <div class="image-placeholder-thumbnail">
                                <i class="bi bi-image"></i>
                              </div>
                            <?php endif; ?>
                          </td>

                          <!-- ARTICOLO (Mini-scheda completa: Cat-Subcat, Titolo, Anno•Collection, Summary + link) -->
                          <td>
                            <div class="d-flex flex-column">
                              <!-- Riga 1: [Categoria] - Sottocategoria -->
                              <small class="text-muted">
                                <?php if ($article['category_name']): ?>
                                  <strong>[<?= htmlspecialchars($article['category_name']) ?>]</strong>
                                <?php else: ?>
                                  <strong>[<?= __admin('labels.deleted') ?>]</strong>
                                <?php endif; ?>
                                - <?= htmlspecialchars($article['subcategory_name'] ?? __admin('labels.miscellaneous')) ?>
                              </small>

                              <!-- Riga 2: Titolo -->
                              <strong class="article-title">
                                <?= htmlspecialchars($article['article_title']) ?>
                              </strong>

                              <!-- Riga 3: Anno • Collection -->
                              <?php if (!empty($article['item_year']) || !empty($article['item_collection'])): ?>
                                <small class="text-muted">
                                  <?php if (!empty($article['item_year'])): ?>
                                    <?= htmlspecialchars($article['item_year']) ?>
                                  <?php endif; ?>
                                  <?php if (!empty($article['item_year']) && !empty($article['item_collection'])): ?>
                                    •
                                  <?php endif; ?>
                                  <?php if (!empty($article['item_collection'])): ?>
                                    <?= htmlspecialchars($article['item_collection']) ?>
                                  <?php endif; ?>
                                </small>
                              <?php endif; ?>

                              <!-- Riga 4: Summary breve + link "continua →" A CAPO (sempre presente) -->
                              <?php if (!empty($article['article_summary'])): ?>
                                <small class="article-summary text-muted">
                                  <?php
                                  $summary = $article['article_summary'];
                                  $truncated = substr($summary, 0, 80);
                                  echo htmlspecialchars($truncated);
                                  // Link SEMPRE presente (anche se summary < 80 caratteri)
                                  if (strlen($summary) > 80): ?>
                                    ...
                                  <?php endif; ?>
                                  <br>
                                  <a href="articles_show.php?id=<?= $article['id_article'] ?>"
                                        class="text-decoration-none"><?= __admin('labels.continue') ?> →</a>
                                </small>
                              <?php else: ?>
                                <!-- Se nessun summary, solo link -->
                                <small class="article-summary text-muted">
                                  <a href="articles_show.php?id=<?= $article['id_article'] ?>"
                                        class="text-decoration-none"><?= __admin('labels.continue') ?> →</a>
                                </small>
                              <?php endif; ?>
                            </div>
                          </td>

                          <!-- MEDIA (image_2 thumbnail + YouTube icon) (Desktop only - xl+) -->
                          <td class="thumb-cell d-none d-xl-table-cell">
                            <?php if (!empty($article['image_2'])): ?>
                              <img src="../../file_db_min/<?= $article['image_2'] ?>"
                                   alt="<?= htmlspecialchars($article['article_title']) ?>"
                                   class="article-thumb-main"
                                   title="<?= __admin('articles.show.image_2') ?>">
                            <?php else: ?>
                              <div class="image-placeholder-thumbnail">
                                <i class="bi bi-image"></i>
                              </div>
                            <?php endif; ?>
                            <?php if (!empty($article['youtube_video'])): ?>
                              <i class="bi bi-youtube text-danger fs-5" title="<?= __admin('articles.show.youtube_video') ?>"></i>
                            <?php endif; ?>
                          </td>

                          <!-- DATA PUBBLICAZIONE + Toggle ON/OFF (Desktop only - lg+) -->
                          <td class="d-none d-lg-table-cell">
                            <div class="d-flex flex-column gap-1">
                              <!-- Publish Date -->
                              <small class="text-muted text-center">
                                <?php if (!empty($article['publish_date'])): ?>
                                  <?= date(NOVA_DATE_FORMAT, strtotime($article['publish_date'])) ?>
                                <?php else: ?>
                                  <em><?= __admin('labels.not_set') ?></em>
                                <?php endif; ?>
                              </small>

                              <!-- Show Publish Date Toggle -->
                              <a href="articles_toggle_show_date.php?id=<?= $article['id_article'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                 class="badge nova-badge-clickable <?= $article['show_publish_date'] ? 'bg-info text-white' : 'badge-outline-muted' ?>"
                                 title="<?= $article['show_publish_date'] ? __admin('articles.status.date_visible') : __admin('articles.status.date_hidden') ?>">
                                <i class="bi bi-calendar-<?= $article['show_publish_date'] ? 'check' : 'x' ?>"></i>
                                <?= $article['show_publish_date'] ? __admin('articles.status.date_on') : __admin('articles.status.date_off') ?>
                              </a>
                            </div>
                          </td>

                          <!-- STATUS - Clickable Toggle (Active) -->
                          <td class="text-center">
                            <div class="d-flex flex-column gap-1">
                              <!-- Placeholder (Solo: no featured) -->
                              <span class="badge badge-outline-muted nova-badge-placeholder">
                                <i class="bi bi-dash-lg"></i>
                              </span>
                              <!-- Active/Inactive Toggle -->
                              <a href="articles_toggle.php?id=<?= $article['id_article'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                 class="badge nova-badge-clickable <?= $article['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                                 title="<?= $article['is_active'] ? __admin('articles.status.click_deactivate') : __admin('articles.status.click_activate') ?>">
                                <i class="bi bi-<?= $article['is_active'] ? 'check-circle' : 'x-circle' ?>"></i>
                                <?= $article['is_active'] ? __admin('articles.status.active') : __admin('articles.status.inactive') ?>
                              </a>
                            </div>
                          </td>

                          <!-- Created by (Desktop only - xl+) -->
                          <td class="d-none d-xl-table-cell">
                            <small class="text-muted">
                              <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($admin_full_name) ?>
                              <br>
                              <?= __admin('labels.created_at') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($article['created_at'])) ?>
                              <br>
                              <?php if ($article['updated_at'] && $article['updated_at'] != $article['created_at']): ?>
                                <?= __admin('labels.modified_at') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($article['updated_at'])) ?>
                              <?php endif; ?>
                            </small>
                          </td>

                          <!-- AZIONI (Edit + Delete only - NO Show button) -->
                          <td class="text-center">
                            <div class="d-inline-flex gap-2">
                              <!-- Edit -->
                              <a href="articles_edit.php?id=<?= $article['id_article'] ?>"
                                 class="btn btn-lg btn-outline-success"
                                 title="<?= __admin('buttons.edit') ?>">
                                <i class="bi bi-pencil"></i>
                              </a>

                              <!-- Delete -->
                              <a href="articles_delete.php?id=<?= $article['id_article'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                 class="btn btn-lg btn-outline-danger"
                                 title="<?= __admin('buttons.delete') ?>"
                                 onclick="return confirm('<?= __admin('articles.confirm.delete') ?>')">
                                <i class="bi bi-trash"></i>
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination Footer -->
                <?php if ($total_results > $articles_per_page): ?>
                <div class="border-top pt-3 mt-3">
                  <?php
                  // Setup pagination variables for include
                  $total_records = $total_results;
                  $base_url = 'articles_search.php';
                  $extra_params = ['as' => $search_query];
                  include '../inc/inc_nova_pagination.php';
                  ?>
                </div>
                <?php endif; ?>
                <!-- END: Pagination Footer -->

              </div>
            </div>
          </div>
        </div>
        <?php elseif (!empty($search_query) && strlen($search_query) >= 3): ?>
          <!-- No Results Found (only if search was performed) -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-search display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('articles.search.no_results_title') ?></h3>
              <p class="text-muted mb-4">
                <?= str_replace('{query}', htmlspecialchars($search_query), __admin('articles.search.no_results_desc')) ?>
              </p>
            </div>
          </div>
        <?php endif; ?>

      </section>
      <!-- END: SEARCH RESULTS TABLE SECTION -->

    </div>
  </main>
  <!-- END: MAIN CONTENT WRAPPER -->

  <!-- FOOTER -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
