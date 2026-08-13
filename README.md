# Zipped Docs

**Version:** 3.0.1  
**Author:** Zun Ul Noor  
**URI:** [https://zunulnoor.vercel.app](https://zunulnoor.vercel.app)  
**License:** GPL-2.0+  
**Requires at least:** WordPress 5.8  
**Tested up to:** WordPress 6.7  
**Requires PHP:** 7.4+

---

A standalone documentation CMS for WordPress that lets you create, manage, and display product documentation — powered by custom post types, taxonomies, client-side search, a sticky table of contents, a ChatGPT-style navigation rail, and a fully themeable frontend. No page builders, no jQuery, no external dependencies.

---

## Features

- **Custom Post Type** (`zipped_docs_doc`) — dedicated docs content type, independent of WordPress Pages
- **Category Taxonomies** (`zipped_docs_category`) — organize docs into sections (Getting Started, Guides, Troubleshooting) with default "General" category protection
- **Precomputed Doc Graph** — hierarchical tree + inverted search index cached in `wp_options`, zero-latency frontend
- **Client-Side Search** — instant suggestions with keyboard navigation, in-content highlighting via TreeWalker, TOC filtering, REST API fallback for large indexes
- **Hierarchical Table of Contents** — auto-generated from heading tags, collapsible sections, scroll-spy with IntersectionObserver
- **Navigation Rail** — fixed-position floating rail with group indicators, hover preview panel, and dynamic content-boundary visibility (never overlaps footer)
- **Admin Dashboard** — stats cards, category filter, paginated doc table with Edit/View/Delete
- **Categories CRUD** — add, rename, delete categories with nonce verification and default category protection
- **Tabbed Settings Panel** — 8 tabs: appearance, typography, layout, TOC colors, highlight, behavior, display, advanced
- **Meta Box** — assign category and custom order directly in the Gutenberg/post editor
- **Dynamic CSS** — settings-driven CSS custom properties injected inline, no rebuild required
- **Reading Progress Bar** — optional per-chapter progress indicator at the top of each doc
- **Breadcrumbs** — automatic category > doc navigation
- **Prev / Next Navigation** — sequential doc navigation through the category tree
- **Related Articles** — auto-suggested same-category docs
- **Chapter Engine** — H1-based chapter management with smooth transitions, URL hash updates, and independent scroll tracking
- **REST API** — `GET /wp-json/zipped-docs/v1/search?q=term&product=slug`
- **Centralized Settings** — singleton service class with lazy loading, per-key sanitization, backward compatible
- **Version Upgrade Support** — automated migration callbacks (1.0.0 → 2.0.0+)
- **Uninstall Cleanup** — full data removal on plugin deletion, with `ZIPPED_DOCS_PRESERVE_DATA` constant or settings toggle
- **Deactivation Flow** — branded modal with Keep Data / Remove All Data options
- **Full CSS Namespace Isolation** — all styles scoped under `.zipped-docs`, zero conflicts with themes or page builders
- **Mobile TOC** — floating trigger card with overlay panel, backdrop blur, body scroll lock, independent scrolling, smooth animations
- **Google Font Support** — choose between theme-inherited font or any Google Font via settings
- **Mobile Responsive** — collapsible sidebar drawer, full-width content on smaller screens, adaptive nav rail
- **Accessibility** — ARIA labels, keyboard-navigable search and modals, focus management, reduced-motion support, screen reader compatibility
- **No Dependencies** — zero jQuery, zero external libraries, zero page builders

---

## Installation

1. Upload the `zipped-docs` folder to `/wp-content/plugins/` or install via WordPress plugin admin
2. Activate **Zipped Docs** from the WordPress **Plugins** screen
3. Seed terms (products & categories) are created automatically on activation
4. Go to **Zipped Docs → Add New** to create your first documentation article
5. Insert the shortcode on any page or post

---

## Usage

### Shortcode

```
[zippeddocs product="category-slug"]
```

The `[zipped_docs]` tag is also supported as an alias.

| Attribute    | Description                                          | Default |
| ------------ | ---------------------------------------------------- | ------- |
| `product`    | Product slug — filters docs by `zipped_docs_product` term | —       |
| `doc_id`     | Specific doc ID to display                         | —       |
| `toc_depth`  | Maximum heading depth for TOC (2–6)                | `6`     |

If no `product` is given and no `doc_id` is specified, a fallback message is displayed.

### Admin Menu

| Menu Item      | Description                              |
| -------------- | ---------------------------------------- |
| Zipped Docs      | Dashboard — stats, filters, doc table    |
| Add New        | Quick-create form or link to Gutenberg   |
| Categories     | Manage doc categories (CRUD)             |
| Settings       | Tabbed settings panel                    |

### Post Type & Taxonomies

| Name                | Slug                    | REST Base             |
| ------------------- | ----------------------- | --------------------- |
| Docs (CPT)          | `zipped_docs_doc`         | `zipped-docs-docs`      |
| Doc Categories      | `zipped_docs_category`    | `zipped-docs-categories` |
| Products            | `zipped_docs_product`     | `zipped-docs-products`   |

---

## Frequently Asked Questions

### Can I use this alongside other documentation plugins?

Yes. Zipped Docs uses its own custom post type (`zipped_docs_doc`) and does not interfere with WordPress Pages or other post types.

### Is the frontend compatible with any theme?

Yes. The shortcode only modifies the content area — your theme's header, footer, and sidebar remain untouched. CSS is scoped to `.zipped-docs-*` classes.

### Does search require a server request?

No. Search is fully client-side for small-to-medium doc sets. For large indexes (1000+ docs), the plugin automatically falls back to the REST API search endpoint.

### How do I add a new product?

Products are terms in the `zipped_docs_product` taxonomy. You can add them via Zipped Docs → Products or programmatically.

### Will my data be preserved if I delete the plugin?

By default, all plugin data is removed on deletion. To preserve data, define `define('ZIPPED_DOCS_PRESERVE_DATA', true);` in your `wp-config.php`.

---

## Changelog

### 3.0.1
- **Security** — import decisions and document data re-validated server-side so tampered payloads cannot bypass validation; media import URLs restricted to http/https; document status whitelisted on create/update
- **Fix** — replace/update now verify the referenced post exists and is a `zipped_docs_doc` before modifying it
- **Security** — inline admin `<style>`/`<script>` blocks moved into enqueued assets (`admin.css`, `admin.js`, `wp_add_inline_style`); settings input unslashed before sanitization
- **Version Bump** — 3.0.1

### 3.0.0
- **Rebrand** — released as **Zipped Docs**: plugin slug `zipped-docs`, text domain `zipped-docs`, PHP prefix `zipped_docs_`, and `ZIPPED_DOCS_*` constants
- **Doc URL Parameter** — frontend doc navigation uses `?zipped_docs=`
- **Import/Export Security** — real `check_ajax_referer()` in upload and preview handlers, per-field validation of decoded import decisions and doc IDs, `intval`/`filter_var` sanitization
- **Plugin Check Compliance** — addresses WordPress.org review issues (escaping, i18n, filesystem, capability checks)

### 2.2.0
- **Navigation Rail** — new ChatGPT-style side rail with dot indicators, hover preview panel, smooth slide transitions, dynamic content-boundary detection (never overlaps footer/related content)
- **Activation Redirect** — automatic redirect to Zipped Docs Dashboard after plugin activation
- **Deactivation Flow** — branded modal dialog with Keep Data / Remove All Data options and AJAX preference storage
- **Admin Modal System** — custom `ZippedDocsPopup` with alert, confirm, and deactivation flows, replaces native `confirm()` across admin
- **Custom Capabilities** — new `zipped_docs_editor` role, fine-grained `zipped_docs_create`/`zipped_docs_read`/`zipped_docs_edit`/`zipped_docs_delete`/`zipped_docs_manage_settings` caps
- **Security Hardening** — stored XSS fix in search suggestions (`escapeHtml` before regex), nonce sanitization with `sanitize_key()`, POST filtering via `array_intersect_key()`, output escaping with `wp_kses_post()`
- **Memory Leak Fixes** — resolved keydown listener accumulation in admin modals, ChapterEngine callback arrays reset on init
- **Performance** — N+1 database query elimination in graph builder (single `WP_Query` instead of per-category queries), dashboard pagination (20 per page), `wp_count_posts()` for stats
- **Dashboard Pagination** — doc table now paginated with `paginate_links()`, safe for thousands of docs
- **Dead Code Removal** — removed unused `get_default()` method, empty `doc_index` field from graph, orphaned transient cleanup
- **CSS Slide Transitions** — nav rail hidden state uses `translateX(10px)` with smooth `transform 300ms` transition

### 2.1.0
- **Full CSS Namespace Isolation** — every selector prefixed under `.zipped-docs`, no leakage to/from themes or plugins
- **JavaScript Isolation** — all DOM queries scoped to plugin wrapper, delegated events, no globals
- **Premium Mobile TOC Redesign** — sticky floating trigger card, overlay panel with backdrop blur, body scroll lock, independent scrolling, smooth open/close animations
- **Premium Mobile TOC Search** — search field fixed at top of overlay panel, only TOC list scrolls
- **Active Heading Tracking** — active TOC item auto-scrolls into view in the mobile panel
- **Admin Bar Namespace** — `.admin-bar` dependency replaced with scoped `.zipped-docs-has-admin-bar` class
- **Google Font Integration** — new Typography setting to inherit theme font or load a Google Font with automatic enqueueing
- **Refined Mobile Typography** — adjusted mobile heading sizes (H1: 28px, H2: 26px, H3: 24px, H4: 22px, H5: 20px, H6: 18px, P: 14px)

### 2.0.0
- **Precomputed Documentation Graph** — hierarchical doc tree + inverted search index, zero heavy queries on frontend
- **Ranked Inverted Index Search** — fuzzy matching, partial input (≥2 chars), weighted results (title 5×, heading 3×, content 1×)
- **Instant Search Suggestions** — dropdown with keyboard navigation, AJAX fallback for large indexes
- **Hierarchical TOC** — collapsible nested sections, scroll-spy with IntersectionObserver
- **REST API Search Endpoint** — `GET /wp-json/zipped-docs/v1/search`
- **Breadcrumb, Prev/Next, Related Articles** — auto-generated navigation
- **Reading Progress Bar** — optional top-of-page progress indicator
- **Centralized Settings Service** — `Zipped_Docs_Settings` singleton, lazy-loaded
- **Version Upgrade Support** — automated migration callbacks
- **Uninstall Cleanup** — full data removal with `ZIPPED_DOCS_PRESERVE_DATA` constant
- **Security Hardening** — PHP 8.0+ multibyte safety, escaped dynamic CSS, single `wp_localize_script`
- **Accessibility** — `aria-expanded` on toggles, keyboard-only focus indicators, hash-activated scroll-spy
- **WordPress.org Submission** — `readme.txt`, coding standards compliance
- **Performance** — precomputed graph eliminates dynamic queries

### 1.0.0
- Initial release
- Custom post type `zipped_docs_doc` with REST API support
- `zipped_docs_product` and `zipped_docs_category` taxonomies
- Shortcode `[zippeddocs]` with product and doc_id filters
- Client-side search with TreeWalker-based highlight engine
- Auto-generated table of contents with scroll spy
- Admin dashboard with stats, filters, and doc management
- Categories CRUD page
- Settings panel
- Doc Settings meta box
- Transient caching
- Dynamic CSS via CSS custom properties
- Mobile-responsive layout
- Seed terms: Default for now

---

## Development

The plugin follows WordPress coding standards and is compatible with PHP 7.4+.

### File Structure

```
zipped-docs/
├── assets/
│   ├── zipped-docs-admin.css    # Admin dashboard, settings, modal, & deactivation styles
│   ├── zipped-docs-admin.js     # Admin JS (modal popup, delete confirmations, deactivation flow)
│   ├── zipped-docs.css          # Frontend documentation styles (21+ sections, responsive)
│   └── zipped-docs.js           # Frontend JS (ChapterEngine, TocBuilder, ScrollSpy, NavRail, Search)
├── includes/
│   ├── class-settings.php     # Centralized settings service (Zipped_Docs_Settings singleton)
│   ├── class-capabilities.php # Custom roles and capability registration
│   ├── post-type.php          # CPT & taxonomy registration, default category protection
│   ├── doc-graph.php          # Precomputed doc graph builder & inverted search index
│   ├── shortcode.php          # Shortcode handler, JS config localization, layout rendering
│   ├── admin-dashboard.php    # Dashboard with stats cards, category filter, paginated doc table
│   ├── admin-new-doc.php      # Add New Doc quick-create form
│   ├── admin-categories.php   # Categories CRUD admin page with delete protection
│   ├── admin-settings.php     # Tabbed settings panel (8 tabs), cache rebuild
│   └── admin-meta-box.php     # Doc Settings meta box for Gutenberg editor
├── templates/
│   └── layout.php             # Frontend two-column layout template with dynamic CSS vars
├── uninstall.php              # Full data cleanup on plugin deletion (preserve-aware)
├── readme.txt                 # WordPress.org plugin readme
├── README.md
└── zipped-docs.php              # Main plugin bootstrap, activation, REST API, AJAX, helpers
```

---

## License

This plugin is licensed under the GPL-2.0+ license.
