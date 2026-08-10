<?php

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', 'zipped_docs_add_meta_box' );
function zipped_docs_add_meta_box() {
    add_meta_box(
        'zipped_docs_doc_settings',
        __( 'Doc Settings', 'zipped-docs' ),
        'zipped_docs_render_meta_box',
        'zipped_docs_doc',
        'side',
        'high'
    );
}

function zipped_docs_render_meta_box( $post ) {
    wp_nonce_field( 'zipped_docs_save_doc_settings', 'zipped_docs_doc_settings_nonce' );

    $categories = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    $current_categories = wp_get_post_terms( $post->ID, 'zipped_docs_category', array( 'fields' => 'ids' ) );
    $current_order      = get_post_meta( $post->ID, '_zipped_docs_order', true );
    ?>
    <style>
        #zipped_docs_doc_settings .zipped-docs-mb-field {
            margin-bottom: 14px;
        }
        #zipped_docs_doc_settings .zipped-docs-mb-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 12px;
            text-transform: uppercase;
            color: #50575e;
        }
        #zipped_docs_doc_settings .zipped-docs-mb-field select,
        #zipped_docs_doc_settings .zipped-docs-mb-field input[type="number"] {
            width: 100%;
        }
    </style>

    <div class="zipped-docs-mb-field">
        <label for="zipped-docs-mb-category"><?php esc_html_e( 'Category', 'zipped-docs' ); ?></label>
        <select id="zipped-docs-mb-category" name="zipped_docs_category">
                <option value=""><?php esc_html_e( "\u{2014} Select Category \u{2014}", 'zipped-docs' ); ?></option>
            <?php foreach ( $categories as $c ) : ?>
                <option value="<?php echo esc_attr( $c->term_id ); ?>" <?php selected( in_array( $c->term_id, $current_categories, true ) ); ?>>
                    <?php echo esc_html( $c->name ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="zipped-docs-mb-field">
        <label for="zipped-docs-mb-order"><?php esc_html_e( 'Order', 'zipped-docs' ); ?></label>
        <input
            type="number"
            id="zipped-docs-mb-order"
            name="zipped_docs_order"
            value="<?php echo esc_attr( $current_order ?: '0' ); ?>"
            min="0"
            step="1"
        />
        <p class="description" style="margin:2px 0 0;font-size:11px;">
            <?php esc_html_e( 'Lower numbers appear first.', 'zipped-docs' ); ?>
        </p>
    </div>
    <?php
}

add_action( 'save_post', 'zipped_docs_save_meta_box' );
function zipped_docs_save_meta_box( $post_id ) {

    if ( ! isset( $_POST['zipped_docs_doc_settings_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zipped_docs_doc_settings_nonce'] ) ), 'zipped_docs_save_doc_settings' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : '';
    if ( 'zipped_docs_doc' !== $post_type ) {
        return;
    }

    $category_id = isset( $_POST['zipped_docs_category'] ) ? (int) wp_unslash( $_POST['zipped_docs_category'] ) : 0;
    if ( $category_id && term_exists( $category_id, 'zipped_docs_category' ) ) {
        wp_set_object_terms( $post_id, array( $category_id ), 'zipped_docs_category' );
    } else {
        wp_set_object_terms( $post_id, array(), 'zipped_docs_category' );
    }

    $order = isset( $_POST['zipped_docs_order'] ) ? (int) wp_unslash( $_POST['zipped_docs_order'] ) : 0;
    update_post_meta( $post_id, '_zipped_docs_order', max( 0, $order ) );
}
