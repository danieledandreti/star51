<?php
/**
 * Star51 Frontend Language Loader
 * Loads translations from static JSON file based on nova_config_values.php
 *
 * Usage:
 *   include 'inc/inc_star51_lang.php';
 *   echo __front('nav.home');
 *
 * Function: __front() for frontend translations
 * Admin uses __admin() - separate functions to avoid conflicts
 *
 * Supported languages: it (Italian - default), en (English)
 * Language is stored in nova_config_values.php (shared with admin)
 */

// Prevent double loading
if (defined('STAR51_LANG_LOADED')) {
    return;
}
define('STAR51_LANG_LOADED', true);

// Load config if not already loaded
if (!isset($nova_settings)) {
    require_once __DIR__ . '/../nova/conf/nova_config_values.php';
}

// Get language from config (defaults to 'it')
$star51_lang_code = $nova_settings['nova_lang'] ?? 'it';

// Validate supported language
$star51_supported_langs = ['it', 'en'];
if (!in_array($star51_lang_code, $star51_supported_langs, true)) {
    $star51_lang_code = 'it'; // Fallback to Italian
}

// Build language file path (uses {lang}_front.json)
$star51_lang_file = __DIR__ . "/../nova/lang/{$star51_lang_code}_front.json";

// Fallback to Italian if file doesn't exist
if (!file_exists($star51_lang_file)) {
    $star51_lang_file = __DIR__ . '/../nova/lang/it_front.json';
    $star51_lang_code = 'it';
}

// Load and decode language file
$star51_lang_content = file_get_contents($star51_lang_file);
$L = json_decode($star51_lang_content, true);

// Verify successful loading
if ($L === null) {
    error_log("Star51 Lang Error: Failed to parse {$star51_lang_file} - " . json_last_error_msg());
    $L = []; // Empty array to prevent errors
}

/**
 * Helper function for dot notation access (Frontend)
 * Example: __front('nav.home') returns $L['nav']['home']
 *
 * @param string $key Key with dot notation
 * @return string Translation or key if not found (debug-friendly)
 */
function __front($key) {
    global $L;

    // Handle empty key
    if (empty($key)) {
        return '';
    }

    // Split key by dots
    $keys = explode('.', $key);
    $value = $L;

    // Traverse the array
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            // Return key if not found (makes debugging easier)
            return $key;
        }
        $value = $value[$k];
    }

    return $value;
}

/**
 * Get current frontend language code
 *
 * @return string Current language code (e.g., 'it', 'en')
 */
function star51_get_current_lang() {
    global $star51_lang_code;
    return $star51_lang_code;
}

/**
 * Format date according to current language settings
 * Uses date.format and date.months from language JSON
 *
 * Format tokens:
 *   d = day (01-31)
 *   month = full month name from JSON
 *   Y = year (4 digits)
 *
 * IT: "d month Y" → "27 Gennaio 2026"
 * EN: "month d, Y" → "January 27, 2026"
 *
 * @param string|DateTime $date Date string or DateTime object
 * @return string Formatted date or fallback message
 */
function format_date_i18n($date) {
    global $L;

    // Handle empty date
    if (empty($date)) {
        return __front('messages.no_date');
    }

    // Convert to DateTime if string
    if (is_string($date)) {
        try {
            $date = new DateTime($date);
        } catch (Exception $e) {
            return __front('messages.no_date');
        }
    }

    // Get format template and month names from JSON
    $format = $L['date']['format'] ?? '{d} {month} {Y}';
    $months = $L['date']['months'] ?? [];

    // Get month name (1-based index, array is 0-based)
    $month_index = (int)$date->format('n') - 1;
    $month_name = $months[$month_index] ?? $date->format('F');

    // Build formatted date
    $day = $date->format('d');
    $year = $date->format('Y');

    // Replace {token} placeholders (safe — no collision between tokens)
    $result = str_replace(
      ['{d}', '{month}', '{Y}'],
      [$day, $month_name, $year],
      $format
    );

    return $result;
}
