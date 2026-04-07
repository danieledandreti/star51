<?php
// Nova Subcategories List - Display subcategories in table format
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('subcategories.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('subcategories.page.description');

// Pagination configuration
$subcategories_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $subcategories_per_page;

// Count total subcategories for pagination
// Using COUNT(id_subcategory) for better performance (uses PK index)
$query_count = "
  SELECT COUNT(id_subcategory) as total
  FROM ns_subcategories
";
$rs_count = mysqli_query($conn, $query_count);
$total_subcategories = mysqli_fetch_assoc($rs_count)['total'];
$total_pages = ceil($total_subcategories / $subcategories_per_page);

// Query to get subcategories with parent category and admin info
$query_subcats = "
  SELECT
    s.id_subcategory,
    s.subcategory_name,
    s.subcategory_description,
    s.is_active,
    s.created_at,
    s.updated_at,
    c.id_category,
    c.category_name,
    c.is_active as category_active
  FROM ns_subcategories s
  LEFT JOIN ns_categories c ON s.id_category = c.id_category
  ORDER BY s.id_subcategory DESC
  LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $query_subcats);
mysqli_stmt_bind_param($stmt, 'ii', $subcategories_per_page, $offset);
mysqli_stmt_execute($stmt);
$rs_subcats = mysqli_stmt_get_result($stmt);

if (!$rs_subcats) {
  error_log("Error fetching subcategories: " . mysqli_error($conn));
  die(__admin('subcategories.err.load'));
}

