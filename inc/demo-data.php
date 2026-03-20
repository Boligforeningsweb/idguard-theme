<?php
/**
 * IDguard Demo Data — import/reset test products from WP admin.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The six test products bundled with the theme.
 */
function idguard_get_demo_products() {
    return [
        [
            'name'        => 'Rødvin - Château Test 2020',
            'price'       => '149',
            'description' => 'Fransk rødvin, årgang 2020. Aldersverifikation påkrævet.',
            'image'       => 'rodvin.jpg',
        ],
        [
            'name'        => 'Whisky - Single Malt Test',
            'price'       => '399',
            'description' => 'Single malt whisky til test. Kræver aldersverifikation via MitID.',
            'image'       => 'whisky.jpg',
        ],
        [
            'name'        => 'Økologisk Æblejuice',
            'price'       => '49',
            'description' => 'Friskpresset dansk økologisk æblejuice, 750 ml.',
            'image'       => 'ablejuice.jpg',
        ],
        [
            'name'        => 'CBD Olie 10%',
            'price'       => '299',
            'description' => 'Fuldspektrum CBD olie, 10 ml. Kræver aldersverifikation ved køb.',
            'image'       => 'cbd-olie.jpg',
        ],
        [
            'name'        => 'Gavekort 500 kr',
            'price'       => '500',
            'description' => 'Digitalt gavekort til butikken. Kan bruges på alle produkter.',
            'image'       => 'gavekort.jpg',
        ],
        [
            'name'        => 'E-cigaret Startkit',
            'price'       => '199',
            'description' => 'Komplet startkit med e-væske. Kun til personer over 18 år.',
            'image'       => 'e-cigaret.jpg',
        ],
    ];
}

/**
 * Register admin page under Appearance.
 */
function idguard_demo_admin_menu() {
    add_theme_page(
        'IDguard Testdata',
        'IDguard Testdata',
        'manage_woocommerce',
        'idguard-demo-data',
        'idguard_demo_admin_page'
    );
}
add_action( 'admin_menu', 'idguard_demo_admin_menu' );

/**
 * Handle form submissions.
 */
function idguard_demo_handle_actions() {
    if ( ! isset( $_POST['idguard_demo_action'] ) ) return;
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    check_admin_referer( 'idguard_demo_data' );

    $action = sanitize_text_field( $_POST['idguard_demo_action'] );

    if ( $action === 'import' ) {
        $count = idguard_import_demo_products();
        set_transient( 'idguard_demo_notice', sprintf( '%d produkter oprettet.', $count ), 30 );
    } elseif ( $action === 'reset' ) {
        idguard_delete_demo_products();
        $count = idguard_import_demo_products();
        set_transient( 'idguard_demo_notice', sprintf( 'Shop nulstillet. %d produkter oprettet.', $count ), 30 );
    } elseif ( $action === 'delete' ) {
        $deleted = idguard_delete_demo_products();
        set_transient( 'idguard_demo_notice', sprintf( '%d produkter slettet.', $deleted ), 30 );
    }

    wp_safe_redirect( admin_url( 'themes.php?page=idguard-demo-data' ) );
    exit;
}
add_action( 'admin_init', 'idguard_demo_handle_actions' );

/**
 * Import demo products and attach bundled images.
 */
function idguard_import_demo_products() {
    if ( ! class_exists( 'WC_Product_Simple' ) ) return 0;

    @set_time_limit( 120 );

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $products = idguard_get_demo_products();
    $created  = 0;

    foreach ( $products as $data ) {
        $product = new WC_Product_Simple();
        $product->set_name( $data['name'] );
        $product->set_regular_price( $data['price'] );
        $product->set_short_description( $data['description'] );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->save();

        // Attach image
        $image_path = get_template_directory() . '/assets/images/products/' . $data['image'];
        if ( file_exists( $image_path ) ) {
            $attachment_id = idguard_sideload_image( $image_path, $product->get_id() );
            if ( $attachment_id ) {
                $product->set_image_id( $attachment_id );
                $product->save();
            }
        }

        // Tag as demo product so we can clean up later
        update_post_meta( $product->get_id(), '_idguard_demo', '1' );
        $created++;
    }

    return $created;
}

