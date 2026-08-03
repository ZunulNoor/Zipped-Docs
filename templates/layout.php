<?php
/**
 * Doc Vista — Layout Template
 *
 * @package doc_vista
 */

defined( 'ABSPATH' ) || exit;

$doc_vista_show_search          = 'yes' === $settings['doc_vista_show_search'];
$doc_vista_show_breadcrumbs     = 'yes' === $settings['doc_vista_show_breadcrumbs'];
$doc_vista_show_previous        = 'yes' === $settings['doc_vista_show_previous'];
$doc_vista_show_next            = 'yes' === $settings['doc_vista_show_next'];
$doc_vista_show_navigation      = 'yes' === $settings['doc_vista_show_navigation'];
$doc_vista_show_toc             = 'yes' === $settings['doc_vista_show_toc'];
$doc_vista_show_categories      = 'yes' === $settings['doc_vista_show_categories'];
$doc_vista_show_related         = 'yes' === $settings['doc_vista_show_related_articles'];
$doc_vista_show_reading_progress = 'yes' === $settings['doc_vista_show_reading_progress'];
$doc_vista_show_nav_rail         = 'yes' === $settings['doc_vista_show_navigation_rail'];
$doc_vista_toc_position          = $settings['toc_position'] ?? 'left';
$doc_vista_rail_side             = 'left' === $doc_vista_toc_position ? 'right' : 'left';
$doc_vista_mobile_toc_position   = $settings['mobile_toc_position'] ?? 'top';
$doc_vista_show_sidebar          = $doc_vista_show_search || $doc_vista_show_toc;
?>
<div
    class="doc-vista"
    data-product="<?php echo esc_attr( $product ); ?>"
    data-doc-id="<?php echo esc_attr( $doc_id ?? 0 ); ?>"
    data-toc-depth="<?php echo esc_attr( $toc_depth ); ?>"
    data-show-toc="<?php echo $doc_vista_show_toc ? '1' : '0'; ?>"
    data-show-search="<?php echo $doc_vista_show_search ? '1' : '0'; ?>"
    data-show-sidebar="<?php echo $doc_vista_show_sidebar ? '1' : '0'; ?>"
    data-show-reading-progress="<?php echo $doc_vista_show_reading_progress ? '1' : '0'; ?>"
    role="main"
    aria-label="<?php echo esc_attr( $page_title ); ?> documentation"
>

    <?php if ( $doc_vista_show_reading_progress ) : ?>
    <div class="doc-vista-progress-bar" aria-hidden="true">
        <div class="doc-vista-progress-bar-fill"></div>
    </div>
    <?php endif; ?>

    <?php if ( $doc_vista_show_sidebar ) : ?>
    <div class="doc-vista-mobile-toc" data-mobile-toc-position="<?php echo esc_attr( $doc_vista_mobile_toc_position ); ?>">
        <button
            class="doc-vista-mobile-toc-trigger"
            aria-expanded="false"
            aria-label="<?php esc_attr_e( 'Table of Contents', 'doc-vista' ); ?>"
        >
            <span class="doc-vista-mobile-toc-label"><?php esc_html_e( 'Table of Contents', 'doc-vista' ); ?></span>
            <svg class="doc-vista-mobile-toc-chevron" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6l4 4 4-4"/></svg>
        </button>
        <div class="doc-vista-mobile-toc-backdrop"></div>
        <div class="doc-vista-mobile-toc-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Table of Contents', 'doc-vista' ); ?>">
            <div class="doc-vista-mobile-toc-panel-header">
                <h2 class="doc-vista-mobile-toc-panel-title"><?php esc_html_e( 'Table of Contents', 'doc-vista' ); ?></h2>
                <button class="doc-vista-mobile-toc-close" aria-label="<?php esc_attr_e( 'Close', 'doc-vista' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="doc-vista-mobile-toc-panel-body">
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( $doc_vista_show_sidebar ) : ?>
    <aside class="doc-vista-sidebar" aria-label="<?php esc_attr_e( 'Documentation navigation', 'doc-vista' ); ?>">

        <button
            class="doc-vista-sidebar-toggle"
            aria-expanded="false"
            aria-label="<?php esc_attr_e( 'Toggle navigation', 'doc-vista' ); ?>"
        >
            <span class="doc-vista-toggle-icon" aria-hidden="true"></span>
            <?php esc_html_e( 'Contents', 'doc-vista' ); ?>
        </button>

        <div class="doc-vista-sidebar-inner">

            <?php if ( $doc_vista_show_search ) : ?>
            <div class="doc-vista-search-wrap" role="search">
                <label for="doc-vista-search" class="doc-vista-sr-only">
                    <?php esc_html_e( 'Search documentation', 'doc-vista' ); ?>
                </label>
                <span class="doc-vista-search-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </span>
                <input
                    type="search"
                    id="doc-vista-search"
                    class="doc-vista-search-input"
                    placeholder="<?php esc_attr_e( 'Search documentation…', 'doc-vista' ); ?>"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="<?php esc_attr_e( 'Search documentation', 'doc-vista' ); ?>"
                    data-min-query="2"
                />
                <button
                    class="doc-vista-search-clear doc-vista-hidden"
                    aria-label="<?php esc_attr_e( 'Clear search', 'doc-vista' ); ?>"
                >✕</button>
            </div>

            <div class="doc-vista-suggestions doc-vista-hidden" role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'doc-vista' ); ?>"></div>

            <p class="doc-vista-no-results doc-vista-hidden" role="status" aria-live="polite">
                <?php esc_html_e( 'No results found.', 'doc-vista' ); ?>
            </p>
            <?php endif; ?>

            <?php if ( $doc_vista_show_toc ) : ?>
            <nav class="doc-vista-toc" id="doc-vista-toc"
                 aria-label="<?php esc_attr_e( 'On this page', 'doc-vista' ); ?>">
                <p class="doc-vista-toc-empty doc-vista-hidden" aria-live="polite"></p>
            </nav>
            <?php endif; ?>

        </div>
    </aside>
    <?php endif; ?>

