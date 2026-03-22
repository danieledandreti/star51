<?php
// Nova Categories Toggle - Universal toggle using include
// Session management and database connection
include '../inc/inc_nova_session.php';

// Set parameters for universal toggle
$toggle_table = 'ns_categories';
$toggle_id_field = 'id_category';
$toggle_id_value = isset($_GET['id']) ? $_GET['id'] : 0;
$toggle_success_session = 'cat_success';

// Use universal toggle handler
include '../inc/inc_nova_toggle.php';
