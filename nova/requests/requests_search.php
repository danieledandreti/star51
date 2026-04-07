<?php
// Nova Requests Search - Search results with same layout as requests_list
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('requests.page.title_search') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('requests.page.desc_search');

// Search variables
$search_query = '';
$search_results = [];
$total_results = 0;
$error_message = '';
$success_message = '';

// Pagination configuration
$requests_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $requests_per_page;

// Search logic
if (isset($_GET['rs']) && !empty(trim($_GET['rs']))) {
  $search_query = trim($_GET['rs']);

  // Validate minimum 3 characters
  if (strlen($search_query) < 3) {
    $error_message = __admin('requests.search.min_chars');
  } else {
    // Split search query into words
    $words = explode(' ', $search_query);

    // Build WHERE clause for LIKE search
    // Search in: first_name, last_name, email, request_message
    $where_conditions = [];
    $params = [];
    $types = '';

    foreach ($words as $word) {
      $word = trim($word);
      if (!empty($word)) {
        $where_conditions[] =
          '(r.first_name LIKE ? OR r.last_name LIKE ? OR r.email LIKE ? OR r.request_message LIKE ?)';
        $search_param = '%' . $word . '%';
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ssss';
      }
    }

    if (!empty($where_conditions)) {
      $where_clause = implode(' AND ', $where_conditions);

      // Count total results for pagination
      // Using COUNT(r.id_request) for better performance (uses PK index)
      $query_count = "SELECT COUNT(r.id_request) as total FROM ns_requests r WHERE " . $where_clause;
      $stmt_count = mysqli_prepare($conn, $query_count);
      if ($stmt_count) {
        if (!empty($params)) {
          mysqli_stmt_bind_param($stmt_count, $types, ...$params);
        }
        mysqli_stmt_execute($stmt_count);
        $rs_count = mysqli_stmt_get_result($stmt_count);
        $total_results = mysqli_fetch_assoc($rs_count)['total'];
        mysqli_stmt_close($stmt_count);
      }

      // Calculate total pages
      $total_pages = ceil($total_results / $requests_per_page);

      // Fetch search results with pagination
      $query_search = "
        SELECT
          r.id_request,
          r.first_name,
          r.last_name,
          r.email,
          r.phone,
          r.request_message,
          r.request_status,
          r.request_date,
          r.is_active
        FROM ns_requests r
        WHERE " . $where_clause . "
        ORDER BY r.id_request DESC
        LIMIT ? OFFSET ?
      ";

      $stmt = mysqli_prepare($conn, $query_search);
      if ($stmt) {
        // Bind parameters for main query (search params + pagination)
        $main_params = array_merge($params, [$requests_per_page, $offset]);
        $main_types = $types . 'ii';
        mysqli_stmt_bind_param($stmt, $main_types, ...$main_params);
        mysqli_stmt_execute($stmt);
        $rs_search = mysqli_stmt_get_result($stmt);

        if ($rs_search) {
          $search_results = mysqli_fetch_all($rs_search, MYSQLI_ASSOC);
        }

        mysqli_stmt_close($stmt);
      }

      // Set success message with result count
      if ($total_results > 0) {
        $success_message = str_replace(
          ['{count}', '{query}'],
          [$total_results, htmlspecialchars($search_query)],
          __admin('requests.search.results_found')
        );
      } else {
        $error_message = str_replace(
          '{query}',
          htmlspecialchars($search_query),
          __admin('requests.search.no_results')
        );
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
          <i class="bi bi-search me-2"></i><?= __admin('requests.search.title') ?>
        </h1>
      </header>
      <!-- END: PAGE HEADER SECTION -->

      <!-- SEARCH FORM - Top Right -->
      <div class="row mb-4">
        <div class="col-md-8"></div>
        <div class="col-md-4">
          <?php
          $form_action = 'requests_search.php';
          include '../inc/inc_nova_search_requests.php';
          ?>
        </div>
      </div>
      <!-- END: SEARCH FORM -->

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['requests'];
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
                  <i class="bi bi-list-ul me-2"></i><?= __admin('requests.search.results_title') ?>
                </h5>
                <p class="page-subtitle mb-3">
                  <?= __admin('requests.search.results_subtitle') ?>
                </p>

              <div class="table-responsive">
                <table class="table table-hover nova-table nova-table-compact nova-table-accordion">
                <thead class="table-dark">
                  <tr>
                    <th scope="col"><?= __admin('requests.list.col_name') ?></th>
                    <th scope="col"><?= __admin('requests.list.col_message') ?></th>
                    <th scope="col"><?= __admin('requests.list.col_contact') ?></th>
                    <th scope="col" class="text-center"><?= __admin('requests.list.col_status') ?></th>
                    <th scope="col"><?= __admin('requests.list.col_date') ?></th>
                    <th scope="col" class="text-center"><?= __admin('requests.list.col_actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($search_results as $request): ?>
                    <tr>
                      <!-- Full Name -->
                      <td>
                        <strong><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></strong>
                      </td>

                      <!-- Message -->
                      <td>
                        <?php if (!empty($request['request_message'])): ?>
                          <span class="text-muted">
                            <?= htmlspecialchars(substr($request['request_message'], 0, 80)) ?>
                            <?php if (strlen($request['request_message']) > 80): ?>...<?php endif; ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted fst-italic"><?= __admin('requests.content.no_message') ?></span>
                        <?php endif; ?>
                      </td>

                      <!-- Contact -->
                      <td class="text-nowrap">
                        <div>
                          <a href="mailto:<?= htmlspecialchars($request['email']) ?>" class="text-decoration-none">
                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($request['email']) ?>
                          </a>
                        </div>
                        <?php if (!empty($request['phone'])): ?>
                          <div class="mt-1 text-muted">
                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($request['phone']) ?>
                          </div>
                        <?php endif; ?>
                      </td>

                      <!-- Status (Toggle Processed/Pending) -->
                      <td class="text-center">
                        <a href="requests_toggle.php?id=<?= $request['id_request'] ?>"
                           class="badge nova-badge-clickable <?= $request['is_active'] ? 'bg-success' : 'bg-warning' ?>"
                           title="<?= $request['is_active'] ? __admin('requests.status.click_pending') : __admin('requests.status.click_processed') ?>">
                          <i class="bi bi-<?= $request['is_active'] ? 'check-circle' : 'exclamation-circle' ?>"></i>
                          <?= $request['is_active'] ? __admin('requests.status.processed') : __admin('requests.status.pending') ?>
                        </a>
                      </td>

                      <!-- Date -->
                      <td>
                        <small class="text-muted">
                          <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($request['request_date'])) ?>
                        </small>
                      </td>

                      <!-- Actions -->
                      <td class="text-center">
                        <div class="d-inline-flex gap-2">
                          <!-- Toggle Accordion -->
                          <button type="button"
                                  class="btn btn-lg btn-outline-secondary collapsed"
                                  data-bs-toggle="collapse"
                                  data-bs-target="#request-<?= $request['id_request'] ?>"
                                  title="<?= __admin('requests.content.expand_collapse') ?>">
                            <i class="bi bi-chevron-down"></i>
                            <i class="bi bi-chevron-up"></i>
                          </button>

                          <!-- Delete -->
                          <a href="requests_delete.php?id=<?= $request['id_request'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                             class="btn btn-lg btn-outline-danger"
                             title="<?= __admin('buttons.delete') ?>"
                             onclick="return confirm('<?= __admin('requests.confirm.delete') ?>')">
                            <i class="bi bi-trash"></i>
                          </a>
                        </div>
                      </td>
                    </tr>

                    <!-- Accordion Row - Message Details -->
                    <tr class="collapse" id="request-<?= $request['id_request'] ?>">
                      <td colspan="6" class="p-0">
                        <div class="bg-light border-top border-bottom p-4">
                          <h6 class="text-muted mb-3">
                            <i class="bi bi-envelope-open me-2"></i><?= __admin('requests.content.full_message') ?>
                          </h6>
                          <?php if (!empty($request['request_message'])): ?>
                            <div class="bg-white p-3 rounded border nova-pre-wrap">
                              <?= htmlspecialchars(trim($request['request_message'])) ?>
                            </div>
                          <?php else: ?>
                            <p class="text-muted fst-italic"><?= __admin('requests.content.no_message_available') ?></p>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              </div>

              <!-- Pagination Footer -->
              <?php if ($total_results > $requests_per_page): ?>
              <div class="border-top pt-3 mt-3">
                <?php
                $total_records = $total_results;
                $base_url = 'requests_search.php';
                $extra_params = ['rs' => $search_query];
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
          <!-- No Results Found -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-search display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('requests.search.no_results_title') ?></h3>
              <p class="text-muted mb-4">
                <?= str_replace('{query}', htmlspecialchars($search_query), __admin('requests.search.no_results_desc')) ?>
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
