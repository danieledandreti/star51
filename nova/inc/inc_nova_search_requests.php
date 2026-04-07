<?php
// Nova Requests Search Form Include
// Simple reusable search form for requests
// Used in: home.php, requests_list.php, requests_search.php

// Expected variable before include:
// - $form_action (required): path to requests_search.php (relative to current page)
// - $search_query (optional): pre-filled search value (default empty)

$search_query = $search_query ?? '';
?>
<form action="<?= $form_action ?>" method="get" role="search">
  <div class="input-group">
    <input type="text"
           name="rs"
           value="<?= htmlspecialchars($search_query) ?>"
           class="form-control"
           required>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-search me-1"></i><?= __admin('buttons.search') ?>
    </button>
  </div>
  <small class="form-text text-muted mt-1 d-block">
    <?= __admin('search.requests_hint') ?>
  </small>
</form>
