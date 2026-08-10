<?php
/**
 * Zipped Docs — Layout Template
 *
 * @package zipped_docs
 */

defined( 'ABSPATH' ) || exit;

$zipped_docs_show_search          = 'yes' === $settings['zipped_docs_show_search'];
$zipped_docs_show_breadcrumbs     = 'yes' === $settings['zipped_docs_show_breadcrumbs'];
$zipped_docs_show_previous        = 'yes' === $settings['zipped_docs_show_previous'];
$zipped_docs_show_next            = 'yes' === $settings['zipped_docs_show_next'];
$zipped_docs_show_navigation      = 'yes' === $settings['zipped_docs_show_navigation'];
$zipped_docs_show_toc             = 'yes' === $settings['zipped_docs_show_toc'];
$zipped_docs_show_categories      = 'yes' === $settings['zipped_docs_show_categories'];
$zipped_docs_show_related         = 'yes' === $settings['zipped_docs_show_related_articles'];
$zipped_docs_show_reading_progress = 'yes' === $settings['zipped_docs_show_reading_progress'];
$zipped_docs_show_nav_rail         = 'yes' === $settings['zipped_docs_show_navigation_rail'];
$zipped_docs_toc_position          = $settings['toc_position'] ?? 'left';
$zipped_docs_rail_side             = 'left' === $zipped_docs_toc_position ? 'right' : 'left';
$zipped_docs_mobile_toc_position   = $settings['mobile_toc_position'] ?? 'top';
$zipped_docs_show_sidebar          = $zipped_docs_show_search || $zipped_docs_show_toc;
?>
<div
    class="zipped-docs"
    data-product="<?php echo esc_attr( $product ); ?>"
    data-doc-id="<?php echo esc_attr( $doc_id ?? 0 ); ?>"
    data-toc-depth="<?php echo esc_attr( $toc_depth ); ?>"
    data-show-toc="<?php echo $zipped_docs_show_toc ? '1' : '0'; ?>"
    data-show-search="<?php echo $zipped_docs_show_search ? '1' : '0'; ?>"
    data-show-sidebar="<?php echo $zipped_docs_show_sidebar ? '1' : '0'; ?>"
    data-show-reading-progress="<?php echo $zipped_docs_show_reading_progress ? '1' : '0'; ?>"
    role="main"
    aria-label="<?php echo esc_attr( $page_title ); ?> documentation"
>

    <?php if ( $zipped_docs_show_reading_progress ) : ?>
    <div class="zipped-docs-progress-bar" aria-hidden="true">
        <div class="zipped-docs-progress-bar-fill"></div>
    </div>
    <?php endif; ?>

    <?php if ( $zipped_docs_show_sidebar ) : ?>
    <div class="zipped-docs-mobile-toc" data-mobile-toc-position="<?php echo esc_attr( $zipped_docs_mobile_toc_position ); ?>">
        <button
            class="zipped-docs-mobile-toc-trigger"
            aria-expanded="false"
            aria-label="<?php esc_attr_e( 'Table of Contents', 'zipped-docs' ); ?>"
        >
            <span class="zipped-docs-mobile-toc-label"><?php esc_html_e( 'Table of Contents', 'zipped-docs' ); ?></span>
            <svg class="zipped-docs-mobile-toc-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6l4 4 4-4"/></svg>
        </button>
        <div class="zipped-docs-mobile-toc-backdrop"></div>
        <div class="zipped-docs-mobile-toc-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Table of Contents', 'zipped-docs' ); ?>">
            <div class="zipped-docs-mobile-toc-panel-header">
                <h2 class="zipped-docs-mobile-toc-panel-title"><?php esc_html_e( 'Table of Contents', 'zipped-docs' ); ?></h2>
                <button class="zipped-docs-mobile-toc-close" aria-label="<?php esc_attr_e( 'Close', 'zipped-docs' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="zipped-docs-mobile-toc-panel-body">
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $zipped_docs_show_sidebar ) : ?>
    <aside class="zipped-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'zipped-docs' ); ?>">

        <button
            class="zipped-docs-sidebar-toggle"
            aria-expanded="false"
            aria-label="<?php esc_attr_e( 'Toggle navigation', 'zipped-docs' ); ?>"
        >
            <span class="zipped-docs-toggle-icon" aria-hidden="true"></span>
            <?php esc_html_e( 'Contents', 'zipped-docs' ); ?>
        </button>

        <div class="zipped-docs-sidebar-inner">

            <?php if ( $zipped_docs_show_search ) : ?>
            <div class="zipped-docs-search-wrap" role="search">
                <label for="zipped-docs-search" class="zipped-docs-sr-only">
                    <?php esc_html_e( 'Search documentation', 'zipped-docs' ); ?>
                </label>
                <span class="zipped-docs-search-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </span>
                <input
                    type="search"
                    id="zipped-docs-search"
                    class="zipped-docs-search-input"
                    placeholder="<?php esc_attr_e( 'Search documentation…', 'zipped-docs' ); ?>"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="<?php esc_attr_e( 'Search documentation', 'zipped-docs' ); ?>"
                    data-min-query="2"
                />
                <button
                    class="zipped-docs-search-clear zipped-docs-hidden"
                    aria-label="<?php esc_attr_e( 'Clear search', 'zipped-docs' ); ?>"
                >✕</button>
            </div>

            <div class="zipped-docs-suggestions zipped-docs-hidden" role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'zipped-docs' ); ?>"></div>

            <p class="zipped-docs-no-results zipped-docs-hidden" role="status" aria-live="polite">
                <?php esc_html_e( 'No results found.', 'zipped-docs' ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $zipped_docs_show_toc ) : ?>
            <nav class="zipped-docs-toc" id="zipped-docs-toc"
                 aria-label="<?php esc_attr_e( 'On this page', 'zipped-docs' ); ?>">
                <p class="zipped-docs-toc-empty zipped-docs-hidden" aria-live="polite"></p>
            </nav>
            <?php endif; ?>

        </div>
    </aside>
    <?php endif; ?>

