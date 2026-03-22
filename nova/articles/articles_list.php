<?php
// Nova Articles List - Table view with complete management
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('articles.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('articles.page.description');

// Pagination configuration
$articles_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $articles_per_page;

// Filter by subcategory (for "Varie" filter)
$filter_subcat = isset($_GET['subcat'])
  ? intval($_GET['subcat'])
  : 0;

// Build WHERE clause once, reuse for count and fetch
$where = '';
$bind_types = '';
$bind_params = [];

if ($filter_subcat > 0) {
  $where = ' WHERE a.id_subcategory = ?';
  $bind_types = 'i';
  $bind_params[] = $filter_subcat;
}

// Count total articles for pagination
$count_stmt = mysqli_prepare($conn,
  'SELECT COUNT(id_article) as total FROM ns_articles a' . $where);
if ($bind_params) {
  mysqli_stmt_bind_param($count_stmt, $bind_types, ...$bind_params);
}
mysqli_stmt_execute($count_stmt);
$total_articles = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total_articles / $articles_per_page);

// Fetch articles with complete data
$fetch_query =
  'SELECT
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
  LEFT JOIN ns_categories c ON s.id_category = c.id_category'
  . $where . ' ORDER BY a.id_article DESC LIMIT ? OFFSET ?';

$stmt = mysqli_prepare($conn, $fetch_query);
if ($filter_subcat > 0) {
  mysqli_stmt_bind_param($stmt, 'iii', $filter_subcat, $articles_per_page, $offset);
} else {
  mysqli_stmt_bind_param($stmt, 'ii', $articles_per_page, $offset);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
  error_log('Error fetching articles: ' . mysqli_error($conn));
  die(__admin('articles.err.load'));
}

// Fetch all data into array for reuse
$articles = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Count articles in "Varie" (id_subcategory = 1) for badge
$varie_stmt = mysqli_prepare($conn,
  'SELECT COUNT(id_article) as total FROM ns_articles WHERE id_subcategory = ?');
$varie_id = SUBCATEGORY_VARIE;
mysqli_stmt_bind_param($varie_stmt, 'i', $varie_id);
mysqli_stmt_execute($varie_stmt);
$varie_count = mysqli_fetch_assoc(mysqli_stmt_get_result($varie_stmt))['total'];
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
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title">
              <i class="bi bi-collection me-2"></i>
              <?= __admin('pages.articles_management') ?>
            </h1>
          </div>
          <div>
            <a href="articles_create.php" class="btn btn-primary nova-btn-action">
              <i class="bi bi-plus"></i><?= __admin('buttons.new_article') ?>
            </a>
          </div>
        </div>
      </header>
      <!-- END: PAGE HEADER SECTION -->

      <!-- SEARCH FORM - Top Right -->
      <div class="row mb-4">
        <div class="col-md-8"></div>
        <div class="col-md-4">
          <?php
          $form_action = 'articles_search.php';
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

      <!-- ARTICLES TABLE SECTION -->
      <section class="content-section mb-5">
        <?php if (count($articles) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('articles.list.title') ?>
                </h5>
                <p class="page-subtitle mb-2">
                  <?= __admin('articles.list.subtitle') ?>
                </p>

                <!-- FILTER TOGGLE BUTTONS (Only if orphan articles exist) -->
                <?php if ($varie_count > 0): ?>
                <div class="mb-3 d-flex gap-2">
                  <?php if ($filter_subcat == SUBCATEGORY_VARIE): ?>
                    <!-- We are in FILTERED view - "Varie" is ACTIVE -->
                    <a href="articles_list.php" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-grid-3x3-gap me-2"></i><?= __admin('labels.all_articles') ?>
                    </a>
                    <span class="btn btn-sm btn-primary">
                      <i class="bi bi-inbox me-2"></i><?= $varie_count ?> <?= __admin('labels.to_reorganize') ?>
                    </span>
                  <?php else: ?>
                    <!-- We are in NORMAL view - "Tutti" is ACTIVE -->
                    <span class="btn btn-sm btn-primary">
                      <i class="bi bi-grid-3x3-gap me-2"></i><?= __admin('labels.all_articles') ?>
                    </span>
                    <a href="articles_list.php?subcat=1" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-inbox me-2"></i><?= $varie_count ?> <?= __admin('labels.to_reorganize') ?>
                    </a>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
                <!-- END: FILTER TOGGLE BUTTONS -->

                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i>
                  <?= __admin('articles.list.reminder') ?>
                </div>

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
                      <?php foreach ($articles as $article): ?>
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
                              <?php
                              $parts = array_filter([
                                $article['item_year'] ?? null,
                                $article['item_collection'] ?? null
                              ]);
                              if ($parts): ?>
                                <small class="text-muted"><?= htmlspecialchars(implode(' • ', $parts)) ?></small>
                              <?php endif; ?>

                              <!-- Riga 4: Summary breve + link "continua →" -->
                              <small class="article-summary text-muted">
                                <?php if (!empty($article['article_summary'])):
                                  $summary = $article['article_summary'];
                                  echo htmlspecialchars(substr($summary, 0, 80));
                                  if (strlen($summary) > 80) echo '...';
                                ?><br>
                                <?php endif; ?>
                                <a href="articles_show.php?id=<?= $article['id_article'] ?>"
                                   class="text-decoration-none"><?= __admin('labels.continue') ?> →</a>
                              </small>
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
                <div class="border-top pt-3 mt-3">
                  <?php
                  // Setup pagination variables for include
                  $total_records = $total_articles;
                  $base_url = 'articles_list.php';
                  $extra_params = [];
                  if ($filter_subcat > 0) {
                    $extra_params['subcat'] = $filter_subcat;
                  }
                  include '../inc/inc_nova_pagination.php';
                  ?>
                </div>
                <!-- END: Pagination Footer -->

              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
          <!-- No Articles Found -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-inbox display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('empty.no_articles') ?></h3>
              <p class="text-muted mb-4">
                <?= __admin('empty.no_articles_desc') ?>
              </p>
              <a href="articles_create.php" class="btn nova-btn-primary">
                <i class="bi bi-plus-circle fs-2 me-2"></i><?= __admin('empty.create_first_article') ?>
              </a>
            </div>
          </div>
        <?php endif; ?>

      </section>
      <!-- END: ARTICLES TABLE SECTION -->

    </div>
  </main>
  <!-- END: MAIN CONTENT WRAPPER -->

  <!-- FOOTER -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
