#!/usr/bin/env bash
# Run from the WordPress root (for example Forge -> teststore -> Commands).
# Installs official catalogs only; does not change WPLANG, products or IDguard settings.
set -euo pipefail
command -v wp >/dev/null || { echo 'WP-CLI is required. No changes made.' >&2; exit 1; }
wp --skip-plugins --skip-themes core is-installed
before_language="$(wp --skip-plugins --skip-themes option get WPLANG)"
theme_directory="$(wp --skip-plugins --skip-themes eval 'echo get_stylesheet_directory();')"

wp --skip-plugins --skip-themes language core install da_DK en_GB sv_SE nb_NO nl_NL fi
wp --skip-plugins --skip-themes language plugin install woocommerce da_DK en_GB sv_SE nb_NO nl_NL fi

[ "$(wp --skip-plugins --skip-themes option get WPLANG)" = "$before_language" ] || {
    echo 'The site language changed unexpectedly; review before continuing.' >&2
    exit 1
}
wp --skip-plugins --skip-themes eval-file "$theme_directory/scripts/check-language-readiness.php"
