<?php
/** Local fixture isolation: no emails, authentication or financial/network side effects. */
if (!defined('ABSPATH')) exit;
add_filter('pre_wp_mail', '__return_false', 1);
add_filter('pre_transient_idguard_endpoint_status', static function () { return 'available'; });
add_filter('pre_http_request', static function ($pre, $args, $url) {
    if (in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost'], true)) return $pre;
    return new WP_Error('teststore_fixture_offline', 'External PHP HTTP is disabled in this isolated fixture.');
}, 1, 3);
add_filter('automatic_updater_disabled', '__return_true');
add_filter('pre_site_transient_update_core', '__return_null');
add_filter('pre_site_transient_update_plugins', '__return_null');
add_filter('pre_site_transient_update_themes', '__return_null');
