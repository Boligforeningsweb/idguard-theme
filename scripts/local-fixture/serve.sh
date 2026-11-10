#!/usr/bin/env bash
# Re-open the already-built isolated fixture without resetting its synthetic data.
set -euo pipefail
fixture_root=/tmp/idguard-teststore-localization
fixture_here="$(cd "$(dirname "$0")" && pwd)"
theme_root="$(cd "$fixture_here/../.." && pwd)"
fixture_cli="${PLAYGROUND_CLI:-/tmp/idguard-video/playground-cli/node_modules/.bin/wp-playground-cli}"
[[ -f "$fixture_root/site/wp-config.php" ]] || { echo 'Run setup.sh first.' >&2; exit 1; }
exec "$fixture_cli" server --port="${PORT:-18441}" --wp=7.1.4 --php=8.3 \
    --mount-before-install="$fixture_root/site:/wordpress" \
    --mount="$fixture_root/build:/setup" \
    --mount="$theme_root:/wordpress/wp-content/themes/idguard-shop" \
    --wordpress-install-mode=do-not-attempt-installing
