<?php
// Nova Admins List - Single admin hub (Solo Edition)
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('admins.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('admins.page.description');

// Fetch current admin data
$query_admin = '
  SELECT
    id_admin,
    first_name,
    last_name,
    username,
    email,
    created_at,
    updated_at
  FROM ns_admins
  WHERE id_admin = ?
';
$stmt = mysqli_prepare($conn, $query_admin);
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['admin_id']);
mysqli_stmt_execute($stmt);
$rs_admin = mysqli_stmt_get_result($stmt);

if (!$rs_admin) {
  error_log('Error fetching admin: ' . mysqli_error($conn));
  die(__admin('admins.err.load_account'));
}

$admin = mysqli_fetch_assoc($rs_admin);
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
            <h1 class="page-title">
              <i class="bi bi-people me-2"></i><?= __admin('admins.page.title') ?>
            </h1>
          </div>
          <div class="d-flex gap-2">
            <!-- System Configuration Button -->
            <a href="admins_settings.php"
               class="btn btn-outline-primary nova-btn-action">
              <i class="bi bi-gear-fill"></i><?= __admin('admins.buttons.settings') ?>
            </a>
          </div>
        </div>
      </header>

      <!-- Flash Messages -->
      <?php
      $flash_modules = ['admin'];
      include '../inc/inc_nova_flash_messages.php';
      ?>

      <!-- Admin Table Section -->
      <section class="content-section mb-5">
        <div class="row">
          <div class="col-12">
            <div class="nova-card">
              <div class="nova-card-body">
                <h5 class="card-title mb-1">
                  <i class="bi bi-list-ul me-2"></i><?= __admin('admins.list.title') ?>
                </h5>
                <p class="page-subtitle mb-2"><?= __admin('admins.list.subtitle') ?></p>

                <div class="table-responsive">
                  <table class="table table-hover nova-table nova-table-compact">
                    <thead class="table-dark">
                      <tr>
                        <th scope="col"><?= __admin('admins.list.col_name') ?></th>
                        <th scope="col" class="d-none d-md-table-cell"><?= __admin('admins.list.col_username') ?></th>
                        <th scope="col" class="d-none d-lg-table-cell"><?= __admin('admins.list.col_email') ?></th>
                        <th scope="col" class="d-none d-xl-table-cell"><?= __admin('admins.list.col_created_by') ?></th>
                        <th scope="col" class="text-center"><?= __admin('admins.list.col_actions') ?></th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php if (!empty($admin)): ?>
                        <tr style="--bs-table-accent-bg: var(--bs-table-hover-bg)">
                          <!-- Full Name -->
                          <td>
                            <i class="bi bi-person-circle me-2"></i>
                            <?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?>
                            <small class="d-md-none d-block">
                              @<?= htmlspecialchars($admin['username']) ?>
                            </small>
                          </td>

                          <!-- Username (Desktop only) -->
                          <td class="d-none d-md-table-cell">
                            <code><?= htmlspecialchars($admin['username']) ?></code>
                          </td>

                          <!-- Email (Desktop only) -->
                          <td class="d-none d-lg-table-cell">
                            <a href="mailto:<?= htmlspecialchars($admin['email']) ?>"
                               class="text-decoration-none">
                              <?= htmlspecialchars($admin['email']) ?>
                            </a>
                          </td>

                          <!-- Created by + dates (Desktop only) -->
                          <td class="d-none d-xl-table-cell">
                            <small class="text-muted">
                              <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($admin_full_name) ?>
                              <br>
                              <?= __admin('admins.list.created') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($admin['created_at'])) ?>
                              <br>
                              <?php if ($admin['updated_at'] && $admin['updated_at'] != $admin['created_at']): ?>
                                <?= __admin('admins.list.modified') ?>: <?= date(NOVA_DATE_FORMAT . ', H:i:s', strtotime($admin['updated_at'])) ?>
                              <?php endif; ?>
                            </small>
                          </td>

                          <!-- Actions (Edit only) -->
                          <td class="text-center">
                            <a href="admins_edit.php"
                               class="btn btn-lg btn-outline-success"
                               title="<?= __admin('admins.buttons.edit') ?>">
                              <i class="bi bi-pencil"></i>
                            </a>
                          </td>
                        </tr>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="text-center text-danger py-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?= __admin('admins.list.error_load_account') ?>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- Footer -->
  <?php include '../inc/inc_nova_footer.php'; ?>

</body>
</html>
