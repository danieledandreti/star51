# Star51 — Free Personal Collection Manager

[![Latest release](https://img.shields.io/github/v/release/danieledandreti/star51?display_name=tag&sort=semver)](https://github.com/danieledandreti/star51/releases/latest)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](#requirements)
[![License: GPL v3](https://img.shields.io/badge/License-GPL_v3-blue.svg)](LICENSE)

A lightweight, self-hosted collection manager for individuals. Catalog your movies, books, comics, vinyl records, or anything you collect — with categories, subcategories, images, and multilingual support.

**Star51** is the free edition of the Star51 ecosystem. Simple to install, easy to use, zero recurring costs.

**Current stable release: [v1.2.1](https://github.com/danieledandreti/star51/releases/tag/v1.2.1)** — consolidated search documentation. Application behavior is unchanged from v1.2.0. No database migration is required.

[Release notes](https://github.com/danieledandreti/star51/releases/latest) · [Changelog](CHANGELOG.md)

See the [release notes and search behavior](RELEASE_NOTES.md).

## Features

- **Universal Collections** — Manage any type of collection (films, books, comics, cards, stamps...)
- **Categories & Subcategories** — Organize items with a two-level hierarchy
- **Image Gallery** — Two images per item with automatic resizing (3 sizes)
- **Multilingual** — Italian and English out of the box, easily extensible
- **Responsive Design** — Bootstrap 5, works on desktop and mobile
- **Rich Text Editor** — Quill editor for item descriptions
- **Search** — All-word matching across titles and summaries, short-title support and newest-year-first results
- **YouTube Integration** — Embed video links on item detail pages
- **SEO Ready** — Sitemap, robots.txt, Open Graph, JSON-LD structured data
- **Secure** — Isolated admin sessions, CSRF protection, prepared statements, rate limiting, and security headers

## What's New in v1.2.1

- Search explanations are consolidated in the bilingual release notes.
- Documentation-only update; no application code, configuration or database changes.

## Search Improvements in v1.2.0

- Search titles and summaries using all requested words, in any order.
- Find short titles such as IT and Up with whole-word matching.
- Treat punctuation and hyphens as word separators: x-men and x men are equivalent.
- Order public results by item year descending, then title and ID.
- No database migration. Preserve configuration and uploaded images when upgrading.

See [release notes](RELEASE_NOTES.md) for compatibility, behavior changes and validation.

## Previous Release: v1.1.0

- **Isolated Nova Sessions** — A dedicated, portable admin session protects login, recovery, authenticated pages, and logout across different installation paths.
- **Safer Contact Form** — Explicit field limits, a live 2500-character counter, UTF-8-aware trimming, and clear visitor feedback.
- **Improved Mobile Administration** — Profile editing, language selection, and logout are grouped in one compact mobile menu.
- **Clearer System Diagnostics** — Installation files and the persistent `.installed` safety lock are reported separately.
- **Protected Sensitive Directories** — Direct HTTP access to `nova/conf` and `nova/logs` is denied by distributed Apache rules.
- **Straightforward Upgrade** — No database migration is required. Administrators sign in again once after upgrading because Nova now uses its own session cookie.

See the [v1.1.0 release notes](https://github.com/danieledandreti/star51/releases/tag/v1.1.0) for the complete technical summary.

## Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled

## Installation

1. Download or clone this repository
2. Upload files to your web server
3. Navigate to `http://your-site/install/`
4. Follow the installation wizard (database, administrator account, optional Gmail SMTP)
5. Log in to the admin panel at `http://your-site/nova/`

The wizard generates the configuration file, database tables, and SEO files automatically.

## Project Structure

```
star51/
├── *.php              Frontend pages
├── inc/               Frontend includes (navbar, footer, head, lang)
├── css/ js/ img/      Frontend assets
├── favicon/           Browser icons and web app manifest
├── nova/              Admin panel (NovaStar51)
│   ├── conf/ inc/     Configuration and shared admin includes
│   ├── lang/          Admin and frontend i18n JSON files
│   ├── articles/      Article CRUD
│   ├── cat/ subcat/   Category and subcategory management
│   ├── admins/        Solo administrator profile management
│   ├── requests/      Contact request management
│   ├── system/        System configuration and maintenance
│   ├── legas/         Legal content configuration
│   ├── logs/          Protected application logs
│   ├── mail-templates/  Localized email templates
│   └── css/ js/       Admin assets
├── file_db_max/       Original uploaded images
├── file_db_med/       Medium generated images
├── file_db_min/       Thumbnail generated images
└── install/           Installation wizard
```

## Star51 Editions

| Edition | Users | Price | License |
|---------|-------|-------|---------|
| **Star51** (this) | Single user | Free | GPL v3 |
| **Star51 Team** | Multi-user | Coming soon | Coming soon |

## Support the Project

Star51 is free and open source. If you find it useful, consider supporting its development:

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/G2G81UUL2W)

[![PayPal](https://img.shields.io/badge/PayPal-Tip-blue?logo=paypal)](https://www.paypal.com/paypalme/danieledandreti)

## License

This project is licensed under the **GNU General Public License v3.0** — see the [LICENSE](LICENSE) file for details.

You are free to use, modify, and redistribute this software under the terms of the GPL v3. If you distribute modified versions, you must release the source code under the same license.

## Author

Created by **Daniele D'Andreti**

---

[danieledandreti.github.io/star51](https://danieledandreti.github.io/star51/) — *Part of the Star51 collection management ecosystem.*
