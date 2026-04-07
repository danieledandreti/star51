<?php
// Nova Toggle Include - Universal ON/OFF toggle for any boolean field
//
// Required variables (set before including):
//   $toggle_table           — table name (must be in whitelist)
//   $toggle_id_field        — primary key column name
//   $toggle_id_value        — record ID from $_GET
//
// Optional variables:
//   $toggle_field           — column to toggle (default: 'is_active', must be in whitelist)
//   $toggle_success_session — session key for success message
//   $toggle_msg_on          — i18n key for "turned on" message (with {id} placeholder)
//   $toggle_msg_off         — i18n key for "turned off" message (with {id} placeholder)

// Security: Whitelist allowed tables
$allowed_tables = [
    'ns_articles'      => 'id_article',
    'ns_categories'    => 'id_category',
    'ns_subcategories' => 'id_subcategory',
    'ns_requests'      => 'id_request',
];

if (!isset($allowed_tables[$toggle_table]) || $allowed_tables[$toggle_table] !== $toggle_id_field) {
    error_log("Nova Toggle: Invalid table/field combination: $toggle_table / $toggle_id_field");
    header('Location: ../home.php');
    exit();
}

// Security: Whitelist allowed toggle fields
$allowed_fields = ['is_active', 'show_publish_date'];
$toggle_field = $toggle_field ?? 'is_active';

if (!in_array($toggle_field, $allowed_fields, true)) {
    error_log("Nova Toggle: Invalid field: $toggle_field");
    header('Location: ../home.php');
    exit();
}

// Security: CSRF Protection
if (!isset($_GET['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    error_log("Nova Toggle: CSRF token mismatch for $toggle_table ID $toggle_id_value");
    header('Location: ../home.php');
    exit();
}

$id = intval($toggle_id_value);

// Get current value
$check_query = "SELECT $toggle_field FROM $toggle_table WHERE $toggle_id_field = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current = mysqli_fetch_assoc($result);

// Toggle value
$new_status = $current[$toggle_field] ? 0 : 1;

// Update (no timestamp modification — toggle is state change, not content change)
$update_query = "UPDATE $toggle_table SET $toggle_field = ? WHERE $toggle_id_field = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, 'ii', $new_status, $id);
$success = mysqli_stmt_execute($stmt);

// Success message (custom or generic)
if ($success && isset($toggle_success_session)) {
    if (isset($toggle_msg_on, $toggle_msg_off)) {
        $msg_key = $new_status ? $toggle_msg_on : $toggle_msg_off;
        $_SESSION[$toggle_success_session] = str_replace('{id}', $id, __admin($msg_key));
    } else {
        $status_text = $new_status ? __admin('toggle.activated') : __admin('toggle.deactivated');
        $_SESSION[$toggle_success_session] = str_replace(
            ['{id}', '{status}'],
            [$id, $status_text],
            __admin('toggle.success')
        );
    }
}

// Log the action
error_log("Nova: Toggle $toggle_field on $toggle_table - ID: $id, new value: $new_status by Admin ID: " . $_SESSION['admin_id']);

// Redirect back to referer (pagination preserved)
$redirect_url = nova_safe_redirect('../home.php');
header("Location: $redirect_url");
exit();