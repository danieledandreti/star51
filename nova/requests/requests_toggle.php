<?php
// Nova Requests Toggle - Universal toggle using include
// Session management and database connection
include '../inc/inc_nova_session.php';

// Set parameters for universal toggle
$toggle_table = 'ns_requests';
$toggle_id_field = 'id_request';
$toggle_id_value = isset($_GET['id']) ? $_GET['id'] : 0;
$toggle_success_session = 'requests_success';

// Use universal toggle handler
include '../inc/inc_nova_toggle.php';