<?php if ( $zipped_docs_show_nav_rail ) : ?>
<nav class="zipped-docs-nav-rail zipped-docs-nav-rail--<?php echo esc_attr( $zipped_docs_rail_side ); ?>"
         aria-label="<?php esc_attr_e( 'Section navigation', 'zipped-docs' ); ?>"
         data-rail-side="<?php echo esc_attr( $zipped_docs_rail_side ); ?>"></nav>
    <?php endif; ?>

    <div class="zipped-docs-content-wrap">
        <article class="zipped-docs-content" id="zipped-docs-content">

            <?php if ( $zipped_docs_show_breadcrumbs ) : ?>
            <nav class="zipped-docs-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'zipped-docs' ); ?>">
            </nav>
            <?php endif; ?>

            <?php
            if ( $page_content ) {
                echo wp_kses_post( $page_content );
            } else {
                ?>
                <div class="zipped-docs-empty-state">
                    <p>
                        <?php
                        if ( $product ) {
                            printf(
                                /* translators: %s: product category label. */
                                esc_html__( 'Documentation for "%s" is coming soon.', 'zipped-docs' ),
                                esc_html( zipped_docs_product_label( $product ) )
                            );
                        } else {
                            esc_html_e( 'No documentation selected.', 'zipped-docs' );
                        }
                        ?>
                    </p>
                    <?php
                    $zipped_docs_show_hint = $settings['show_admin_hint'];
                    if ( current_user_can( 'zipped_docs_edit' ) && 'yes' === $zipped_docs_show_hint ) :
                    ?>
                        <p class="zipped-docs-admin-hint">
                            <?php
                            printf(
                                /* translators: %s: product category slug. */
                                esc_html__( 'Create a doc tagged with product "%s" (Zipped Docs → Add New) to populate this section.', 'zipped-docs' ),
                                esc_html( $product )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>

            <?php if ( $zipped_docs_show_related ) : ?>
            <div class="zipped-docs-related-wrap" aria-label="<?php esc_attr_e( 'Related articles', 'zipped-docs' ); ?>">
                <h3 class="zipped-docs-related-title"><?php esc_html_e( 'Related articles', 'zipped-docs' ); ?></h3>
                <ul class="zipped-docs-related-list"></ul>
            </div>
            <?php endif; ?>

        </article>

        <?php if ( $zipped_docs_show_navigation ) : ?>
        <footer class="zipped-docs-page-nav" aria-label="<?php esc_attr_e( 'Doc navigation', 'zipped-docs' ); ?>"
                data-show-prev="<?php echo $zipped_docs_show_previous ? '1' : '0'; ?>"
                data-show-next="<?php echo $zipped_docs_show_next ? '1' : '0'; ?>">
        </footer>
        <?php endif; ?>
    </div>

</div>
