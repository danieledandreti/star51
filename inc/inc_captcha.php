<?php
/**
 * Star51 - Simple Math CAPTCHA System
 * Generates a simple math question for human verification
 * Accessible to everyone with numbers 0-6 (max result 12)
 * i18n: Uses language file for numbers and questions
 */

// Ensure language loader is available
if (!defined('STAR51_LANG_LOADED')) {
  require_once __DIR__ . '/inc_star51_lang.php';
}

// Get numbers from language file (0-12)
global $L;
$numbers = $L['captcha']['numbers'] ?? ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve'];

// Get question formats from language file
$question_formats = $L['captcha']['questions'] ?? ['What is %s + %s?'];

// Generate two random numbers (0-6 for more variety)
$num1 = rand(0, 6);
$num2 = rand(0, 6);

// Calculate the sum
$captcha_result = $num1 + $num2;

// Store in session for validation
$_SESSION['captcha_num1'] = $num1;
$_SESSION['captcha_num2'] = $num2;
$_SESSION['captcha_result'] = $captcha_result;
$_SESSION['captcha_answer'] = $numbers[$captcha_result]; // Store expected answer in current language
$_SESSION['captcha_timestamp'] = time(); // Add timestamp for expiration (30 min)

// Pick a random question format
$random_format = $question_formats[array_rand($question_formats)];

// Generate the question text with variety
$captcha_question = sprintf($random_format, $numbers[$num1], $numbers[$num2]);
?>
