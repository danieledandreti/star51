<?php
/**
 * Star51 - Common HEAD Section
 * Include file for common <head> section across all pages
 *
 * Required variables:
 * - $page_title (string): Page title (e.g., "About Us", "Contact") - Default: "Star51"
 * - $page_description (string): Meta description for SEO
 */

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");


// Load site configuration from Nova settings
require_once __DIR__ . "/../nova/conf/nova_config_values.php";

// Load frontend language translations
require_once __DIR__ . "/inc_star51_lang.php";

// Get site name from Nova settings (fallback to "NovaStar51" if empty)
$site_name = !empty($nova_settings["site_name"]) ? $nova_settings["site_name"] : "NovaStar51";

// Default values if not specified
$page_title = $page_title ?? $site_name;
$page_description = $page_description ?? __front('homepage.page_description');
$meta_robots = $meta_robots ?? 'index, follow';

// Open Graph defaults
$og_title = $page_title === $site_name ? $site_name : $page_title . " | " . $site_name;
$og_site_url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost");
$og_url = $og_site_url . ($_SERVER["REQUEST_URI"] ?? "/");
$og_image = isset($page_image) ? $og_site_url . "/" . $page_image : $og_site_url . "/img/logo-star51.png";
?>
<!doctype html>
<html lang="<?= star51_get_current_lang() ?>">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $page_title === $site_name ? $site_name : htmlspecialchars($page_title, ENT_QUOTES, "UTF-8") . " | " . $site_name ?></title>
  <meta name="description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, "UTF-8") ?>" />
  <meta name="author" content="Daniele D'Andreti" />
  <meta name="robots" content="<?= $meta_robots ?>" />
  <link rel="canonical" href="<?= htmlspecialchars($og_url, ENT_QUOTES, "UTF-8") ?>" />

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($og_title, ENT_QUOTES, "UTF-8") ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($page_description, ENT_QUOTES, "UTF-8") ?>" />
  <meta property="og:image" content="<?= htmlspecialchars($og_image, ENT_QUOTES, "UTF-8") ?>" />
  <meta property="og:url" content="<?= htmlspecialchars($og_url, ENT_QUOTES, "UTF-8") ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="<?= htmlspecialchars($site_name, ENT_QUOTES, "UTF-8") ?>" />

  <!-- Favicon & PWA Icons -->
  <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="96x96" href="favicon/favicon-96x96.png">
  <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg">
  <link rel="manifest" href="favicon/site.webmanifest">
  <link rel="icon" href="favicon/favicon.ico" />

  <!-- CDN Preconnect -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous" />

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet"
        integrity="sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk"
        crossorigin="anonymous" />

  <!-- GLightbox CSS (conditional) -->
  <?php if (!empty($use_glightbox)): ?>
    <link href="https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css"
          rel="stylesheet" />
  <?php endif; ?>

  <!-- Star51 System CSS -->
  <link href="css/star51-system.css" rel="stylesheet" />

  <!-- Page-specific preload hints -->
  <?php if (isset($page_preload)): ?>
    <?php foreach ($page_preload as $preload): ?>
      <link rel="preload" href="<?= $preload['href'] ?>" as="<?= $preload['as'] ?>" />
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Fallback: show cards if JS is disabled -->
  <noscript><style>.star51-card, .news-card { opacity: 1; transform: none; }</style></noscript>

</head>

<body class="star51-content bg-star51-cream-light" data-theme="star51">
  <a href="#main-content" class="visually-hidden-focusable">Skip to main content</a>
