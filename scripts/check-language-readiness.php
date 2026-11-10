<?php
/** Read-only readiness check, executed with `wp eval-file`. */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    if ( PHP_SAPI !== 'cli' ) http_response_code( 404 );
    echo "Run with wp eval-file from the WordPress root.\n";
    exit( 1 );
}
$missing = [];
foreach ( [ 'da_DK', 'en_GB', 'sv_SE', 'nb_NO', 'nl_NL', 'fi' ] as $locale ) {
    if ( ! is_file( WP_LANG_DIR . '/' . $locale . '.mo' ) && ! is_file( WP_LANG_DIR . '/' . $locale . '.l10n.php' ) ) $missing[] = 'core:' . $locale;
    if ( ! is_file( WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo' ) && ! is_file( WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.l10n.php' ) ) $missing[] = 'woocommerce:' . $locale;
    if ( ! glob( WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '-*.json' ) ) $missing[] = 'woocommerce-blocks:' . $locale;
}
if ( $missing ) WP_CLI::error( 'Missing official catalogs: ' . implode( ', ', $missing ) );
WP_CLI::success( 'All six core/WooCommerce catalogs and block-checkout translation files are present. Default site language remains ' . get_option( 'WPLANG', '' ) . '.' );
