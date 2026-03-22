<?php
/**
 * Star51 - Reserved IDs Constants
 * System reserved category and subcategory IDs
 *
 * DO NOT MODIFY - These IDs are hardcoded in frontend/backend logic
 * Reference: _dev/docs-star51/MYSQL_RESERVED_CONVENTIONS.md
 *
 * Created: December 2025 (Session 47)
 * Philosophy: Self-documenting code > Magic numbers
 */

// ============================================
// RESERVED CATEGORY IDs
// ============================================

/**
 * Category 1 - Extra (System Backup)
 * Purpose: Backup container for orphaned articles when categories are deleted
 * Frontend: EXCLUDED from display (NOT IN query)
 * Nova Logic: Orphaned articles moved to Subcategory 1 (Varie)
 */
define('CATEGORY_EXTRA', 1);

/**
 * Category 2 - Info (System Reserved)
 * Purpose: System category for special content (News)
 * Frontend: EXCLUDED from category listings
 * Contains: Subcategory 2 (News) - visible in homepage sidebar
 */
define('CATEGORY_INFO', 2);

// ============================================
// RESERVED SUBCATEGORY IDs
// ============================================

/**
 * Subcategory 1 - Varie (Default Orphan Container)
 * Parent: Category 1 (Extra)
 * Purpose: Default container for orphaned articles
 * Frontend: NOT VISIBLE (parent category excluded)
 * Nova Logic: cat_delete.php, subcat_delete.php move orphans here
 */
define('SUBCATEGORY_VARIE', 1);

/**
 * Subcategory 2 - News (System Content)
 * Parent: Category 2 (Info)
 * Purpose: News/Company updates
 * Frontend: VISIBLE in homepage sidebar and dedicated news page
 * Hardcoded in: index.php, news.php
 */
define('SUBCATEGORY_NEWS', 2);

// ============================================
// END OF RESERVED IDs
// ============================================
