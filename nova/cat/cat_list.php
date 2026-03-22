<?php
// Nova Categories List - Categories management
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('categories.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('categories.page.description');

// Pagination configuration
$categories_per_page = NOVA_RECORDS_PER_PAGE;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $categories_per_page;

// Count total categories for pagination
$query_count = "
  SELECT COUNT(id_category) as total
  FROM ns_categories
";
$rs_count = mysqli_query($conn, $query_count);
$total_categories = mysqli_fetch_assoc($rs_count)['total'];
$total_pages = ceil($total_categories / $categories_per_page);

// Query to get categories with admin info
$query_categories = "
  SELECT
    c.id_category,
    c.category_name,
    c.category_description,
    c.is_active,
    c.created_at,
    c.updated_at
  FROM ns_categories c
  ORDER BY c.id_category DESC
  LIMIT ? OFFSET ?
";
$stmt = mysqli_prepare($conn, $query_categories);
mysqli_stmt_bind_param($stmt, 'ii', $categories_per_page, $offset);
mysqli_stmt_execute($stmt);
$rs_categories = mysqli_stmt_get_result($stmt);

if (!$rs_categories) {
  error_log("Error fetching categories: " . mysqli_error($conn));
  die(__admin('categories.err.load'));
}

// Fetch all data into array for reuse
$categories = mysqli_fetch_all($rs_categories, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $nova_lang_code ?>">
<head>
  <?php include '../inc/inc_nova_head.php'; ?>
</head>

<body class="nova-layout">
  <!-- Navigation -->
  <?php include '../inc/inc_nova_navigation.php'; ?>

  <!-- Main Content -->
  <main class="nova-main-content" role="main" id="main-content">
    <div class="container-nova py-4">

      <!-- Page Header -->
      <header class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title"><i class="bi bi-tag me-2"></i><?= __admin('categories.page.title') ?></h1>
          </div>
          <div>
            <a href="cat_create.php" class="btn btn-primary nova-btn-action">
              <i class="bi bi-plus"></i><?= __admin('categories.buttons.new_category') ?>
            </a>
          </div>
        </div>
      </header>

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['cat'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- Categories Table Section -->
      <section class="content-section mb-5">
        <?php if (count($categories) > 0): ?>
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('categories.list.title') ?>
                </h5>
                <p class="page-subtitle mb-2"><?= __admin('categories.list.subtitle') ?></p>
                <div class="alert alert-info mb-3">
                  <i class="bi bi-info-circle me-2"></i><?= __admin('categories.list.reminder') ?>
                </div>
              <div class="table-responsive">
                <table class="table table-hover nova-table nova-table-compact">
                <thead class="table-dark">
                  <tr>
                    <th scope="col"><?= __admin('categories.list.col_category') ?></th>
                    <th scope="col" class="d-none d-md-table-cell"><?= __admin('categories.list.col_description') ?></th>
                    <th scope="col" class="text-center"><?= __admin('categories.list.col_status') ?></th>
                    <th scope="col" class="d-none d-lg-table-cell"><?= __admin('categories.list.col_created_by') ?></th>
                    <th scope="col" class="text-center"><?= __admin('categories.list.col_actions') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $category): ?>
                    <tr>
                      <!-- Category Name -->
                      <td>
                        <div class="d-flex flex-column">
                          <?= htmlspecialchars($category['category_name']) ?>
                          <small class="text-muted d-md-none">
                            <?= htmlspecialchars(substr($category['category_description'] ?? '', 0, 50)) ?>
                            <?php if (strlen($category['category_description'] ?? '') > 50): ?>...<?php endif; ?>
                          </small>
                        </div>
                      </td>

                      <!-- Description (Desktop only) -->
                      <td class="d-none d-md-table-cell">
                        <small class="text-muted">
                          <?= htmlspecialchars(substr($category['category_description'] ?? '', 0, 100)) ?>
                          <?php if (strlen($category['category_description'] ?? '') > 100): ?>...<?php endif; ?>
                        </small>
                      </td>

                      <!-- Status - Clickable Toggle -->
                      <td class="text-center">
                        <a href="cat_toggle.php?id=<?= $category['id_category'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                           class="badge nova-badge-clickable <?= $category['is_active'] ? 'bg-success' : 'bg-secondary' ?>"
                           title="<?= $category['is_active'] ? __admin('categories.status.click_deactivate') : __admin('categories.status.click_activate') ?>"
                           <?= $category['is_active'] ? "onclick=\"return confirm('" . __admin('categories.confirm.deactivate') . "')\"" : '' ?>>
                          <i class="bi bi-<?= $category['is_active'] ? 'check-circle' : 'x-circle' ?>"></i>
                          <?= $category['is_active'] ? __admin('categories.status.active') : __admin('categories.status.inactive') ?>
                        </a>
                      </td>

                      <!-- Created By (Desktop only) -->
                      <td class="d-none d-lg-table-cell">
                        <small class="text-muted">
                          <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($admin_full_name) ?>
                          <br>
                          <?= __admin('categories.list.created_at') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($category['created_at'])) ?>
                          <br>
                          <?php if ($category['updated_at'] && $category['updated_at'] != $category['created_at']): ?>
                            <?= __admin('categories.list.updated_at') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($category['updated_at'])) ?>
                          <?php endif; ?>
                        </small>
                      </td>

                      <!-- Actions -->
                      <td class="text-center">
                        <div class="d-inline-flex gap-2">
                          <!-- Edit -->
                          <a href="cat_edit.php?id=<?= $category['id_category'] ?>"
                             class="btn btn-lg btn-outline-success" title="<?= __admin('categories.buttons.edit') ?>">
                            <i class="bi bi-pencil"></i>
                          </a>

                          <!-- Delete (locked for system categories) -->
                          <?php if ($category['id_category'] > 2): ?>
                            <a href="cat_delete.php?id=<?= $category['id_category'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                               class="btn btn-lg btn-outline-danger" title="<?= __admin('categories.buttons.delete') ?>"
                               onclick="return confirm('<?= __admin('categories.confirm.delete') ?>')">
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
                $total_records = $total_categories;
                $base_url = 'cat_list.php';
                $extra_params = [];
                include '../inc/inc_nova_pagination.php';
                ?>
              </div>

            </div>
          </div>
        </div>
        </div>
        <?php else: ?>
          <!-- No Categories Found -->
          <div class="card nova-card text-center py-5">
            <div class="card-body">
              <i class="bi bi-folder display-1 text-muted mb-3"></i>
              <h3 class="text-muted"><?= __admin('categories.list.empty_title') ?></h3>
              <p class="text-muted mb-4"><?= __admin('categories.list.empty_desc') ?></p>
              <a href="cat_create.php" class="btn nova-btn-primary">
                <i class="bi bi-plus-circle fs-2 me-2"></i><?= __admin('categories.list.empty_button') ?>
              </a>
            </div>
          </div>
        <?php endif; ?>

      </section>

    </div>
  </main>

  <!-- Footer -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
