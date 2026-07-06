# Changelog

All notable changes to Star51 will be documented in this file.

## [1.0.1] - 2026-07-06

### Changed

- Separated Nova system configuration from administrator management by moving it to the dedicated `nova/system/` section.
- Updated Nova navigation with the `Admin` menu, grouping administrator management and system configuration in a clearer area.
- Renamed the `Management` menu to `Collection` to better describe the area for categories, subcategories, and articles.
- Updated the `Collection` icon to `bi-archive` for clearer visual recognition.

### Fixed

- Fixed password recovery when Nova security constants are not loaded before rate-limit handling.
- Added missing password recovery translations.
- Improved password recovery logging with clearer context.
- Removed final PHP closing tags from pure PHP files.

## [1.0.0] - 2026-03-10

### Initial Release

- Universal collection management system (single user)
- Categories and subcategories with two-level hierarchy
- Article management with rich text editor (Quill)
- Image gallery with automatic resizing (max, med, min)
- YouTube video embedding on article detail pages
- Multilingual support (Italian, English) with JSON-based i18n
- Full-text search across articles
- Contact form with SMTP email support
- Express installation wizard
- SEO: sitemap.xml, robots.txt, Open Graph, JSON-LD
- Security: CSRF protection, prepared statements, rate limiting, security headers
- Responsive frontend with Bootstrap 5
- Admin panel (NovaStar51) for content management
