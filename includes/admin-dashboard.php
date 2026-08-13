<?php

defined( 'ABSPATH' ) || exit;

function zipped_docs_admin_dashboard() {
    if ( ! current_user_can( 'zipped_docs_read' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'zipped-docs' ) );
    }

    if ( isset( $_GET['action'], $_GET['doc'] ) && 'delete' === $_GET['action'] ) {
        if ( ! current_user_can( 'zipped_docs_delete' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'zipped-docs' ) );
        }
        $doc_id = (int) $_GET['doc'];
        if ( $doc_id && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'delete_doc_' . $doc_id ) ) {
            $doc_obj = get_post( $doc_id );
            if ( $doc_obj instanceof WP_Post && 'zipped_docs_doc' === $doc_obj->post_type ) {
                wp_delete_post( $doc_id, true );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Doc deleted.', 'zipped-docs' ) . '</p></div>';
            }
        }
    }

    $filter_category = isset( $_GET['zipped_docs_category'] ) ? (int) $_GET['zipped_docs_category'] : 0;
    $paged           = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
    $per_page        = 20;

    $sortable_columns = array( 'title', 'author', 'status', 'updated', 'order' );
    $current_orderby  = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'updated';
    if ( ! in_array( $current_orderby, $sortable_columns, true ) ) {
        $current_orderby = 'updated';
    }
    $current_order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
    if ( ! in_array( $current_order, array( 'ASC', 'DESC' ), true ) ) {
        $current_order = 'DESC';
    }

    $sort_url = function ( $column ) use ( $current_orderby, $current_order ) {
        $new_order = 'ASC';
        if ( $current_orderby === $column ) {
            $new_order = 'ASC' === $current_order ? 'DESC' : 'ASC';
        }
        $classes = 'zipped-docs-sortable';
        if ( $current_orderby === $column ) {
            $classes .= ' sorted ' . strtolower( $current_order );
        }
        $arrow = '';
        if ( $current_orderby === $column ) {
            $arrow = 'ASC' === $current_order ? '▲' : '▼';
        }
        return array(
            'url'   => add_query_arg( array( 'orderby' => $column, 'order' => $new_order, 'paged' => 1 ) ),
            'class' => $classes,
            'arrow' => $arrow,
        );
    };

    $counts    = (array) wp_count_posts( 'zipped_docs_doc' );
    $total     = (int) ( $counts['publish'] ?? 0 ) + (int) ( $counts['draft'] ?? 0 ) + (int) ( $counts['pending'] ?? 0 );
    $published = (int) ( $counts['publish'] ?? 0 );
    $drafts    = (int) ( $counts['draft'] ?? 0 );

    $graph      = zipped_docs_get_graph();
    $graph_total = 0;
    if ( isset( $graph['doc_tree'] ) && is_array( $graph['doc_tree'] ) ) {
        foreach ( $graph['doc_tree'] as $slug => $tree ) {
            $graph_total += isset( $tree['flat_list'] ) ? count( $tree['flat_list'] ) : 0;
        }
    }

    $orderby_map = array(
        'title'   => 'title',
        'author'  => 'author',
        'status'  => 'post_status',
        'updated' => 'modified',
        'order'   => 'meta_value_num',
    );

    $args = array(
        'post_type'      => 'zipped_docs_doc',
        'post_status'    => array( 'publish', 'draft', 'pending' ),
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => $orderby_map[ $current_orderby ],
        'order'          => $current_order,
    );

    if ( 'order' === $current_orderby ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for sorting by custom order field.
        $args['meta_key'] = '_zipped_docs_order';
    }

    if ( $filter_category ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for category filtering.
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'zipped_docs_category',
                'field'    => 'term_id',
                'terms'    => $filter_category,
            ),
        );
    }

    $query = new WP_Query( $args );
    $docs  = $query->posts;
    $total_pages = $query->max_num_pages;

    $categories = get_terms( array(
        'taxonomy'   => 'zipped_docs_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    ?>
    <div class="wrap zipped-docs-dashboard">
        <h1>
            <?php esc_html_e( 'Zipped Docs', 'zipped-docs' ); ?>
            <?php if ( current_user_can( 'zipped_docs_create' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=zipped-docs-new' ) ); ?>" class="page-title-action">
                    <?php esc_html_e( 'Add New Doc', 'zipped-docs' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'zipped_docs_import' ) ) : ?>
                <a href="#" id="zipped-docs-import-btn" class="page-title-action" style="border-color:#2563EB;color:#2563EB;">
                    <?php esc_html_e( 'Import Documentation', 'zipped-docs' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'zipped_docs_export' ) ) : ?>
                <a href="#" id="zipped-docs-export-btn" class="page-title-action" style="border-color:#16A34A;color:#16A34A;">
                    <?php esc_html_e( 'Export Documentation', 'zipped-docs' ); ?>
                </a>
            <?php endif; ?>
        </h1>

        <div class="zipped-docs-stats-grid">
            <div class="zipped-docs-stat-card">
                <span class="zipped-docs-stat-number"><?php echo esc_html( $total ); ?></span>
                <span class="zipped-docs-stat-label"><?php esc_html_e( 'Total Docs', 'zipped-docs' ); ?></span>
            </div>
            <div class="zipped-docs-stat-card zipped-docs-stat-published">
                <span class="zipped-docs-stat-number"><?php echo esc_html( $published ); ?></span>
                <span class="zipped-docs-stat-label"><?php esc_html_e( 'Published', 'zipped-docs' ); ?></span>
            </div>
            <div class="zipped-docs-stat-card zipped-docs-stat-draft">
                <span class="zipped-docs-stat-number"><?php echo esc_html( $drafts ); ?></span>
                <span class="zipped-docs-stat-label"><?php esc_html_e( 'Drafts', 'zipped-docs' ); ?></span>
            </div>
            <div class="zipped-docs-stat-card zipped-docs-stat-cats">
                <span class="zipped-docs-stat-number"><?php echo esc_html( count( $categories ) ); ?></span>
                <span class="zipped-docs-stat-label"><?php esc_html_e( 'Categories', 'zipped-docs' ); ?></span>
            </div>
        </div>

        <div class="zipped-docs-filter-bar">
            <form method="get" action="">
                <input type="hidden" name="page" value="zipped-docs" />

                <label for="zipped-docs-filter-category" class="zipped-docs-sr-admin">
                    <?php esc_html_e( 'Filter by category', 'zipped-docs' ); ?>
                </label>
                <select id="zipped-docs-filter-category" name="zipped_docs_category">
                    <option value=""><?php esc_html_e( 'All Categories', 'zipped-docs' ); ?></option>
                    <?php foreach ( $categories as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->term_id ); ?>" <?php selected( $filter_category, $c->term_id ); ?>>
                            <?php echo esc_html( $c->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'zipped-docs' ); ?></button>

                <?php if ( $filter_category ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zipped-docs' ) ); ?>" class="button">
                        <?php esc_html_e( 'Clear', 'zipped-docs' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="zipped-docs-docs-list-wrap">
            <?php if ( empty( $docs ) ) : ?>
                <div class="zipped-docs-empty-admin">
                    <p><?php esc_html_e( 'No documentation articles found.', 'zipped-docs' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zipped-docs-new' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Create your first doc', 'zipped-docs' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="zippeddocs-table-wrapper">
                <table class="wp-list-table widefat fixed striped zipped-docs-docs-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-cb" style="width:32px;">
                                <input type="checkbox" id="zipped-docs-select-all" style="accent-color:#2563EB;">
                            </th>
                            <th scope="col" class="column-title"><?php $s = $sort_url( 'title' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Title', 'zipped-docs' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo esc_html( $s['arrow'] ); ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php esc_html_e( 'Category', 'zipped-docs' ); ?></th>
                            <th scope="col"><?php $s = $sort_url( 'author' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Author', 'zipped-docs' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo esc_html( $s['arrow'] ); ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'status' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Status', 'zipped-docs' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo esc_html( $s['arrow'] ); ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'order' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Order', 'zipped-docs' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo esc_html( $s['arrow'] ); ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'updated' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Updated', 'zipped-docs' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo esc_html( $s['arrow'] ); ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php esc_html_e( 'Actions', 'zipped-docs' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $docs as $doc ) :
                            $doc_cats     = wp_get_post_terms( $doc->ID, 'zipped_docs_category', array( 'fields' => 'names' ) );
                            $doc_order    = get_post_meta( $doc->ID, '_zipped_docs_order', true );
                            $status_label = 'publish' === $doc->post_status ? __( 'Published', 'zipped-docs' ) : ucfirst( $doc->post_status );
                            $status_class = 'publish' === $doc->post_status ? 'zipped-docs-status-published' : 'zipped-docs-status-draft';
                            $edit_link    = admin_url( 'post.php?post=' . $doc->ID . '&action=edit' );
                            $view_link    = get_permalink( $doc->ID );
                            $delete_link  = wp_nonce_url(
                                admin_url( 'admin.php?page=zipped-docs&action=delete&doc=' . $doc->ID ),
                                'delete_doc_' . $doc->ID
                            );
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="zipped-docs-export-checkbox" value="<?php echo esc_attr( $doc->ID ); ?>" style="accent-color:#2563EB;">
                            </td>
                            <td class="column-title">
                                <strong><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $doc->post_title ); ?></a></strong>
                            </td>
                            <td><?php echo esc_html( $doc_cats ? implode( ', ', $doc_cats ) : '—' ); ?></td>
                            <td class="column-author"><?php
                                $author_id = $doc->post_author;
                                $author    = $author_id ? get_userdata( $author_id ) : false;
                                if ( $author ) :
                                    echo get_avatar( $author->ID, 24, '', '', array( 'class' => 'zipped-docs-author-avatar' ) );
                                    echo '<span class="zipped-docs-author-name">' . esc_html( $author->display_name ) . '</span>';
                                else :
                                    echo '<span class="zipped-docs-author-unknown">—</span>';
                                endif;
                            ?></td>
                            <td><span class="zipped-docs-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                            <td><?php echo esc_html( $doc_order ?: '—' ); ?></td>
                            <td><?php echo esc_html( get_the_modified_date( 'Y-m-d', $doc->ID ) ); ?></td>
                            <td class="zipped-docs-actions">
                                <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'zipped-docs' ); ?></a>
                                <a href="<?php echo esc_url( $view_link ); ?>" class="button button-small" target="_blank"><?php esc_html_e( 'View', 'zipped-docs' ); ?></a>
                                <?php if ( current_user_can( 'zipped_docs_export' ) ) : ?>
                                    <a href="#" class="button button-small zipped-docs-export-single" data-doc-id="<?php echo esc_attr( $doc->ID ); ?>" style="border-color:#16A34A;color:#16A34A;"><?php esc_html_e( 'Export', 'zipped-docs' ); ?></a>
                                <?php endif; ?>
                                <?php if ( current_user_can( 'zipped_docs_delete' ) ) : ?>
                                    <a href="<?php echo esc_url( $delete_link ); ?>" class="button button-small button-link-delete zipped-docs-delete-doc" data-confirm="<?php esc_attr_e( 'Delete this doc permanently?', 'zipped-docs' ); ?>"><?php esc_html_e( 'Delete', 'zipped-docs' ); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo wp_kses_post( paginate_links( array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $paged,
                                'type'      => 'plain',
                            ) ) );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