// Fetch all data into array for reuse
$subcategories = mysqli_fetch_all($rs_subcats, MYSQLI_ASSOC);
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
            <h1 class="page-title"><i class="bi bi-tags me-2"></i><?= __admin('subcategories.page.title') ?></h1>
          </div>
          <div>
            <!-- Action Button -->
            <a href="subcat_create.php" class="btn btn-primary nova-btn-action">
              <i class="bi bi-plus"></i><?= __admin('subcategories.buttons.new_subcategory') ?>
            </a>
          </div>
        </div>
      </header>
      <!-- END: PAGE HEADER SECTION -->

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['subcat'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- SUBCATEGORIES TABLE SECTION -->
      <section class="content-section mb-5">
        <?php if (count($subcategories) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('subcategories.list.title') ?>
                </h5>
                <p class="page-subtitle mb-2"><?= __admin('subcategories.list.subtitle') ?>
                </p>
                <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i><?= __admin('subcategories.list.reminder') ?>
                </div>
              <div class="table-responsive">
                <table class="table table-hover nova-table nova-table-compact">
                <thead class="table-dark">
                  <tr>
                    <th scope="col"><?= __admin('subcategories.list.col_catsubcat') ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?= __admin('subcategories.list.col_description') ?></th>
                    <th scope="col" class="text-center"><?= __admin('subcategories.list.col_status') ?></th>
                    <th scope="col" class="d-none d-lg-table-cell"><?= __admin('subcategories.list.col_created_by') ?></th>
                    <th scope="col" class="text-center"><?= __admin('subcategories.list.col_actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subcategories as $subcat): ?>
                    <tr>
                      <!-- [Cat.] - Subcat -->
                      <td>
                        <div class="d-flex flex-column">
                          <!-- Category name in bold with description style -->
                          <small class="text-muted">
                            <?php if ($subcat['category_name']): ?>
                              <strong>[<?= htmlspecialchars($subcat['category_name']) ?>]</strong>
                            <?php else: ?>
                              <strong>[<?= __admin('subcategories.list.category_deleted') ?>]</strong>
                            <?php endif; ?>
                          </small>
                          <!-- Subcategory name with description style -->
                          <small class="text-muted">
                            <?= htmlspecialchars($subcat['subcategory_name']) ?>
                          </small>
                          <!-- Description on mobile only -->
                          <small class="text-muted d-md-none">
                            <?= htmlspecialchars(substr($subcat['subcategory_description'] ?? '', 0, 50)) ?>
                            <?php if (strlen($subcat['subcategory_description'] ?? '') > 50): ?>...<?php endif; ?>
                          </small>
                        </div>
                      </td>

                      <!-- Description (Desktop only) -->
                      <td class="d-none d-md-table-cell">
                        <small class="text-muted">
                          <?= htmlspecialchars(substr($subcat['subcategory_description'] ?? '', 0, 100)) ?>
                          <?php if (strlen($subcat['subcategory_description'] ?? '') > 100): ?>...<?php endif; ?>
                        </small>
                      </td>

                      <!-- Status - Clickable Toggle -->
                      <td class="text-center">
                        <a href="subcat_toggle.php?id=<?= $subcat['id_subcategory'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                           class="badge nova-badge-clickable <?= $subcat['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                           title="<?= $subcat['is_active'] ? __admin('categories.status.click_deactivate') : __admin('categories.status.click_activate') ?>"
                           <?= $subcat['is_active'] ? "onclick=\"return confirm('" . __admin('subcategories.confirm.deactivate') . "')\"" : '' ?>>
                          <i class="bi bi-<?= $subcat['is_active'] ? 'check-circle' : 'x-circle' ?>"></i>
                          <?= $subcat['is_active'] ? __admin('categories.status.active') : __admin('categories.status.inactive') ?>
                        </a>
                      </td>

                      <!-- Created By (Desktop only) -->
                      <td class="d-none d-lg-table-cell">
                        <small class="text-muted">
                          <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($admin_full_name) ?>
                          <br>
                          <?= __admin('categories.list.created_at') ?> <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($subcat['created_at'])) ?>
                          <br>
                          <?php if ($subcat['updated_at'] && $subcat['updated_at'] != $subcat['created_at']): ?>
                            <?= __admin('categories.list.updated_at') ?> <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($subcat['updated_at'])) ?>
                          <?php endif; ?>
                        </small>
                      </td>

                      <!-- Actions -->
                      <td class="text-center">
                        <div class="d-inline-flex gap-2">
                          <!-- Edit -->
                          <a href="subcat_edit.php?id=<?= $subcat['id_subcategory'] ?>"
                             class="btn btn-lg btn-outline-success" title="<?= __admin('buttons.edit') ?>">
                            <i class="bi bi-pencil"></i>
                          </a>

                          <!-- Delete (locked for system subcategories) -->
                          <?php if ($subcat['id_subcategory'] > 2): ?>
                            <a href="subcat_delete.php?id=<?= $subcat['id_subcategory'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                               class="btn btn-lg btn-outline-danger" title="<?= __admin('buttons.delete') ?>"
                               onclick="return confirm('<?= __admin('subcategories.confirm.delete') ?>')">
                              <i class="bi bi-trash"></i>
                            </a>
                          <?php else: ?>
                            <div class="btn btn-lg btn-outline-secondary disabled">
                              <i class="bi bi-lock"></i>
                            </div>
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
                $total_records = $total_subcategories; // Pass total records for info display
                $base_url = 'subcat_list.php';
                $extra_params = []; // No extra parameters
                include '../inc/inc_nova_pagination.php';
                ?>
              </div>
              <!-- END: Pagination Footer -->

            </div>
          </div>
        </div>
        </div>
        <?php else: ?>
          <!-- No Subcategories Found -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-folder2 display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('empty.no_subcategories') ?></h3>
              <p class="text-muted mb-4"><?= __admin('empty.no_subcategories_desc') ?></p>
              <a href="subcat_create.php" class="btn nova-btn-primary">
                <i class="bi bi-plus-circle fs-2 me-2"></i><?= __admin('empty.create_first_subcategory') ?>
              </a>
            </div>
          </div>
        <?php endif; ?>

      </section>
      <!-- END: SUBCATEGORIES TABLE SECTION -->

    </div>
  </main>
  <!-- END: MAIN CONTENT WRAPPER -->

  <!-- FOOTER -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
