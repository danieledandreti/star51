<?php
/**
 * Nova Admin Language Loader
 * Loads translations from static JSON file based on nova_config_values.php
 *
 * Usage:
 *   echo __admin('nav.dashboard');
 *   echo __admin('buttons.save');
 *
 * Function: __admin() for admin translations
 * Frontend uses __front() - separate functions for clarity
 *
 * Supported languages: it (Italian - default), en (English)
 * Language is stored in nova_config_values.php, NOT in cookie
 */

// Get language from config (defaults to 'it')
global $nova_settings;
$nova_lang_code = $nova_settings['nova_lang'] ?? 'it';

// Validate supported language
$nova_supported_langs = ['it', 'en'];
if (!in_array($nova_lang_code, $nova_supported_langs, true)) {
  $nova_lang_code = 'it'; // Fallback to Italian
}

// Build language file path (now uses {lang}_admin.json)
$nova_lang_file = __DIR__ . '/../lang/' . $nova_lang_code . '_admin.json';

// Fallback to Italian if file doesn't exist
if (!file_exists($nova_lang_file)) {
  $nova_lang_file = __DIR__ . '/../lang/it_admin.json';
  $nova_lang_code = 'it';
}

// Load and decode language file
$nova_lang_content = file_get_contents($nova_lang_file);
$L = json_decode($nova_lang_content, true);

// Verify successful loading
if ($L === null) {
  error_log("Nova Lang Error: Failed to parse {$nova_lang_file} - " . json_last_error_msg());
  $L = []; // Empty array to prevent errors
}

/**
 * Helper function for dot notation access (Admin)
 * Example: __admin('buttons.save') returns $L['buttons']['save']
 *
 * @param string $key Key with dot notation
 * @return string Translation or key if not found (debug-friendly)
 */
function __admin($key)
{
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
 * Get list of available languages with metadata
 * Used for the language dropdown in navigation and settings
 *
 * @return array Array of language info ['code' => [...], ...]
 */
function nova_get_available_languages()
{
  return [
    'it' => [
      'code' => 'it',
      'name' => 'Italiano',
      'native' => 'Italiano',
    ],
    'en' => [
      'code' => 'en',
      'name' => 'English',
      'native' => 'English',
    ],
  ];
}

/**
 * Get current language code
 *
 * @return string Current language code (e.g., 'it', 'en')
 */
function nova_get_current_lang()
{
  global $nova_lang_code;
  return $nova_lang_code;
}