/**
 * Copy a local file into the media library and attach it to a post.
 */
function idguard_sideload_image( $file_path, $post_id ) {
    $upload_dir = wp_upload_dir();
    $filename   = wp_unique_filename( $upload_dir['path'], basename( $file_path ) );
    $new_path   = $upload_dir['path'] . '/' . $filename;

    if ( ! copy( $file_path, $new_path ) ) return 0;

    $filetype = wp_check_filetype( $filename );

    $attachment = [
        'guid'           => $upload_dir['url'] . '/' . $filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment( $attachment, $new_path, $post_id );
    $meta      = wp_generate_attachment_metadata( $attach_id, $new_path );
    wp_update_attachment_metadata( $attach_id, $meta );

    return $attach_id;
}

/**
 * Delete all products tagged as demo data.
 */
function idguard_delete_demo_products() {
    $demo_ids = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_idguard_demo',
        'meta_value'     => '1',
    ]);

    foreach ( $demo_ids as $id ) {
        // Delete attached images
        $thumb_id = get_post_thumbnail_id( $id );
        if ( $thumb_id ) wp_delete_attachment( $thumb_id, true );

        wp_delete_post( $id, true );
    }

    return count( $demo_ids );
}

/**
 * Render admin page.
 */
function idguard_demo_admin_page() {
    $notice = get_transient( 'idguard_demo_notice' );
    if ( $notice ) delete_transient( 'idguard_demo_notice' );

    $existing = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_idguard_demo',
        'meta_value'     => '1',
    ]);
    $has_demo = ! empty( $existing );
    ?>
    <div class="wrap">
        <h1>IDguard Testdata</h1>
        <p>Opret eller nulstil de 6 testprodukter der følger med IDguard-temaet.</p>

        <?php if ( $notice ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
        <?php endif; ?>

        <?php if ( $has_demo ) : ?>
            <div class="notice notice-info"><p>Der er aktuelt demo-produkter i shoppen.</p></div>
        <?php endif; ?>

        <div style="display:flex;gap:20px;margin-top:20px;">
            <form method="post">
                <?php wp_nonce_field( 'idguard_demo_data' ); ?>
                <input type="hidden" name="idguard_demo_action" value="import">
                <button type="submit" class="button button-primary button-hero">
                    Opret testprodukter
                </button>
                <p class="description">Opretter 6 produkter med billeder, priser og beskrivelser.<br>Eksisterende produkter påvirkes ikke.</p>
            </form>
            <form method="post">
                <?php wp_nonce_field( 'idguard_demo_data' ); ?>
                <input type="hidden" name="idguard_demo_action" value="reset">
                <button type="submit" class="button button-hero"
                        onclick="return confirm('Er du sikker? Alle eksisterende testprodukter slettes og genoprettes. Øvrige produkter påvirkes ikke.');">
                    Nulstil testdata
                </button>
                <p class="description">Sletter alle testprodukter og genopretter dem.<br>Øvrige produkter påvirkes ikke.</p>
            </form>
            <form method="post">
                <?php wp_nonce_field( 'idguard_demo_data' ); ?>
                <input type="hidden" name="idguard_demo_action" value="delete">
                <button type="submit" class="button button-hero"
                        onclick="return confirm('Er du sikker? Alle testprodukter slettes permanent. Øvrige produkter påvirkes ikke.');">
                    Slet testprodukter
                </button>
                <p class="description">Fjerner kun testprodukter og deres billeder.<br>Øvrige produkter påvirkes ikke.</p>
            </form>
        </div>

        <h2 style="margin-top:40px;">Inkluderede testprodukter</h2>
        <table class="widefat striped" style="max-width:700px;">
            <thead>
                <tr><th>Produkt</th><th>Pris</th><th>Beskrivelse</th></tr>
            </thead>
            <tbody>
                <?php foreach ( idguard_get_demo_products() as $p ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $p['name'] ); ?></strong></td>
                    <td><?php echo esc_html( $p['price'] ); ?> kr</td>
                    <td><?php echo esc_html( $p['description'] ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
