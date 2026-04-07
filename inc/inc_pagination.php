<?php
/**
 * Star51 - Pagination Component
 * Reusable pagination include for frontend pages
 *
 * Requires: inc_star51_lang.php loaded (via inc_head.php)
 *
 * Required variables:
 * - $current_page_num (int): Current page number (1-based)
 * - $total_pages (int): Total number of pages
 * - $base_url (string): Base URL without query params (e.g., "articles.php")
 * - $query_params (array): Additional query parameters (e.g., ['cat' => 3, 'subcat' => 5])
 * - $aria_label (string): ARIA label for navigation (e.g., "Articles navigation")
 *
 * Example usage:
 *
 * $current_page_num = 3;
 * $total_pages = 10;
 * $base_url = "articles.php";
 * $query_params = ['cat' => 3, 'subcat' => 5];
 * $aria_label = "Articles navigation";
 * include "inc/inc_pagination.php";
 */

// Validate required variables
if (!isset($current_page_num, $total_pages, $base_url, $aria_label)) {
  trigger_error(
    "inc_pagination.php requires: \$current_page_num, \$total_pages, \$base_url, \$aria_label",
    E_USER_WARNING,
  );
  return;
}

// Default query params to empty array if not set
$query_params = $query_params ?? [];

// Only show pagination if more than 1 page
if ($total_pages <= 1) {
  return;
}

/**
 * Helper function: Build URL with query parameters
 */
function build_pagination_url($base, $params, $page_num)
{
  $all_params = array_merge($params, ['page' => $page_num]);
  $query_string = http_build_query($all_params);
  return $base . '?' . $query_string;
}

// Calculate page range (max 5 numbers with current page in middle)
$start_page = max(1, $current_page_num - 2);
$end_page = min($total_pages, $current_page_num + 2);

// Adjust if at beginning or end
if ($current_page_num <= 2) {
  $end_page = min(5, $total_pages);
}
if ($current_page_num >= $total_pages - 1) {
  $start_page = max(1, $total_pages - 4);
}

// Build URLs
$prev_url = $current_page_num > 1
  ? build_pagination_url($base_url, $query_params, $current_page_num - 1)
  : "#";
$next_url = $current_page_num < $total_pages
  ? build_pagination_url($base_url, $query_params, $current_page_num + 1)
  : "#";
?>

<!-- Pagination Component -->
<nav aria-label="<?= htmlspecialchars($aria_label) ?>" class="mt-5">

  <?php if (isset($total_items) && $total_items > 0 && isset($items_per_page)): ?>
    <?php
    $range_start = ($current_page_num - 1) * $items_per_page + 1;
    $range_end = min($current_page_num * $items_per_page, $total_items);
    ?>
    <p class="text-center text-muted small mb-2">
      <?= __front('pagination.showing') ?> <?= $range_start ?>-<?= $range_end ?> <?= __front('pagination.of') ?> <?= $total_items ?> <?= __front('pagination.results') ?>
    </p>
  <?php endif; ?>

  <ul class="pagination justify-content-center">

    <!-- Previous page button -->
    <li class="page-item <?= $current_page_num <= 1 ? 'disabled' : '' ?>">
      <a class="page-link"
         href="<?= htmlspecialchars($prev_url) ?>"
         <?= $current_page_num <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>
         aria-label="<?= __front('pagination.previous') ?>">
        <i class="bi bi-chevron-left"></i>
      </a>
    </li>

    <!-- Page numbers -->
    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
      <li class="page-item <?= $i == $current_page_num ? 'active' : '' ?>"
          <?= $i == $current_page_num ? 'aria-current="page"' : '' ?>>
        <a class="page-link" href="<?= htmlspecialchars(build_pagination_url($base_url, $query_params, $i)) ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- Next page button -->
    <li class="page-item <?= $current_page_num >= $total_pages ? 'disabled' : '' ?>">
      <a class="page-link"
         href="<?= htmlspecialchars($next_url) ?>"
         <?= $current_page_num >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : '' ?>
         aria-label="<?= __front('pagination.next') ?>">
        <i class="bi bi-chevron-right"></i>
      </a>
    </li>

  </ul>


</nav>
