# IDguard Test Store Theme

## What is this?

A minimal WooCommerce theme built specifically for [teststore.idguard.dk](https://teststore.idguard.dk). The store exists solely to demonstrate and test the **IDguard** plugin — a WooCommerce/Shopify plugin for age verification via MitID.

The theme is intentionally simple: it should look great but stay out of the way. The star of the show is the IDguard plugin, not the theme.

## Architecture

- Pure WordPress theme (no build step, no JS framework, no bundler)
- Single `style.css` with CSS custom properties for design tokens
- WooCommerce templates are handled via `woocommerce.php` (uses `woocommerce_content()`)
- Inter font loaded from Google Fonts
- No sidebar, no widgets, no bloat — WooCommerce UI elements that aren't needed are hidden via CSS
- Demo data system in `inc/demo-data.php` — admin page for importing/resetting 6 test products with bundled images

## Key files

| File | Purpose |
|---|---|
| `style.css` | All styling — design tokens, layout, product grid, cart, checkout |
| `functions.php` | Asset loading, WooCommerce support, cart AJAX fragments, demo data include |
| `header.php` | Sticky dark header with logo + nav |
| `footer.php` | Minimal dark footer |
| `front-page.php` | Hero section + featured products grid |
| `woocommerce.php` | WooCommerce page wrapper |
| `inc/demo-data.php` | Admin page for importing/resetting test products |
| `assets/images/products/` | Bundled product images (6 PNGs) |
| `index.php` | Fallback template |
| `page.php` | Static page template |

## Design principles

- **Minimalist**: No unnecessary UI. Hide WooCommerce elements that add clutter (breadcrumbs, sorting, sidebars, related products, tabs).
- **Dark header/footer, light content area**: Creates visual framing.
- **Accent color `#e94560`**: Used for CTAs, prices, and interactive elements.
- **Mobile-first responsive**: 2-column grid on tablet, 1-column on phone.

## Language

The default theme language remains **Danish**. The public demo also supports
English, Swedish, Norwegian Bokmål, Dutch and Finnish, explicitly requested by
the owner. Keep all six keys synchronized in `inc/translations.php`.

`inc/language.php` selects `?lang=da|en|sv|no|nl|fi`, then a functional session
cookie, then Danish. It switches only storefront requests, including WooCommerce
Store API/AJAX. WordPress admin, login, cron, CLI and unrelated REST requests keep
their original language and unmodified product data. Never change the global
WordPress language, database product fields, currency or verification settings.

Official WordPress/WooCommerce language packs are required, including JSON
catalogs for block checkout. Install them with `scripts/install-languages.sh`
from the WordPress root. Do not pass `--activate`: WPLANG must remain unchanged.

Only the six known demo products are translated at read time. Foreign popup
texts use request-only filters on the five existing merchant-editable text
options. `assets/language.js` narrowly translates the legacy plugin's four exact
Danish alerts and three popup presentation labels. Unknown messages and native
alert behavior pass through unchanged. Never alter authentication, verification
state, tokens or the genuine Danish MitID login window.

Run `php tests/language.php`, `node tests/presentation.mjs`, PHP lint and native
WooCommerce browser tests before release. `README.md` describes deployment and
rollback. A push to this repository does not prove the live theme was updated.

## Test products

Six test products are bundled with the theme and can be imported from **Udseende → IDguard Testdata** in WP admin:

| Product | Price | Why it's here |
|---|---|---|
| Rødvin - Château Test 2020 | 149 kr | Alcohol — requires age verification |
| Whisky - Single Malt Test | 399 kr | Alcohol — requires age verification |
| Økologisk Æblejuice | 49 kr | No restriction — control product |
| CBD Olie 10% | 299 kr | CBD — requires age verification |
| Gavekort 500 kr | 500 kr | Gift card — no restriction |
| E-cigaret Startkit | 199 kr | Tobacco/nicotine — requires age verification |
