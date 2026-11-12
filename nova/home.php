<?php
// Nova Home Dashboard - Clean version with includes
// Nova Session Management Include - handles session validation and database connection
include 'inc/inc_nova_session.php';

// Initialize modification dates for dashboard cards
$mod_categories = $mod_subcategories = $mod_articles = $mod_misc_articles = __admin('labels.no_date');
$mod_requests = __admin('labels.no_date');

// Initialize counters for dashboard cards
$num_categories = $num_subcategories = $num_articles = $num_misc_articles = 0;
$num_requests = 0;

// Count Categories - Optimized query with COUNT + MAX
$query_categories = "
  SELECT COUNT(id_category) AS total, MAX(updated_at) AS last_updated
  FROM ns_categories
  WHERE is_active = 1
";
$rs_categories = mysqli_query($conn, $query_categories);
if ($rs_categories) {
  $row = mysqli_fetch_assoc($rs_categories);
  $num_categories = $row['total'];
  if ($row['last_updated']) {
    $mod_categories = date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($row['last_updated']));
  }
}

// Count Subcategories - Optimized query with COUNT + MAX
$query_subcategories = "
  SELECT COUNT(id_subcategory) AS total, MAX(updated_at) AS last_updated
  FROM ns_subcategories
  WHERE is_active = 1
";
$rs_subcategories = mysqli_query($conn, $query_subcategories);
if ($rs_subcategories) {
  $row = mysqli_fetch_assoc($rs_subcategories);
  $num_subcategories = $row['total'];
  if ($row['last_updated']) {
    $mod_subcategories = date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($row['last_updated']));
  }
}

// Count Articles - Optimized query with COUNT + MAX
$query_articles = "
  SELECT COUNT(id_article) AS total, MAX(updated_at) AS last_updated
  FROM ns_articles
  WHERE is_active = 1
";
$rs_articles = mysqli_query($conn, $query_articles);
if ($rs_articles) {
  $row = mysqli_fetch_assoc($rs_articles);
  $num_articles = $row['total'];
  if ($row['last_updated']) {
    $mod_articles = date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($row['last_updated']));
  }
}

// Count Articles in "Varie" (id_subcategory = 1) - Articles to reorganize
$query_misc_articles = "
  SELECT COUNT(id_article) AS total, MAX(updated_at) AS last_updated
  FROM ns_articles
  WHERE id_subcategory = " . SUBCATEGORY_VARIE . "
    AND is_active = 1
";
$rs_misc_articles = mysqli_query($conn, $query_misc_articles);
if ($rs_misc_articles) {
  $row = mysqli_fetch_assoc($rs_misc_articles);
  $num_misc_articles = $row['total'];
  if ($row['last_updated']) {
    $mod_misc_articles = date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($row['last_updated']));
  }
}

// Count Requests - Always visible to all levels
$query_requests = "
  SELECT COUNT(id_request) AS total, MAX(request_date) AS last_updated
  FROM ns_requests
  WHERE is_active = 1
";
$rs_requests = mysqli_query($conn, $query_requests);
if ($rs_requests) {
  $row = mysqli_fetch_assoc($rs_requests);
  $num_requests = $row['total'];
  if ($row['last_updated']) {
    $mod_requests = date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($row['last_updated']));
  }
}

// Set page variables for includes
$page_title = __admin('pages.dashboard_title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('pages.dashboard_overview') . ' ' . $nova_settings['admin_name'];
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
    <?php include 'inc/inc_nova_head.php'; ?>
</head>
<body class="nova-layout">
  <?php include 'inc/inc_nova_navigation.php'; ?>

  <!-- Main Content Start -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Page Header -->
      <header class="page-header">
        <h1 class="page-title"><?= __admin('pages.dashboard_overview') ?></h1>
        <p class="universal-subtitle">
          <i class="bi bi-collection me-1"></i>
          <?= __admin('dashboard.universal_collection') ?>
        </p>
        <p class="page-subtitle">
          <?= __admin('pages.dashboard_subtitle') ?> <strong><?= $nova_settings['site_name'] ?></strong>. <?= __admin('pages.dashboard_intro') ?>
        </p>
      </header>

      <!-- Search Forms Section -->
      <section class="nova-section mb-5">
        <div class="row g-4">
          <!-- Articles Search Card -->
          <div class="col-md-6">
            <div class="nova-card h-100">
              <div class="nova-card-body">
                <h5 class="card-title mb-3"><?= __admin('dashboard.search_articles') ?></h5>
                <?php
                $form_action = 'articles/articles_search.php';
                include 'inc/inc_nova_search_articles.php';
                ?>
              </div>
            </div>
          </div>

          <!-- Requests Search Card - All levels -->
          <div class="col-md-6">
            <div class="nova-card h-100">
              <div class="nova-card-body">
                <h5 class="card-title mb-3"><?= __admin('dashboard.search_requests') ?></h5>
                <?php
                $form_action = 'requests/requests_search.php';
                include 'inc/inc_nova_search_requests.php';
                ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <?php
      $overview_rows = [
        ['tag', 'nav.categories', $num_categories, $mod_categories, 'cat/cat_list.php'],
        ['tags', 'nav.subcategories', $num_subcategories, $mod_subcategories, 'subcat/subcat_list.php'],
        ['card-heading', 'nav.articles', $num_articles, $mod_articles, 'articles/articles_list.php'],
        ['envelope', 'nav.requests', $num_requests, $mod_requests, 'requests/requests_list.php'],
      ];
      if ($num_misc_articles > 0) {
        $overview_rows[] = ['inbox', 'dashboard.overview_list.articles_misc', $num_misc_articles, $mod_misc_articles, 'articles/articles_list.php?subcat=' . SUBCATEGORY_VARIE];
      }
      ?>
      <!-- Statistics Section -->
      <section class="nova-section" aria-labelledby="nova-overview-title">
        <div class="nova-card">
          <div class="nova-card-body">
            <h2 class="h5 mb-3" id="nova-overview-title"><?= __admin('dashboard.overview_list.title') ?></h2>
            <ul class="nova-overview-grid list-unstyled mb-0">
              <?php foreach ($overview_rows as [$icon, $label, $count, $updated, $url]): ?>
                <li class="nova-overview-row border-bottom py-2">
                  <div class="d-flex gap-2 align-items-baseline">
                    <i class="bi bi-<?= $icon ?> nova-overview-icon" aria-hidden="true"></i>
                    <a class="nova-overview-link" href="<?= htmlspecialchars($url) ?>"><?= __admin($label) ?>: <strong><?= (int) $count ?></strong></a>
                  </div>
                  <span class="small text-muted"><?= __admin('dashboard.updated_at') ?>: <?= htmlspecialchars($updated) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </section>

    </div>
  </main>

  <?php include 'inc/inc_nova_footer.php'; ?>

</body>
</html>
