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

The theme is in **Danish**. All UI copy should remain in Danish.

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
