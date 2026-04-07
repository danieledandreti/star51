<?php
// Nova Requests List - User requests management
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('requests.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('requests.page.description');

// Pagination configuration
$requests_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $requests_per_page;

// Count total requests for pagination
// Using COUNT(id_request) for better performance (uses PK index)
$query_count = "SELECT COUNT(id_request) as total FROM ns_requests";
$rs_count = mysqli_query($conn, $query_count);
$total_requests = mysqli_fetch_assoc($rs_count)['total'];
$total_pages = ceil($total_requests / $requests_per_page);

// Query to get requests data
$query_requests = "
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
  ORDER BY r.id_request DESC
  LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $query_requests);
mysqli_stmt_bind_param($stmt, 'ii', $requests_per_page, $offset);
mysqli_stmt_execute($stmt);
$rs_requests = mysqli_stmt_get_result($stmt);

if (!$rs_requests) {
  error_log("Error fetching requests: " . mysqli_error($conn));
  die(__admin('requests.err.load'));
}

// Fetch all data into array for reuse
$requests = mysqli_fetch_all($rs_requests, MYSQLI_ASSOC);

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
            <h1 class="page-title"><i class="bi bi-envelope me-2"></i><?= __admin('requests.page.title') ?></h1>
          </div>
          <div></div>
        </div>
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

      <!-- REQUESTS TABLE SECTION -->
      <section class="content-section mb-5">
        <?php if (count($requests) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('requests.list.title') ?>
                </h5>
                <p class="page-subtitle mb-2"><?= __admin('requests.list.subtitle') ?></p>
                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i><?= __admin('requests.list.hint') ?>
                </div>
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
                  <?php foreach ($requests as $request): ?>
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
                        <a href="requests_toggle.php?id=<?= $request['id_request'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
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
                      <td class="text-center" onclick="event.stopPropagation();">
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
              <div class="border-top pt-3 mt-3">
                <?php
                // Setup pagination variables for include
                $total_records = $total_requests;
                $base_url = 'requests_list.php';
                $extra_params = [];
                include '../inc/inc_nova_pagination.php';
                ?>
              </div>
              <!-- END: Pagination Footer -->

            </div>
          </div>
        </div>
        </div>
        <?php else: ?>
          <!-- No Requests Found -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-envelope display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('requests.empty.title') ?></h3>
              <p class="text-muted mb-4"><?= __admin('requests.empty.desc') ?></p>
            </div>
          </div>
        <?php endif; ?>

      </section>
      <!-- END: REQUESTS TABLE SECTION -->

    </div>
  </main>
  <!-- END: MAIN CONTENT WRAPPER -->

  <!-- FOOTER -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
