<?php
/**
 * ============================================
 * NOVA FORM HELPERS - Universal Form Functions
 * ============================================
 * Handles form field repopulation after validation errors
 *
 * Philosophy: Single Source of Truth - One function to rule them all
 * Pattern: Session data > Database data > Default value
 *
 * Usage Examples:
 *
 *   // EDIT FORMS (with database data)
 *   include '../inc/inc_nova_form_helpers.php';
 *   $form_data = $_SESSION['xxx_form_data'] ?? [];
 *
 *   <input value="<?= nova_get_form_value('first_name', $admin, $form_data) ?>">
 *   <input <?= nova_is_checked('is_active', $admin, $form_data) ?>>
 *
 *   // CREATE FORMS (no database data, with defaults)
 *   include '../inc/inc_nova_form_helpers.php';
 *   $form_data = $_SESSION['xxx_form_data'] ?? [];
 *
 *   <input value="<?= nova_get_form_value('first_name', null, $form_data) ?>">
 *   <input <?= nova_is_checked('is_active', null, $form_data, true) ?>>
 *
 * Created: November 2025 (Session 21)
 * Project: Star51 Nova Admin System
 * ============================================
 */

// Prevent multiple inclusions
if (defined('NOVA_FORM_HELPERS_LOADED')) {
    return;
}
define('NOVA_FORM_HELPERS_LOADED', true);

/**
 * Get form field value with priority logic
 *
 * Priority order:
 * 1. Session form data (after validation error - show what user typed)
 * 2. Database record data (for edit forms - show current DB value)
 * 3. Default value (for create forms - show default)
 *
 * @param string $field Field name
 * @param array|null $db_data Database record (e.g., $admin, $article, $category)
 * @param array|null $form_data Session form data ($_SESSION['xxx_form_data'])
 * @param string $default Default value for create forms
 * @return string HTML-safe field value (escaped with htmlspecialchars)
 */
function nova_get_form_value($field, $db_data = null, $form_data = null, $default = '')
{
    // Priority 1: Session form data (user just submitted invalid data)
    if (isset($form_data[$field])) {
        return htmlspecialchars($form_data[$field]);
    }

    // Priority 2: Database data (editing existing record)
    if (isset($db_data[$field])) {
        return htmlspecialchars($db_data[$field]);
    }

    // Priority 3: Default value (creating new record)
    return htmlspecialchars($default);
}

/**
 * Check if checkbox should be checked
 *
 * Priority order:
 * 1. Session form data (after validation error)
 * 2. Database record data (for edit forms)
 * 3. Default state (for create forms)
 *
 * NOTE: Checkbox logic is tricky because unchecked checkboxes don't send
 * any value in POST data. We only check isset() on session data if we're
 * in validation error state (form_data exists). Otherwise we check the value.
 *
 * @param string $field Field name
 * @param array|null $db_data Database record
 * @param array|null $form_data Session form data
 * @param bool $default Default checked state (true = checked by default)
 * @return string 'checked' or empty string
 */
function nova_is_checked($field, $db_data = null, $form_data = null, $default = false)
{
    // Priority 1: Session form data
    // If form_data exists AND has the field, use it
    // If form_data exists but field is missing, checkbox was unchecked
    if (isset($form_data[$field])) {
        return $form_data[$field] ? 'checked' : '';
    }

    // Priority 2: Database data
    if (isset($db_data[$field])) {
        return $db_data[$field] ? 'checked' : '';
    }

    // Priority 3: Default state
    return $default ? 'checked' : '';
}

?>
