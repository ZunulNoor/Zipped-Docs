<?php

defined( 'ABSPATH' ) || exit;

function zipped_docs_admin_new_doc_page() {
    if ( ! current_user_can( 'zipped_docs_create' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions.', 'zipped-docs' ) );
    }

    $categories = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
    ) );

    $saved = false;
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['zipped_docs_new_doc_nonce'] ) ) {
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zipped_docs_new_doc_nonce'] ) ), 'zipped_docs_new_doc' ) ) {
            if ( ! current_user_can( 'zipped_docs_publish' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'zipped-docs' ) );
            }
            $title   = sanitize_text_field( wp_unslash( $_POST['zipped_docs_title'] ?? '' ) );
            $content = wp_kses_post( wp_unslash( $_POST['zipped_docs_content'] ?? '' ) );
            $cat_id  = isset( $_POST['zipped_docs_category'] ) ? (int) wp_unslash( $_POST['zipped_docs_category'] ) : 0;

            if ( $title ) {
                $post_id = wp_insert_post( array(
                    'post_type'    => 'zipped_docs_doc',
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                ) );

                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    if ( $cat_id && term_exists( $cat_id, 'zipped_docs_category' ) ) {
                        wp_set_object_terms( $post_id, (int) $cat_id, 'zipped_docs_category' );
                    }
                    $saved = true;
                    $edit_link = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
                }
            }
        }
    }
    ?>
    <div class="wrap zipped-docs-new-doc">
        <h1><?php esc_html_e( 'Add New Doc', 'zipped-docs' ); ?></h1>

        <?php if ( $saved ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Doc created successfully.', 'zipped-docs' ); ?>
                <a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit with Gutenberg', 'zipped-docs' ); ?></a></p>
            </div>
        <?php endif; ?>

        <div class="zipped-docs-new-doc-layout">
            <div class="zipped-docs-quick-form">
                <h2><?php esc_html_e( 'Quick Create', 'zipped-docs' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Write a title and content below, or use the full editor for advanced formatting.', 'zipped-docs' ); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field( 'zipped_docs_new_doc', 'zipped_docs_new_doc_nonce' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="zipped_docs_title"><?php esc_html_e( 'Title', 'zipped-docs' ); ?></label></th>
                            <td><input type="text" id="zipped_docs_title" name="zipped_docs_title" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="zipped_docs_category"><?php esc_html_e( 'Category', 'zipped-docs' ); ?></label></th>
                            <td>
                                <select id="zipped_docs_category" name="zipped_docs_category">
                                    <option value=""><?php esc_html_e( '— Select —', 'zipped-docs' ); ?></option>
                                    <?php foreach ( $categories as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="zipped_docs_content"><?php esc_html_e( 'Content', 'zipped-docs' ); ?></label></th>
                            <td>
                                <?php
                                wp_editor(
                                    '',
                                    'zipped_docs_content',
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
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Doc', 'zipped-docs' ); ?></button>
                    </p>
                </form>
            </div>

            <div class="zipped-docs-full-editor-link">
                <h2><?php esc_html_e( 'Full Editor', 'zipped-docs' ); ?></h2>
                <p><?php esc_html_e( 'Use the full Gutenberg block editor for rich content, embeds, and advanced layouts.', 'zipped-docs' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=zipped_docs_doc' ) ); ?>" class="button button-primary button-hero">
                    <?php esc_html_e( 'Open Gutenberg Editor', 'zipped-docs' ); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}
