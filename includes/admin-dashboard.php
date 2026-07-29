<?php

defined( 'ABSPATH' ) || exit;

function doc_vista_admin_dashboard() {
    if ( ! current_user_can( 'doc_vista_read' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
    }

    if ( isset( $_GET['action'], $_GET['doc'] ) && 'delete' === $_GET['action'] ) {
        if ( ! current_user_can( 'doc_vista_delete' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
        }
        $doc_id = (int) $_GET['doc'];
        if ( $doc_id && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ?? '' ), 'delete_doc_' . $doc_id ) ) {
            wp_delete_post( $doc_id, true );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Doc deleted.', 'doc-vista' ) . '</p></div>';
        }
    }

    $filter_category = isset( $_GET['doc_vista_category'] ) ? (int) $_GET['doc_vista_category'] : 0;
    $paged           = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
    $per_page        = 20;

    $sortable_columns = array( 'title', 'author', 'status', 'updated', 'order' );
    $current_orderby  = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $sortable_columns, true ) ? $_GET['orderby'] : 'updated';
    $current_order    = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $_GET['order'] ) : 'DESC';

    $sort_url = function ( $column ) use ( $current_orderby, $current_order ) {
        $new_order = 'ASC';
        if ( $current_orderby === $column ) {
            $new_order = 'ASC' === $current_order ? 'DESC' : 'ASC';
        }
        $classes = 'doc-vista-sortable';
        if ( $current_orderby === $column ) {
            $classes .= ' sorted ' . strtolower( $current_order );
        }
        $arrow = '';
        if ( $current_orderby === $column ) {
            $arrow = 'ASC' === $current_order ? '&#9650;' : '&#9660;';
        }
        return array(
            'url'   => add_query_arg( array( 'orderby' => $column, 'order' => $new_order, 'paged' => 1 ) ),
            'class' => $classes,
            'arrow' => $arrow,
        );
    };

    $counts    = (array) wp_count_posts( 'doc_vista_doc' );
    $total     = (int) ( $counts['publish'] ?? 0 ) + (int) ( $counts['draft'] ?? 0 ) + (int) ( $counts['pending'] ?? 0 );
    $published = (int) ( $counts['publish'] ?? 0 );
    $drafts    = (int) ( $counts['draft'] ?? 0 );

    $graph      = doc_vista_get_graph();
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
        'post_type'      => 'doc_vista_doc',
        'post_status'    => array( 'publish', 'draft', 'pending' ),
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => $orderby_map[ $current_orderby ],
        'order'          => $current_order,
    );

    if ( 'order' === $current_orderby ) {
        $args['meta_key'] = '_doc_vista_order';
    }

    if ( $filter_category ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'doc_vista_category',
                'field'    => 'term_id',
                'terms'    => $filter_category,
            ),
        );
    }

    $query = new WP_Query( $args );
    $docs  = $query->posts;
    $total_pages = $query->max_num_pages;

    $categories = get_terms( array(
        'taxonomy'   => 'doc_vista_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );
    ?>
    <div class="wrap doc-vista-dashboard">
        <h1>
            <?php esc_html_e( 'Doc Vista', 'doc-vista' ); ?>
            <?php if ( current_user_can( 'doc_vista_create' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=doc-vista-new' ) ); ?>" class="page-title-action">
                    <?php esc_html_e( 'Add New Doc', 'doc-vista' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'doc_vista_import' ) ) : ?>
                <a href="#" id="doc-vista-import-btn" class="page-title-action" style="border-color:#2563EB;color:#2563EB;">
                    <?php esc_html_e( 'Import Documentation', 'doc-vista' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( current_user_can( 'doc_vista_export' ) ) : ?>
                <a href="#" id="doc-vista-export-btn" class="page-title-action" style="border-color:#16A34A;color:#16A34A;">
                    <?php esc_html_e( 'Export Documentation', 'doc-vista' ); ?>
                </a>
            <?php endif; ?>
        </h1>

        <div class="doc-vista-stats-grid">
            <div class="doc-vista-stat-card">
                <span class="doc-vista-stat-number"><?php echo esc_html( $total ); ?></span>
                <span class="doc-vista-stat-label"><?php esc_html_e( 'Total Docs', 'doc-vista' ); ?></span>
            </div>
            <div class="doc-vista-stat-card doc-vista-stat-published">
                <span class="doc-vista-stat-number"><?php echo esc_html( $published ); ?></span>
                <span class="doc-vista-stat-label"><?php esc_html_e( 'Published', 'doc-vista' ); ?></span>
            </div>
            <div class="doc-vista-stat-card doc-vista-stat-draft">
                <span class="doc-vista-stat-number"><?php echo esc_html( $drafts ); ?></span>
                <span class="doc-vista-stat-label"><?php esc_html_e( 'Drafts', 'doc-vista' ); ?></span>
            </div>
            <div class="doc-vista-stat-card doc-vista-stat-cats">
                <span class="doc-vista-stat-number"><?php echo esc_html( count( $categories ) ); ?></span>
                <span class="doc-vista-stat-label"><?php esc_html_e( 'Categories', 'doc-vista' ); ?></span>
            </div>
        </div>

        <div class="doc-vista-filter-bar">
            <form method="get" action="">
                <input type="hidden" name="page" value="doc-vista" />

                <label for="doc-vista-filter-category" class="doc-vista-sr-admin">
                    <?php esc_html_e( 'Filter by category', 'doc-vista' ); ?>
                </label>
                <select id="doc-vista-filter-category" name="doc_vista_category">
                    <option value=""><?php esc_html_e( 'All Categories', 'doc-vista' ); ?></option>
                    <?php foreach ( $categories as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->term_id ); ?>" <?php selected( $filter_category, $c->term_id ); ?>>
                            <?php echo esc_html( $c->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="button"><?php esc_html_e( 'Filter', 'doc-vista' ); ?></button>

                <?php if ( $filter_category ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=doc-vista' ) ); ?>" class="button">
                        <?php esc_html_e( 'Clear', 'doc-vista' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="doc-vista-docs-list-wrap">
            <?php if ( empty( $docs ) ) : ?>
                <div class="doc-vista-empty-admin">
                    <p><?php esc_html_e( 'No documentation articles found.', 'doc-vista' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=doc-vista-new' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Create your first doc', 'doc-vista' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="docvista-table-wrapper">
                <table class="wp-list-table widefat fixed striped doc-vista-docs-table">
                    <thead>
                        <tr>
                            <th scope="col" class="column-cb" style="width:32px;">
                                <input type="checkbox" id="doc-vista-select-all" style="accent-color:#2563EB;">
                            </th>
                            <th scope="col" class="column-title"><?php $s = $sort_url( 'title' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Title', 'doc-vista' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo $s['arrow']; ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php esc_html_e( 'Category', 'doc-vista' ); ?></th>
                            <th scope="col"><?php $s = $sort_url( 'author' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Author', 'doc-vista' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo $s['arrow']; ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'status' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Status', 'doc-vista' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo $s['arrow']; ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'order' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Order', 'doc-vista' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo $s['arrow']; ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php $s = $sort_url( 'updated' ); ?><a href="<?php echo esc_url( $s['url'] ); ?>" class="<?php echo esc_attr( $s['class'] ); ?>"><?php esc_html_e( 'Updated', 'doc-vista' ); ?><?php if ( $s['arrow'] ) : ?><span class="sorting-indicator"><?php echo $s['arrow']; ?></span><?php endif; ?></a></th>
                            <th scope="col"><?php esc_html_e( 'Actions', 'doc-vista' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $docs as $doc ) :
                            $doc_cats     = wp_get_post_terms( $doc->ID, 'doc_vista_category', array( 'fields' => 'names' ) );
                            $doc_order    = get_post_meta( $doc->ID, '_doc_vista_order', true );
                            $status_label = 'publish' === $doc->post_status ? __( 'Published', 'doc-vista' ) : ucfirst( $doc->post_status );
                            $status_class = 'publish' === $doc->post_status ? 'doc-vista-status-published' : 'doc-vista-status-draft';
                            $edit_link    = admin_url( 'post.php?post=' . $doc->ID . '&action=edit' );
                            $view_link    = get_permalink( $doc->ID );
                            $delete_link  = wp_nonce_url(
                                admin_url( 'admin.php?page=doc-vista&action=delete&doc=' . $doc->ID ),
                                'delete_doc_' . $doc->ID
                            );
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="doc-vista-export-checkbox" value="<?php echo esc_attr( $doc->ID ); ?>" style="accent-color:#2563EB;">
                            </td>
                            <td class="column-title">
                                <strong><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $doc->post_title ); ?></a></strong>
                            </td>
                            <td><?php echo esc_html( $doc_cats ? implode( ', ', $doc_cats ) : '—' ); ?></td>
                            <td class="column-author"><?php
                                $author_id = $doc->post_author;
                                $author    = $author_id ? get_userdata( $author_id ) : false;
                                if ( $author ) :
                                    echo get_avatar( $author->ID, 24, '', '', array( 'class' => 'doc-vista-author-avatar' ) );
                                    echo '<span class="doc-vista-author-name">' . esc_html( $author->display_name ) . '</span>';
                                else :
                                    echo '<span class="doc-vista-author-unknown">—</span>';
                                endif;
                            ?></td>
                            <td><span class="doc-vista-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                            <td><?php echo esc_html( $doc_order ?: '—' ); ?></td>
                            <td><?php echo esc_html( get_the_modified_date( 'Y-m-d', $doc->ID ) ); ?></td>
                            <td class="doc-vista-actions">
                                <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'doc-vista' ); ?></a>
                                <a href="<?php echo esc_url( $view_link ); ?>" class="button button-small" target="_blank"><?php esc_html_e( 'View', 'doc-vista' ); ?></a>
                                <?php if ( current_user_can( 'doc_vista_export' ) ) : ?>
                                    <a href="#" class="button button-small doc-vista-export-single" data-doc-id="<?php echo esc_attr( $doc->ID ); ?>" style="border-color:#16A34A;color:#16A34A;"><?php esc_html_e( 'Export', 'doc-vista' ); ?></a>
                                <?php endif; ?>
                                <?php if ( current_user_can( 'doc_vista_delete' ) ) : ?>
                                    <a href="<?php echo esc_url( $delete_link ); ?>" class="button button-small button-link-delete doc-vista-delete-doc" data-confirm="<?php esc_attr_e( 'Delete this doc permanently?', 'doc-vista' ); ?>"><?php esc_html_e( 'Delete', 'doc-vista' ); ?></a>
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
