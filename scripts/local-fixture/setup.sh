#!/usr/bin/env bash
# Build an isolated WordPress/WooCommerce teststore; never touches production.
set -euo pipefail
fixture_root=/tmp/idguard-teststore-localization
fixture_here="$(cd "$(dirname "$0")" && pwd)"
theme_root="$(cd "$fixture_here/../.." && pwd)"
fixture_cli="${PLAYGROUND_CLI:-/tmp/idguard-video/playground-cli/node_modules/.bin/wp-playground-cli}"
fixture_plugin_zip="${IDGUARD_PLUGIN_ZIP:-/tmp/idguard-video/idguard-app/plugins/wordpress/idguard.zip}"
fixture_port="${PORT:-18441}"
[[ -x "$fixture_cli" ]] || { echo 'Set PLAYGROUND_CLI to a pinned @wp-playground/cli executable.' >&2; exit 1; }
[[ -f "$fixture_plugin_zip" ]] || { echo 'Set IDGUARD_PLUGIN_ZIP to the released 3.3.6 package.' >&2; exit 1; }
[[ "$fixture_port" =~ ^[0-9]+$ ]] || { echo 'PORT must be numeric.' >&2; exit 1; }
unzip -p "$fixture_plugin_zip" idguard/idguard.php | sed -n '1,20p' | grep -q 'Version: *3.3.6' || {
    echo 'The fixture requires the released 3.3.6 plugin, not an unreleased source tree.' >&2; exit 1;
}
mkdir -p "$fixture_root/build" "$fixture_root/site"
if [[ -f "$fixture_root/site/wp-config.php" ]]; then
    echo "Existing fixture at $fixture_root/site. Use serve.sh; remove this isolated directory explicitly to rebuild." >&2
    exit 1
fi
cp "$fixture_here/blueprint.json" "$fixture_here/setup.php" "$fixture_here/isolation.php" "$fixture_here/cart-content.html" "$fixture_root/build/"
cp "$fixture_plugin_zip" "$fixture_root/build/idguard.zip"
curl -fL --retry 2 -o "$fixture_root/build/woocommerce.zip" https://downloads.wordpress.org/plugin/woocommerce.10.6.1.zip
cd "$fixture_root/build"
exec "$fixture_cli" server --port="$fixture_port" --wp=7.1.4 --php=8.3 \
    --mount-before-install="$fixture_root/site:/wordpress" \
    --mount="$fixture_root/build:/setup" \
    --mount="$theme_root:/wordpress/wp-content/themes/idguard-shop" \
    --blueprint="$fixture_root/build/blueprint.json" --blueprint-may-read-adjacent-files