<?php if ( $doc_vista_show_nav_rail ) : ?>
<nav class="doc-vista-nav-rail doc-vista-nav-rail--<?php echo esc_attr( $doc_vista_rail_side ); ?>"
         aria-label="<?php esc_attr_e( 'Section navigation', 'doc-vista' ); ?>"
         data-rail-side="<?php echo esc_attr( $doc_vista_rail_side ); ?>"></nav>
    <?php endif; ?>

    <div class="doc-vista-content-wrap">
        <article class="doc-vista-content" id="doc-vista-content">

            <?php if ( $doc_vista_show_breadcrumbs ) : ?>
            <nav class="doc-vista-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'doc-vista' ); ?>">
            </nav>
            <?php endif; ?>

            <?php
            if ( $page_content ) {
                echo wp_kses_post( $page_content );
            } else {
                ?>
                <div class="doc-vista-empty-state">
                    <p>
                        <?php
                        if ( $product ) {
                            printf(
                                /* translators: %s: product category label. */
                                esc_html__( 'Documentation for "%s" is coming soon.', 'doc-vista' ),
                                esc_html( doc_vista_product_label( $product ) )
                            );
                        } else {
                            esc_html_e( 'No documentation selected.', 'doc-vista' );
                        }
                        ?>
                    </p>
                    <?php
                    $doc_vista_show_hint = $settings['show_admin_hint'];
                    if ( current_user_can( 'doc_vista_edit' ) && 'yes' === $doc_vista_show_hint ) :
                    ?>
                        <p class="doc-vista-admin-hint">
                            <?php
                            printf(
                                /* translators: %s: product category slug. */
                                esc_html__( 'Create a doc tagged with product "%s" (Doc Vista → Add New) to populate this section.', 'doc-vista' ),
                                esc_html( $product )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>

            <?php if ( $doc_vista_show_related ) : ?>
            <div class="doc-vista-related-wrap" aria-label="<?php esc_attr_e( 'Related articles', 'doc-vista' ); ?>">
                <h3 class="doc-vista-related-title"><?php esc_html_e( 'Related articles', 'doc-vista' ); ?></h3>
                <ul class="doc-vista-related-list"></ul>
            </div>
            <?php endif; ?>

        </article>

        <?php if ( $doc_vista_show_navigation ) : ?>
        <footer class="doc-vista-page-nav" aria-label="<?php esc_attr_e( 'Doc navigation', 'doc-vista' ); ?>"
                data-show-prev="<?php echo $doc_vista_show_previous ? '1' : '0'; ?>"
                data-show-next="<?php echo $doc_vista_show_next ? '1' : '0'; ?>">
        </footer>
        <?php endif; ?>
    </div>

</div>
