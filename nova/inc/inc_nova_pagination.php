<?php
/**
 * Nova Pagination Include - Procedural PHP Compatible 7.4-8.2
 * Universal pagination component with Nova styling + record info
 * Handles both simple and complex pagination with URL parameters
 *
 * Required variables before including this file:
 * - $total_pages (int): Total number of pages
 * - $current_page (int): Current page number
 * - $base_url (string): Base URL without query parameters (e.g., "articles_list.php")
 * - $total_records (int): Total records in database (optional - for info display)
 * - $extra_params (array): Additional URL parameters to preserve (optional)
 *
 * Usage examples:
 *
 * // Simple pagination (no filters)
 * $total_pages = 10;
 * $current_page = 3;
 * $base_url = "articles_list.php";
 * $total_records = 95;
 * $extra_params = [];
 * include "../inc/inc_nova_pagination.php";
 *
 * // Complex pagination (with filters)
 * $extra_params = ['cat' => 5, 'status' => 'active'];
 * include "../inc/inc_nova_pagination.php";
 */

// Check required variables
if (!isset($total_pages) || !isset($current_page) || !isset($base_url)) {
  error_log(
    "Nova Pagination: Missing required variables (total_pages, current_page, base_url)",
  );
  return;
}

// Skip pagination if only one page or no pages
if ($total_pages <= 1 && !isset($total_records)) {
  return;
}

// Initialize extra_params if not set
if (!isset($extra_params)) {
  $extra_params = [];
}

// Calculate page range (show max 5 pages around current)
$start_page = max(1, $current_page - 2);
$end_page = min($total_pages, $current_page + 2);

// Ensure we show at least 5 pages if available
if ($end_page - $start_page < 4) {
  if ($start_page == 1) {
    $end_page = min($total_pages, $start_page + 4);
  } else {
    $start_page = max(1, $end_page - 4);
  }
}
?>

<!-- Nova Pagination Component -->
<div class="mt-4">
    <!-- Record Info (if available) -->
    <?php if (isset($total_records) && $total_records > 0): ?>
    <div class="text-center mb-3">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            <strong><?php echo $total_records; ?></strong> record, in <strong><?php echo $total_pages; ?></strong> <?php echo $total_pages == 1 ? __admin('pagination.page_singular') : __admin('pagination.page_plural'); ?>
        </small>
    </div>
    <?php endif; ?>

    <!-- Pagination Navigation -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="<?= __admin('pagination.nav_label') ?>">
        <ul class="pagination nova-pagination justify-content-center">

            <!-- First Page -->
            <?php if ($current_page > 1): ?>
                <?php
                // Build first page URL
                $params = $extra_params;
                $params['page'] = 1;
                $first_url = $base_url . "?" . http_build_query($params);
                ?>
                <li class="page-item">
                    <a class="page-link nova-page-link" href="<?php echo $first_url; ?>" title="<?= __admin('pagination.first') ?>">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Previous Page -->
            <?php if ($current_page > 1): ?>
                <?php
                // Build previous page URL
                $params = $extra_params;
                $params['page'] = $current_page - 1;
                $prev_url = $base_url . "?" . http_build_query($params);
                ?>
                <li class="page-item">
                    <a class="page-link nova-page-link" href="<?php echo $prev_url; ?>" title="<?= __admin('pagination.previous') ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php
                // Build page number URL
                $params = $extra_params;
                $params['page'] = $i;
                $page_url = $base_url . "?" . http_build_query($params);
                $active_class = $i == $current_page ? 'active' : '';
                ?>
                <li class="page-item <?php echo $active_class; ?>">
                    <a class="page-link nova-page-link" href="<?php echo $page_url; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>

            <!-- Next Page -->
            <?php if ($current_page < $total_pages): ?>
                <?php
                // Build next page URL
                $params = $extra_params;
                $params['page'] = $current_page + 1;
                $next_url = $base_url . "?" . http_build_query($params);
                ?>
                <li class="page-item">
                    <a class="page-link nova-page-link" href="<?php echo $next_url; ?>" title="<?= __admin('pagination.next') ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Last Page -->
            <?php if ($current_page < $total_pages): ?>
                <?php
                // Build last page URL
                $params = $extra_params;
                $params['page'] = $total_pages;
                $last_url = $base_url . "?" . http_build_query($params);
                ?>
                <li class="page-item">
                    <a class="page-link nova-page-link" href="<?php echo $last_url; ?>" title="<?= __admin('pagination.last') ?>">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>
    <?php endif; ?>
</div>
<!-- END: Nova Pagination Component -->
