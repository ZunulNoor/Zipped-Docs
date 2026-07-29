<?php

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', 'doc_vista_add_meta_box' );
function doc_vista_add_meta_box() {
    add_meta_box(
        'doc_vista_doc_settings',
        __( 'Doc Settings', 'doc-vista' ),
        'doc_vista_render_meta_box',
        'doc_vista_doc',
        'side',
        'high'
    );
}

function doc_vista_render_meta_box( $post ) {
    wp_nonce_field( 'doc_vista_save_doc_settings', 'doc_vista_doc_settings_nonce' );

    $categories = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    $current_categories = wp_get_post_terms( $post->ID, 'doc_vista_category', array( 'fields' => 'ids' ) );
    $current_order      = get_post_meta( $post->ID, '_doc_vista_order', true );
    ?>
    <style>
        #doc_vista_doc_settings .doc-vista-mb-field {
            margin-bottom: 14px;
        }
        #doc_vista_doc_settings .doc-vista-mb-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 12px;
            text-transform: uppercase;
            color: #50575e;
        }
        #doc_vista_doc_settings .doc-vista-mb-field select,
        #doc_vista_doc_settings .doc-vista-mb-field input[type="number"] {
            width: 100%;
        }
    </style>

    <div class="doc-vista-mb-field">
        <label for="doc-vista-mb-category"><?php esc_html_e( 'Category', 'doc-vista' ); ?></label>
        <select id="doc-vista-mb-category" name="doc_vista_category">
            <option value=""><?php esc_html_e( '\u2014 Select Category \u2014', 'doc-vista' ); ?></option>
            <?php foreach ( $categories as $c ) : ?>
                <option value="<?php echo esc_attr( $c->term_id ); ?>" <?php selected( in_array( $c->term_id, $current_categories, true ) ); ?>>
                    <?php echo esc_html( $c->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="doc-vista-mb-field">
        <label for="doc-vista-mb-order"><?php esc_html_e( 'Order', 'doc-vista' ); ?></label>
        <input
            type="number"
            id="doc-vista-mb-order"
            name="doc_vista_order"
            value="<?php echo esc_attr( $current_order ?: '0' ); ?>"
            min="0"
            step="1"
        />
        <p class="description" style="margin:2px 0 0;font-size:11px;">
            <?php esc_html_e( 'Lower numbers appear first.', 'doc-vista' ); ?>
        </p>
    </div>
    <?php
}

add_action( 'save_post', 'doc_vista_save_meta_box' );
function doc_vista_save_meta_box( $post_id ) {

    if ( ! isset( $_POST['doc_vista_doc_settings_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( wp_unslash( $_POST['doc_vista_doc_settings_nonce'] ), 'doc_vista_save_doc_settings' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( 'doc_vista_doc' !== ( wp_unslash( $_POST['post_type'] ?? '' ) ) ) {
        return;
    }

    $category_id = (int) ( wp_unslash( $_POST['doc_vista_category'] ?? 0 ) );
    if ( $category_id && term_exists( $category_id, 'doc_vista_category' ) ) {
        wp_set_object_terms( $post_id, array( $category_id ), 'doc_vista_category' );
    } else {
        wp_set_object_terms( $post_id, array(), 'doc_vista_category' );
    }

    $order = (int) ( wp_unslash( $_POST['doc_vista_order'] ?? 0 ) );
    update_post_meta( $post_id, '_doc_vista_order', max( 0, $order ) );
}
