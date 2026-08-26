<?php
// Nova Super Admin Settings - Global system configuration
// Session management and database connection
include '../inc/inc_nova_session.php';

// Page configuration
$page_title = __admin('settings.page.title') . ' | ' . $nova_settings['admin_name'];
$page_description = __admin('settings.page.subtitle');

// Success/Error messages
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
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
        <div>
          <h1 class="page-title">
            <i class="bi bi-gear-fill me-2"></i><?= __admin('settings.page.title') ?>
          </h1>
          <p class="page-subtitle mb-0">
            <i class="bi bi-shield-lock-fill me-1"></i>
            <?= __admin('settings.page.access') ?>
          </p>
        </div>
      </header>

      <!-- Success Message -->
      <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i>
          <?= $success_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Error Message -->
      <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?= $error_message ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Utilities Section -->
      <section class="content-section mb-5">
        <!-- Installation File Removal & Log Management -->
        <div class="row g-4">

          <!-- Installation File Removal -->
          <div class="col-md-4">
            <div class="card h-100">
              <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">
                  <i class="bi bi-trash3 me-2"></i><?= __admin('settings.cleanup.title') ?>
                </h6>
              </div>
              <div class="card-body d-flex flex-column">
                <?php
                // Check removable installation files and the persistent lock separately.
                $project_root = dirname(__DIR__, 2);
                $install_dir = $project_root . '/install';
                $installed_file = $project_root . '/.installed';
                $install_dir_exists = is_dir($install_dir);
                $lock_file_exists = file_exists($installed_file);
                ?>

                <div class="table-responsive mb-3">
                  <table class="table table-sm align-middle mb-0">
                    <thead>
                      <tr>
                        <th><?= __admin('settings.cleanup.element') ?></th>
                        <th class="text-end"><?= __admin('settings.cleanup.status') ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <i class="bi bi-folder-fill me-1 text-secondary"></i>
                          <?= __admin('settings.cleanup.install_directory') ?>
                        </td>
                        <td class="text-end <?= $install_dir_exists ? 'text-warning' : 'text-success' ?>">
                          <i class="bi <?= $install_dir_exists ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill' ?> me-1"></i>
                          <?= $install_dir_exists
                            ? __admin('settings.cleanup.remove_required')
                            : __admin('settings.cleanup.removed') ?>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <i class="bi bi-shield-lock-fill me-1 text-secondary"></i>
                          <?= __admin('settings.cleanup.lock_file') ?>
                        </td>
                        <td class="text-end <?= $lock_file_exists ? 'text-success' : 'text-danger' ?>">
                          <i class="bi <?= $lock_file_exists ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
                          <?= $lock_file_exists
                            ? __admin('settings.cleanup.protection_active')
                            : __admin('settings.cleanup.protection_missing') ?>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <?php if ($install_dir_exists): ?>
                  <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong><?= __admin('settings.cleanup.install_detected') ?></strong>
                    <p class="mb-0 mt-2 small"><?= __admin('settings.cleanup.install_detected_desc') ?></p>
                  </div>
                <?php endif; ?>

                <?php if (!$lock_file_exists): ?>
                  <div class="alert alert-danger mb-3">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    <strong><?= __admin('settings.cleanup.unprotected') ?></strong>
                    <p class="mb-0 mt-2 small"><?= __admin('settings.cleanup.unprotected_desc') ?></p>
                  </div>
                <?php elseif (!$install_dir_exists): ?>
                  <div class="alert alert-success mb-3">
                    <i class="bi bi-shield-check me-2"></i>
                    <strong><?= __admin('settings.cleanup.protected') ?></strong>
                    <p class="mb-0 mt-2 small"><?= __admin('settings.cleanup.protected_desc') ?></p>
                  </div>
                <?php endif; ?>

                <?php if ($install_dir_exists): ?>
                  <a href="cleanup_install.php?csrf_token=<?= $_SESSION['csrf_token'] ?>"
                     class="btn btn-danger btn-sm w-100 mt-auto"
                     onclick="return confirm('<?= __admin('settings.cleanup.remove_confirm') ?>');">
                    <i class="bi bi-trash3-fill me-1"></i>
                    <?= __admin('settings.cleanup.remove_button') ?>
                  </a>
                <?php endif; ?>

              </div>
            </div>
          </div>

          <!-- Log Management -->
          <div class="col-md-8">
            <div class="card h-100">
              <div class="card-header bg-light border-bottom">
                <h6 class="mb-0 fw-bold">
                  <i class="bi bi-file-earmark-text me-2"></i><?= __admin('settings.log.title') ?>
                </h6>
              </div>
              <div class="card-body py-2">
                <?php
                // Log file path (from centralized constant in nova_config.php)
                $log_file = NOVA_LOG_PATH;

                // Auto-create log file if it doesn't exist
                if (!file_exists($log_file)) {
                  touch($log_file);
                  chmod($log_file, 0644);
                }

                $log_exists = file_exists($log_file);

                if ($log_exists) {
                  $log_size_bytes = filesize($log_file);
                  $log_size_kb = round($log_size_bytes / 1024, 2);
                  $log_size_mb = round($log_size_bytes / (1024 * 1024), 2);
                  $log_modified = filemtime($log_file);

                  // Read newest 100 lines
                  $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                  $total_lines = count($log_lines);
                  $log_lines = array_reverse($log_lines);
                  $last_100_lines = array_slice($log_lines, 0, 100);
                }
                ?>

                <?php if ($log_exists): ?>
                  <!-- Log Info + Actions - Single Line -->
                  <div class="d-flex justify-content-between align-items-center">
                    <!-- Left: Log Info -->
                    <div class="text-muted nova-log-info-text">
                      <i class="bi bi-file-earmark-text me-1"></i>
                      error.log
                      <span class="mx-2">•</span>
                      <i class="bi bi-hdd me-1"></i>
                      <?= $log_size_mb > 1 ? $log_size_mb . ' MB' : $log_size_kb . ' KB' ?>
                      <span class="mx-2">•</span>
                      <i class="bi bi-list-ol me-1"></i>
                      <?= number_format($total_lines, 0, ',', '.') ?> <?= __admin('settings.log.lines') ?>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="d-flex gap-3">
                      <!-- Archive Log Button -->
                      <a href="log_archive.php?csrf_token=<?= $_SESSION['csrf_token'] ?>"
                         class="btn btn-outline-primary btn-sm"
                         onclick="return confirm('<?= __admin('settings.log.archive_confirm') ?>');">
                        <i class="bi bi-archive me-1"></i><?= __admin('settings.log.archive_button') ?>
                      </a>

                      <!-- Toggle Accordion Button -->
                      <a href="#logViewerAccordion"
                         class="btn btn-primary btn-sm nova-log-toggle collapsed"
                         data-bs-toggle="collapse"
                         aria-expanded="false"
                         aria-controls="logViewerAccordion">
                        <i class="bi bi-chevron-down me-1"></i><?= __admin('settings.log.view_last') ?>
                      </a>
                    </div>
                  </div>

                  <!-- Accordion: Log Viewer -->
                  <div class="collapse mt-3" id="logViewerAccordion">
                    <div class="bg-light border rounded p-3">
                      <h6 class="text-muted mb-3">
                        <i class="bi bi-eye me-2"></i><?= __admin('settings.log.last_records') ?>
                        <small class="text-muted">(<?= number_format($total_lines, 0, ',', '.') ?> <?= __admin('settings.log.total') ?>)</small>
                      </h6>
                      <div class="bg-white border rounded p-3 nova-log-viewer">
                        <?php if (!empty($last_100_lines)): ?>
                          <?php foreach ($last_100_lines as $line): ?>
                            <?php
                            // Syntax highlighting - Standard black text, color only for errors/warnings
                            $line_class = '';

                            // ERROR patterns - Red
                            if (preg_match('/\b(ERROR|Fatal|Failed|Exception)\b/i', $line)) {
                              $line_class = 'text-danger fw-semibold';
                            }
                            // WARNING patterns - Yellow/Orange
                            elseif (preg_match('/\b(WARNING|Notice|Deprecated)\b/i', $line)) {
                              $line_class = 'text-warning';
                            }
                            ?>
                            <div class="<?= $line_class ?> mb-1">
                              <?= htmlspecialchars($line) ?>
                            </div>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <p class="text-muted fst-italic mb-0"><?= __admin('settings.log.no_records') ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                <?php else: ?>
                  <!-- Log file doesn't exist -->
                  <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong><?= __admin('settings.log.log_not_found') ?></strong><br>
                    <?= __admin('settings.log.log_auto_create') ?>
                  </div>
                <?php endif; ?>

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
