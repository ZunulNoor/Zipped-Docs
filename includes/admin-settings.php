<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'doc_vista_register_settings' );
function doc_vista_register_settings() {
    Doc_Vista_Settings::register();
}

function doc_vista_admin_settings_page() {
    if ( ! current_user_can( 'doc_vista_manage_settings' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'doc-vista' ) );
    }

    $saved_notice = '';

    $rebuild_nonce = isset( $_POST['doc_vista_rebuild_cache_nonce'] ) ? sanitize_key( $_POST['doc_vista_rebuild_cache_nonce'] ) : '';
    if ( current_user_can( 'doc_vista_manage_settings' ) && isset( $_POST['doc_vista_rebuild_cache'] ) && wp_verify_nonce( $rebuild_nonce, 'doc_vista_rebuild_cache' ) ) {
        doc_vista_rebuild_graph();
        $saved_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Documentation cache rebuilt successfully.', 'doc-vista' ) . '</p></div>';
    }

    $settings_nonce = isset( $_POST['doc_vista_settings_nonce'] ) ? sanitize_key( $_POST['doc_vista_settings_nonce'] ) : '';
    if ( current_user_can( 'doc_vista_manage_settings' ) && isset( $_POST['doc_vista_settings_nonce'] ) && wp_verify_nonce( $settings_nonce, 'doc_vista_save_settings' ) ) {
        Doc_Vista_Settings::get_instance()->save( array_intersect_key( $_POST, Doc_Vista_Settings::get_defaults() ) );
        $saved_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'doc-vista' ) . '</p></div>';
    }

    $settings = Doc_Vista_Settings::get_instance();

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    ?>
    <div class="wrap doc-vista-settings-page">
        <h1><?php esc_html_e( 'Doc Vista Settings', 'doc-vista' ); ?></h1>
        <?php echo wp_kses_post( $saved_notice ); ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'doc_vista_save_settings', 'doc_vista_settings_nonce' ); ?>

            <div class="doc-vista-settings-tabs">
                <nav class="doc-vista-tab-nav">
                    <a href="#doc-vista-tab-appearance" class="doc-vista-tab-active"><?php esc_html_e( 'Appearance', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-typography"><?php esc_html_e( 'Typography', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-layout"><?php esc_html_e( 'Layout', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-toc"><?php esc_html_e( 'TOC Colors', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-highlight"><?php esc_html_e( 'Highlight', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-behavior"><?php esc_html_e( 'Behavior', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-display"><?php esc_html_e( 'Display', 'doc-vista' ); ?></a>
                    <a href="#doc-vista-tab-advanced"><?php esc_html_e( 'Advanced', 'doc-vista' ); ?></a>
                </nav>

                <!-- APPEARANCE -->
                <section id="doc-vista-tab-appearance" class="doc-vista-tab-panel doc-vista-tab-active">
                    <h2><?php esc_html_e( 'Appearance', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control the visual appearance of your documentation.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="doc_vista_theme_color"><?php esc_html_e( 'Theme Accent Color', 'doc-vista' ); ?></label></th>
                            <td>
                                <input type="text" id="doc_vista_theme_color" name="doc_vista_theme_color" value="<?php echo esc_attr( $settings->get( 'doc_vista_theme_color' ) ); ?>" class="doc-vista-color-picker" data-default-color="#2563EB" />
                                <p class="description"><?php esc_html_e( 'Controls active TOC indicators, progress bar color, search focus, and link colors.', 'doc-vista' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Reading Progress Bar', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_reading_progress" value="yes" <?php checked( $settings->get( 'doc_vista_show_reading_progress' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display a reading progress bar at the top of the page', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- TYPOGRAPHY -->
                <section id="doc-vista-tab-typography" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Typography', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control font sizes and line height for documentation content.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="h1_size"><?php esc_html_e( 'H1 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h1_size" name="h1_size" value="<?php echo esc_attr( $settings->get( 'h1_size' ) ); ?>" min="14" max="72" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h2_size"><?php esc_html_e( 'H2 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h2_size" name="h2_size" value="<?php echo esc_attr( $settings->get( 'h2_size' ) ); ?>" min="14" max="60" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h3_size"><?php esc_html_e( 'H3 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h3_size" name="h3_size" value="<?php echo esc_attr( $settings->get( 'h3_size' ) ); ?>" min="12" max="48" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h4_size"><?php esc_html_e( 'H4 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h4_size" name="h4_size" value="<?php echo esc_attr( $settings->get( 'h4_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h5_size"><?php esc_html_e( 'H5 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h5_size" name="h5_size" value="<?php echo esc_attr( $settings->get( 'h5_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h6_size"><?php esc_html_e( 'H6 Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="h6_size" name="h6_size" value="<?php echo esc_attr( $settings->get( 'h6_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="p_size"><?php esc_html_e( 'Paragraph Size', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="p_size" name="p_size" value="<?php echo esc_attr( $settings->get( 'p_size' ) ); ?>" min="10" max="32" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="line_height"><?php esc_html_e( 'Line Height', 'doc-vista' ); ?></label></th>
                            <td><input type="number" id="line_height" name="line_height" value="<?php echo esc_attr( $settings->get( 'line_height' ) ); ?>" min="1.0" max="3.0" step="0.1" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Font Family', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="doc_vista_font_family" value="inherit" <?php checked( $settings->get( 'doc_vista_font_family' ), 'inherit' ); ?> />
                                        <?php esc_html_e( 'Inherit from theme', 'doc-vista' ); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="radio" name="doc_vista_font_family" value="google" <?php checked( $settings->get( 'doc_vista_font_family' ), 'google' ); ?> />
                                        <?php esc_html_e( 'Google Font', 'doc-vista' ); ?>
                                    </label>
                                    <br />
                                    <input type="text" name="doc_vista_google_font" value="<?php echo esc_attr( $settings->get( 'doc_vista_google_font' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Inter', 'doc-vista' ); ?>" style="margin-top:6px" />
                                    <p class="description"><?php esc_html_e( 'Enter the Google Font name. Only one font family is supported.', 'doc-vista' ); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- LAYOUT -->
                <section id="doc-vista-tab-layout" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Layout', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control sidebar position, widths, and TOC depth.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="toc_position"><?php esc_html_e( 'TOC Position', 'doc-vista' ); ?></label></th>
                            <td>
                                <select id="toc_position" name="toc_position">
                                    <option value="left" <?php selected( $settings->get( 'toc_position' ), 'left' ); ?>><?php esc_html_e( 'Left', 'doc-vista' ); ?></option>
                                    <option value="right" <?php selected( $settings->get( 'toc_position' ), 'right' ); ?>><?php esc_html_e( 'Right', 'doc-vista' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sidebar_width"><?php esc_html_e( 'Sidebar Width', 'doc-vista' ); ?></label></th>
                            <td>
                                <input type="number" id="sidebar_width" name="sidebar_width" value="<?php echo esc_attr( $settings->get( 'sidebar_width' ) ); ?>" min="20" max="50" class="small-text" /> %
                                <p class="description"><?php esc_html_e( 'Content area fills the remaining width automatically.', 'doc-vista' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_depth"><?php esc_html_e( 'TOC Depth', 'doc-vista' ); ?></label></th>
                            <td>
                                <select id="toc_depth" name="toc_depth">
                                    <?php for ( $i = 2; $i <= 6; $i++ ) : ?>
                                        <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings->get( 'toc_depth' ), $i ); ?>>
                                            <?php
                                            /* translators: %s: maximum heading level shown in the TOC depth dropdown. */
                                            echo esc_html( sprintf( __( 'H1–H%s', 'doc-vista' ), $i ) );
                                            ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mobile_toc_position"><?php esc_html_e( 'Mobile TOC Position', 'doc-vista' ); ?></label></th>
                            <td>
                                <select id="mobile_toc_position" name="mobile_toc_position">
                                    <option value="top" <?php selected( $settings->get( 'mobile_toc_position' ), 'top' ); ?>><?php esc_html_e( 'Top', 'doc-vista' ); ?></option>
                                    <option value="bottom" <?php selected( $settings->get( 'mobile_toc_position' ), 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'doc-vista' ); ?></option>
                                    <option value="left" <?php selected( $settings->get( 'mobile_toc_position' ), 'left' ); ?>><?php esc_html_e( 'Left', 'doc-vista' ); ?></option>
                                    <option value="right" <?php selected( $settings->get( 'mobile_toc_position' ), 'right' ); ?>><?php esc_html_e( 'Right', 'doc-vista' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Controls the collapsed trigger position on mobile (<= 767px). The TOC panel itself is identical for all positions.', 'doc-vista' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- TOC COLORS -->
                <section id="doc-vista-tab-toc" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'TOC Settings', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Customize the sidebar table of contents appearance and behavior.', 'doc-vista' ); ?></p>

                    <h3 style="margin-top:24px;"><?php esc_html_e( 'Display Mode', 'doc-vista' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Show Subheadings as Hierarchy', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_toc_hierarchical" value="yes" <?php checked( $settings->get( 'doc_vista_toc_hierarchical' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display subheadings in a collapsible tree structure (H2 nested under H1, H3 nested under H2). When disabled, headings appear in a flat column with subtle indentation.', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <h3 style="margin-top:24px;"><?php esc_html_e( 'Colors', 'doc-vista' ); ?></h3>

                    <table class="form-table">
                        <tr>
                            <th><label for="toc_bg"><?php esc_html_e( 'Sidebar Background', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_bg" name="toc_bg" value="<?php echo esc_attr( $settings->get( 'toc_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_text"><?php esc_html_e( 'TOC Text Color', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_text" name="toc_text" value="<?php echo esc_attr( $settings->get( 'toc_text' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_hover"><?php esc_html_e( 'TOC Hover Background', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_hover" name="toc_hover" value="<?php echo esc_attr( $settings->get( 'toc_hover' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_text"><?php esc_html_e( 'Active Item Text', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_active_text" name="toc_active_text" value="<?php echo esc_attr( $settings->get( 'toc_active_text' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_bar"><?php esc_html_e( 'Active Bar Color', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_active_bar" name="toc_active_bar" value="<?php echo esc_attr( $settings->get( 'toc_active_bar' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Active Background Highlight', 'doc-vista' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_active_bg" name="enable_active_bg" value="yes" <?php checked( $settings->get( 'enable_active_bg' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable active heading background', 'doc-vista' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_bg"><?php esc_html_e( 'Active Item Background', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_active_bg" name="toc_active_bg" value="<?php echo esc_attr( $settings->get( 'toc_active_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Heading Background Blocks', 'doc-vista' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_heading_bg" name="enable_heading_bg" value="yes" <?php checked( $settings->get( 'enable_heading_bg' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Show background blocks on TOC headings', 'doc-vista' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_heading_bg"><?php esc_html_e( 'Heading Block Background', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="toc_heading_bg" name="toc_heading_bg" value="<?php echo esc_attr( $settings->get( 'toc_heading_bg' ) ); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <!-- HIGHLIGHT -->
                <section id="doc-vista-tab-highlight" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Search Highlight', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control the color of search result highlights in the documentation content.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="highlight_bg"><?php esc_html_e( 'Highlight Background', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="highlight_bg" name="highlight_bg" value="<?php echo esc_attr( $settings->get( 'highlight_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="highlight_text"><?php esc_html_e( 'Highlight Text Color', 'doc-vista' ); ?></label></th>
                            <td><input type="color" id="highlight_text" name="highlight_text" value="<?php echo esc_attr( $settings->get( 'highlight_text' ) ); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <!-- BEHAVIOR -->
                <section id="doc-vista-tab-behavior" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Behavior', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Miscellaneous plugin behavior options.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Allow Editors', 'doc-vista' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="doc_vista_allow_editors" value="yes" <?php checked( $settings->get( 'doc_vista_allow_editors' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Allow Editors to Manage Documentation', 'doc-vista' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'When enabled, the built-in WordPress Editor role will be able to create, edit, and publish documentation.', 'doc-vista' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Admin Hints', 'doc-vista' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_admin_hint" value="yes" <?php checked( $settings->get( 'show_admin_hint' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Show hints to admins when a doc section is empty', 'doc-vista' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- DISPLAY CONTROLS -->
                <section id="doc-vista-tab-display" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Display Controls', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Show or hide frontend UI elements without editing code.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Search Bar', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_search" value="yes" <?php checked( $settings->get( 'doc_vista_show_search' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show search bar in the documentation sidebar', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Breadcrumbs', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_breadcrumbs" value="yes" <?php checked( $settings->get( 'doc_vista_show_breadcrumbs' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display breadcrumb navigation above documentation content', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Previous Navigation', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_previous" value="yes" <?php checked( $settings->get( 'doc_vista_show_previous' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the Previous button in the page navigation', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Next Navigation', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_next" value="yes" <?php checked( $settings->get( 'doc_vista_show_next' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the Next button in the page navigation', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Navigation Block', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_navigation" value="yes" <?php checked( $settings->get( 'doc_vista_show_navigation' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the entire Previous / Next navigation section at the bottom of each doc', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Table of Contents', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_toc" value="yes" <?php checked( $settings->get( 'doc_vista_show_toc' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the table of contents in the sidebar', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Doc Categories', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_categories" value="yes" <?php checked( $settings->get( 'doc_vista_show_categories' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the category section in the documentation sidebar', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Related Articles', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_related_articles" value="yes" <?php checked( $settings->get( 'doc_vista_show_related_articles' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the related articles section at the bottom of each doc', 'doc-vista' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Navigation Rail', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_show_navigation_rail" value="yes" <?php checked( $settings->get( 'doc_vista_show_navigation_rail' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show a fixed navigation rail with H1/H2 section headings on the opposite side of the TOC', 'doc-vista' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Desktop only. Uses IntersectionObserver for active-state tracking.', 'doc-vista' ); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- ADVANCED -->
                <section id="doc-vista-tab-advanced" class="doc-vista-tab-panel">
                    <h2><?php esc_html_e( 'Advanced', 'doc-vista' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Data persistence and advanced plugin behavior.', 'doc-vista' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Keep Data After Uninstall', 'doc-vista' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="doc_vista_preserve_data" value="yes" <?php checked( $settings->get( 'doc_vista_preserve_data' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Keep plugin data after uninstall', 'doc-vista' ); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e( 'When enabled, uninstalling Doc Vista will remove only the plugin files while preserving all documentation, categories, settings, and configuration in the database. Reinstalling the plugin later allows restoring the previous data.', 'doc-vista' ); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Settings', 'doc-vista' ); ?></button>
            </p>
        </form>

        <hr />

        <div class="doc-vista-settings-info">
            <h2><?php esc_html_e( 'Shortcode Usage', 'doc-vista' ); ?></h2>
            <pre><code>[doc_vista product="category-slug"]</code></pre>
            <p>
                <?php esc_html_e( 'The', 'doc-vista' ); ?>
                <code>product</code>
                <?php esc_html_e( 'attribute matches a', 'doc-vista' ); ?>
                <code>doc_vista_category</code>
                <?php esc_html_e( 'term slug. Use', 'doc-vista' ); ?>
                <code>doc_id="123"</code>
                <?php esc_html_e( 'to show a specific doc by ID.', 'doc-vista' ); ?>
            </p>

            <h2><?php esc_html_e( 'Plugin Info', 'doc-vista' ); ?></h2>
            <table class="widefat fixed" style="width:auto">
                <tr><td><strong><?php esc_html_e( 'Version', 'doc-vista' ); ?></strong></td><td><?php echo esc_html( DOC_VISTA_VERSION ); ?></td></tr>
                <tr><td><strong><?php esc_html_e( 'Post Type', 'doc-vista' ); ?></strong></td><td><code>doc_vista_doc</code></td></tr>
                <tr><td><strong><?php esc_html_e( 'Categories', 'doc-vista' ); ?></strong></td><td><code>doc_vista_category</code></td></tr>
                <tr><td><strong><?php esc_html_e( 'PHP Required', 'doc-vista' ); ?></strong></td><td><?php echo esc_html( '7.4+' ); ?></td></tr>
            </table>

            <h2><?php esc_html_e( 'Documentation Cache', 'doc-vista' ); ?></h2>
            <?php
            $graph = doc_vista_get_graph();
            $cache_time = isset( $graph['built'] ) ? $graph['built'] : 0;
            $total_docs = 0;
            if ( isset( $graph['doc_tree'] ) && is_array( $graph['doc_tree'] ) ) {
                foreach ( $graph['doc_tree'] as $slug => $tree ) {
                    $total_docs += isset( $tree['flat_list'] ) ? count( $tree['flat_list'] ) : 0;
                }
            }
            ?>
            <p><?php esc_html_e( 'The plugin uses a precomputed documentation graph for instant page loads. The cache is rebuilt automatically when docs are saved.', 'doc-vista' ); ?></p>
            <table class="widefat fixed" style="width:auto">
                <tr><td><strong><?php esc_html_e( 'Cached Docs', 'doc-vista' ); ?></strong></td><td><?php echo esc_html( $total_docs ); ?></td></tr>
                <tr><td><strong><?php esc_html_e( 'Last Built', 'doc-vista' ); ?></strong></td><td><?php echo $cache_time ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cache_time ) ) : '—'; ?></td></tr>
            </table>
            <br>
            <form method="post" action="" style="display:inline">
                <?php wp_nonce_field( 'doc_vista_rebuild_cache', 'doc_vista_rebuild_cache_nonce' ); ?>
                <button type="submit" name="doc_vista_rebuild_cache" class="button"><?php esc_html_e( 'Rebuild Cache Now', 'doc-vista' ); ?></button>
            </form>
        </div>
    </div>

    <style>
        .doc-vista-tab-nav a:hover {
            color: <?php echo esc_attr( $settings->get( 'doc_vista_theme_color' ) ); ?>;
        }
        .doc-vista-tab-nav a.doc-vista-tab-active {
            color: <?php echo esc_attr( $settings->get( 'doc_vista_theme_color' ) ); ?>;
            border-bottom-color: <?php echo esc_attr( $settings->get( 'doc_vista_theme_color' ) ); ?>;
        }
    </style>

    <script>
    (function() {
        var tabs = document.querySelectorAll('.doc-vista-tab-nav a');
        var panels = document.querySelectorAll('.doc-vista-tab-panel');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var target = this.getAttribute('href');

                tabs.forEach(function(t) { t.classList.remove('doc-vista-tab-active'); });
                this.classList.add('doc-vista-tab-active');

                panels.forEach(function(p) { p.classList.remove('doc-vista-tab-active'); });
                document.querySelector(target).classList.add('doc-vista-tab-active');

                history.pushState(null, '', target);
            });
        });

        if ( window.location.hash ) {
            var hashTab = document.querySelector('.doc-vista-tab-nav a[href="' + window.location.hash + '"]');
            if ( hashTab ) hashTab.click();
        }

        if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
            jQuery('.doc-vista-color-picker').wpColorPicker();
        }
    })();
    </script>
    <?php
}
