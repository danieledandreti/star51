# Changelog

All notable changes to Star51 will be documented in this file.

## [1.2.0] - 2026-10-16

### Improved

- Limit public and Nova search matches to item titles and summaries.
- Require all public search words, independently of word order.
- Allow short public queries; one- and two-character terms match whole words.
- Normalize punctuation separators without modifying stored content.
- Ignore public queries containing no letters or digits.
- Sort public results by item year descending, title ascending and ID descending;
  missing or zero years appear last.
- Share public count and result conditions for consistent pagination.

### Compatibility

- No database migration; existing PHP 8.0+ and MySQL 8.0+ requirements remain.
- Nova retains its three-character minimum and alphabetical ordering.
- Preserve installed configuration and uploaded files when upgrading.
- See [release notes](RELEASE_NOTES.md) and [search guide](SEARCH.md).

## [1.1.0] - 2026-08-26

Star51 v1.1.0 is a backward-compatible security and usability release for
Nova administration, the public contact form, and installation maintenance.
It requires no database migration.

### Security

- Introduced the dedicated `NOVASESSID` bootstrap for login, password
  recovery, protected pages, and logout.
- Derived the Nova cookie path from the deployed directory so installations
  are no longer tied to `/star51-dev/` or another fixed path.
- Validated that the Solo administrator still exists and is active on every
  protected request.
- Centralized directory guards and administrative security headers.
- Reliably expired the Nova session cookie during logout.
- Blocked direct HTTP access to `nova/conf` and `nova/logs` with Apache 2.4
  rules and a legacy compatibility fallback.
- Added matching installer templates so new installations deploy both
  directory protections automatically.

### Improved

- Displayed Nova log entries from newest to oldest while preserving the
  existing line limit.
- Replaced the standalone mobile language entry with one profile menu for
  profile editing, language selection, and logout.
- Added explicit contact form limits and a live 2500-character message
  counter that highlights the final 500 characters.
- Trimmed oversized contact messages consistently in the browser and on the
  server with UTF-8-aware counting and clear visitor feedback.
- Separated the removable `/install/` directory from the persistent
  `.installed` safety lock in System Configuration.
- Clarified installation removal states and actions in Italian and English.

### Compatibility and upgrade notes

- No database migration is required.
- No existing configuration values need to be changed.
- Existing administrators must sign in again once after upgrading because
  Nova now uses its dedicated session cookie.
- Deploy the complete release so the new `nova/conf/.htaccess` and
  `nova/logs/.htaccess` protections are present.
- The Installation Guide keeps its independent version number `2.5.0`.

### Verification

- Star51 Solo regression suite: **85/85 tests passed**.
- Dedicated session, contact form, mobile navigation, installer copy, package
  structure, and HTTP protection checks passed.
- PHP syntax and Italian/English JSON validation passed for the changed files.

## [1.0.2] - 2026-07-14

Star51 v1.0.2 is a maintenance and hardening release focused on safer
password recovery, reliable article image updates, and improved rich-text
handling in Nova.

This release preserves the lightweight, single-user architecture of Star51
Solo and requires no database migration.

### Security

- Hardened the Nova password recovery flow with stricter rate-limit handling.
- Added neutral recovery responses to prevent account enumeration.
- Normalized password-reset token expiration handling to UTC.
- Invalidated reset tokens when email delivery fails.
- Separated Nova administrator translations from frontend language data,
  preventing authentication pages from loading the wrong language context.
- Added server-side sanitization for article rich-text content using an
  explicit HTML allowlist.

### Improved

- Aligned the Nova article editor with the hardened Quill implementation
  developed for Star51 Team.
- Preserved semantic HTML for paragraphs, lists, links, formatting, and code
  blocks when articles are created, edited, and displayed.
- Added a dedicated HTML-to-Quill adapter so existing semantic content can be
  edited without losing list or code-block structure.
- Restricted Quill to the supported formatting set.
- Safely encoded restored editor content and translated JavaScript messages.
- Improved the layout, messages, and navigation of the password recovery
  pages.
- Added dedicated Nova styles for semantic article content.

### Fixed

- Fixed expired rate-limit records remaining active longer than intended.
- Preserved active lockouts and partial attempt counters while removing only
  expired records.
- Made article image replacement atomic: old `max`, `med`, and `min` image
  variants are deleted only after the database update succeeds.
- Prevented failed article updates from deleting the previously stored images.
- Preserved Star51 Solo's intentional `time()`-based image filename policy.

### Compatibility and upgrade notes

- No database migration is required.
- No configuration changes are required.
- Existing article content is not modified in bulk.
- Rich-text sanitization is applied when an article is created or updated.
- Star51 Team-only CSV import features are not included in Star51 Solo.

### Verification

- Star51 Solo regression suite: **85/85 tests passed**.
- PHP syntax validation passed for every changed PHP file.
- Package and credential-cleanliness checks passed.
- Article creation, editing, image replacement, validation errors, Quill
  formatting, and password recovery were functionally tested.

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
