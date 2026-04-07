# Star51 — Free Personal Collection Manager

A lightweight, self-hosted collection manager for individuals. Catalog your movies, books, comics, vinyl records, or anything you collect — with categories, subcategories, images, and multilingual support.

**Star51** is the free edition of the Star51 ecosystem. Simple to install, easy to use, zero recurring costs.

## Features

- **Universal Collections** — Manage any type of collection (films, books, comics, cards, stamps...)
- **Categories & Subcategories** — Organize items with a two-level hierarchy
- **Image Gallery** — Two images per item with automatic resizing (3 sizes)
- **Multilingual** — Italian and English out of the box, easily extensible
- **Responsive Design** — Bootstrap 5, works on desktop and mobile
- **Rich Text Editor** — Quill editor for item descriptions
- **Search** — Full-text search across your collection
- **YouTube Integration** — Embed video links on item detail pages
- **SEO Ready** — Sitemap, robots.txt, Open Graph, JSON-LD structured data
- **Secure** — CSRF protection, prepared statements, rate limiting, security headers

## Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache with mod_rewrite enabled

## Installation

1. Download or clone this repository
2. Upload files to your web server
3. Navigate to `http://your-site/install/`
4. Follow the installation wizard (database, admin account, SMTP)
5. Log in to the admin panel at `http://your-site/nova/`

The wizard generates the configuration file, database tables, and SEO files automatically.

## Project Structure

```
star51/
├── *.php              Frontend pages
├── inc/               Frontend includes (navbar, footer, head, lang)
├── css/ js/ img/      Frontend assets
├── nova/              Admin panel (NovaStar51)
│   ├── conf/          Configuration
│   ├── inc/           Admin includes
│   ├── lang/          i18n JSON files
│   ├── articles/      Article CRUD
│   ├── cat/           Category management
│   ├── admins/        Admin profile
│   └── requests/      Contact request management
├── file_db_max/med/min/  Image uploads (3 sizes)
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
