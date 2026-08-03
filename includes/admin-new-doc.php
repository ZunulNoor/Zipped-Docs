<?php

defined( 'ABSPATH' ) || exit;

function doc_vista_admin_new_doc_page() {
    if ( ! current_user_can( 'doc_vista_create' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
    }

    $categories = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
    ) );

    $saved = false;
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['doc_vista_new_doc_nonce'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['doc_vista_new_doc_nonce'] ) ), 'doc_vista_new_doc' ) ) {
            if ( ! current_user_can( 'doc_vista_publish' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
            }
            $title   = sanitize_text_field( wp_unslash( $_POST['doc_vista_title'] ?? '' ) );
            $content = wp_kses_post( wp_unslash( $_POST['doc_vista_content'] ?? '' ) );
            $cat_id  = isset( $_POST['doc_vista_category'] ) ? (int) wp_unslash( $_POST['doc_vista_category'] ) : 0;

            if ( $title ) {
                $post_id = wp_insert_post( array(
                    'post_type'    => 'doc_vista_doc',
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                ) );

                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    if ( $cat_id && term_exists( $cat_id, 'doc_vista_category' ) ) {
                        wp_set_object_terms( $post_id, (int) $cat_id, 'doc_vista_category' );
                    }
                    $saved = true;
                    $edit_link = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
                }
            }
        }
    }
    ?>
    <div class="wrap doc-vista-new-doc">
        <h1><?php esc_html_e( 'Add New Doc', 'doc-vista' ); ?></h1>

        <?php if ( $saved ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Doc created successfully.', 'doc-vista' ); ?>
                <a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit with Gutenberg', 'doc-vista' ); ?></a></p>
            </div>
        <?php endif; ?>

        <div class="doc-vista-new-doc-layout">
            <div class="doc-vista-quick-form">
                <h2><?php esc_html_e( 'Quick Create', 'doc-vista' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Write a title and content below, or use the full editor for advanced formatting.', 'doc-vista' ); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field( 'doc_vista_new_doc', 'doc_vista_new_doc_nonce' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="doc_vista_title"><?php esc_html_e( 'Title', 'doc-vista' ); ?></label></th>
                            <td><input type="text" id="doc_vista_title" name="doc_vista_title" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="doc_vista_category"><?php esc_html_e( 'Category', 'doc-vista' ); ?></label></th>
                            <td>
                                <select id="doc_vista_category" name="doc_vista_category">
                                    <option value=""><?php esc_html_e( '— Select —', 'doc-vista' ); ?></option>
                                    <?php foreach ( $categories as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="doc_vista_content"><?php esc_html_e( 'Content', 'doc-vista' ); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    '',
                                    'doc_vista_content',
                                    array(
                                        'textarea_rows' => 20,
                                        'media_buttons' => true,
                                        'teeny'         => false,
                                        'quicktags'     => true,
                                    )
                                );
                                ?>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Doc', 'doc-vista' ); ?></button>
                    </p>
                </form>
            </div>

            <div class="doc-vista-full-editor-link">
                <h2><?php esc_html_e( 'Full Editor', 'doc-vista' ); ?></h2>
                <p><?php esc_html_e( 'Use the full Gutenberg block editor for rich content, embeds, and advanced layouts.', 'doc-vista' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=doc_vista_doc' ) ); ?>" class="button button-primary button-hero">
                    <?php esc_html_e( 'Open Gutenberg Editor', 'doc-vista' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}
