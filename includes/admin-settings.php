<?php

defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', 'zipped_docs_register_settings' );
function zipped_docs_register_settings() {
    Zipped_Docs_Settings::register();
}

function zipped_docs_admin_settings_page() {
    if ( ! current_user_can( 'zipped_docs_manage_settings' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions.', 'zipped-docs' ) );
    }

    $saved_notice = '';

    $rebuild_nonce = isset( $_POST['zipped_docs_rebuild_cache_nonce'] ) ? sanitize_key( $_POST['zipped_docs_rebuild_cache_nonce'] ) : '';
    if ( current_user_can( 'zipped_docs_manage_settings' ) && isset( $_POST['zipped_docs_rebuild_cache'] ) && wp_verify_nonce( $rebuild_nonce, 'zipped_docs_rebuild_cache' ) ) {
        zipped_docs_rebuild_graph();
        $saved_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Documentation cache rebuilt successfully.', 'zipped-docs' ) . '</p></div>';
    }

    $settings_nonce = isset( $_POST['zipped_docs_settings_nonce'] ) ? sanitize_key( $_POST['zipped_docs_settings_nonce'] ) : '';
    if ( current_user_can( 'zipped_docs_manage_settings' ) && isset( $_POST['zipped_docs_settings_nonce'] ) && wp_verify_nonce( $settings_nonce, 'zipped_docs_save_settings' ) ) {
        $settings_input = array_intersect_key( wp_unslash( $_POST ), Zipped_Docs_Settings::get_defaults() );
        Zipped_Docs_Settings::get_instance()->save( $settings_input );
        $saved_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'zipped-docs' ) . '</p></div>';
    }

    $settings = Zipped_Docs_Settings::get_instance();

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    ?>
    <div class="wrap zipped-docs-settings-page">
        <h1><?php esc_html_e( 'Zipped Docs Settings', 'zipped-docs' ); ?></h1>
        <?php echo wp_kses_post( $saved_notice ); ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'zipped_docs_save_settings', 'zipped_docs_settings_nonce' ); ?>

            <div class="zipped-docs-settings-tabs">
                <nav class="zipped-docs-tab-nav">
                    <a href="#zipped-docs-tab-appearance" class="zipped-docs-tab-active"><?php esc_html_e( 'Appearance', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-typography"><?php esc_html_e( 'Typography', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-layout"><?php esc_html_e( 'Layout', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-toc"><?php esc_html_e( 'TOC Colors', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-highlight"><?php esc_html_e( 'Highlight', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-behavior"><?php esc_html_e( 'Behavior', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-display"><?php esc_html_e( 'Display', 'zipped-docs' ); ?></a>
                    <a href="#zipped-docs-tab-advanced"><?php esc_html_e( 'Advanced', 'zipped-docs' ); ?></a>
                </nav>

                <!-- APPEARANCE -->
                <section id="zipped-docs-tab-appearance" class="zipped-docs-tab-panel zipped-docs-tab-active">
                    <h2><?php esc_html_e( 'Appearance', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control the visual appearance of your documentation.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="zipped_docs_theme_color"><?php esc_html_e( 'Theme Accent Color', 'zipped-docs' ); ?></label></th>
                            <td>
                                <input type="text" id="zipped_docs_theme_color" name="zipped_docs_theme_color" value="<?php echo esc_attr( $settings->get( 'zipped_docs_theme_color' ) ); ?>" class="zipped-docs-color-picker" data-default-color="#2563EB" />
                                <p class="description"><?php esc_html_e( 'Controls active TOC indicators, progress bar color, search focus, and link colors.', 'zipped-docs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Reading Progress Bar', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_reading_progress" value="yes" <?php checked( $settings->get( 'zipped_docs_show_reading_progress' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display a reading progress bar at the top of the page', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- TYPOGRAPHY -->
                <section id="zipped-docs-tab-typography" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Typography', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control font sizes and line height for documentation content.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="h1_size"><?php esc_html_e( 'H1 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h1_size" name="h1_size" value="<?php echo esc_attr( $settings->get( 'h1_size' ) ); ?>" min="14" max="72" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h2_size"><?php esc_html_e( 'H2 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h2_size" name="h2_size" value="<?php echo esc_attr( $settings->get( 'h2_size' ) ); ?>" min="14" max="60" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h3_size"><?php esc_html_e( 'H3 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h3_size" name="h3_size" value="<?php echo esc_attr( $settings->get( 'h3_size' ) ); ?>" min="12" max="48" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h4_size"><?php esc_html_e( 'H4 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h4_size" name="h4_size" value="<?php echo esc_attr( $settings->get( 'h4_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h5_size"><?php esc_html_e( 'H5 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h5_size" name="h5_size" value="<?php echo esc_attr( $settings->get( 'h5_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="h6_size"><?php esc_html_e( 'H6 Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="h6_size" name="h6_size" value="<?php echo esc_attr( $settings->get( 'h6_size' ) ); ?>" min="12" max="40" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="p_size"><?php esc_html_e( 'Paragraph Size', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="p_size" name="p_size" value="<?php echo esc_attr( $settings->get( 'p_size' ) ); ?>" min="10" max="32" class="small-text" /> px</td>
                        </tr>
                        <tr>
                            <th><label for="line_height"><?php esc_html_e( 'Line Height', 'zipped-docs' ); ?></label></th>
                            <td><input type="number" id="line_height" name="line_height" value="<?php echo esc_attr( $settings->get( 'line_height' ) ); ?>" min="1.0" max="3.0" step="0.1" class="small-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Font Family', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="zipped_docs_font_family" value="inherit" <?php checked( $settings->get( 'zipped_docs_font_family' ), 'inherit' ); ?> />
                                        <?php esc_html_e( 'Inherit from theme', 'zipped-docs' ); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="radio" name="zipped_docs_font_family" value="google" <?php checked( $settings->get( 'zipped_docs_font_family' ), 'google' ); ?> />
                                        <?php esc_html_e( 'Google Font', 'zipped-docs' ); ?>
                                    </label>
                                    <br />
                                    <input type="text" name="zipped_docs_google_font" value="<?php echo esc_attr( $settings->get( 'zipped_docs_google_font' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Inter', 'zipped-docs' ); ?>" style="margin-top:6px" />
                                    <p class="description"><?php esc_html_e( 'Enter the Google Font name. Only one font family is supported.', 'zipped-docs' ); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- LAYOUT -->
                <section id="zipped-docs-tab-layout" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Layout', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control sidebar position, widths, and TOC depth.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="toc_position"><?php esc_html_e( 'TOC Position', 'zipped-docs' ); ?></label></th>
                            <td>
                                <select id="toc_position" name="toc_position">
                                    <option value="left" <?php selected( $settings->get( 'toc_position' ), 'left' ); ?>><?php esc_html_e( 'Left', 'zipped-docs' ); ?></option>
                                    <option value="right" <?php selected( $settings->get( 'toc_position' ), 'right' ); ?>><?php esc_html_e( 'Right', 'zipped-docs' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="sidebar_width"><?php esc_html_e( 'Sidebar Width', 'zipped-docs' ); ?></label></th>
                            <td>
                                <input type="number" id="sidebar_width" name="sidebar_width" value="<?php echo esc_attr( $settings->get( 'sidebar_width' ) ); ?>" min="20" max="50" class="small-text" /> %
                                <p class="description"><?php esc_html_e( 'Content area fills the remaining width automatically.', 'zipped-docs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_depth"><?php esc_html_e( 'TOC Depth', 'zipped-docs' ); ?></label></th>
                            <td>
                                <select id="toc_depth" name="toc_depth">
                                    <?php for ( $i = 2; $i <= 6; $i++ ) : ?>
                                        <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings->get( 'toc_depth' ), $i ); ?>>
                                            <?php
                                            /* translators: %s: maximum heading level shown in the TOC depth dropdown. */
                                            echo esc_html( sprintf( __( 'H1–H%s', 'zipped-docs' ), $i ) );
                                            ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="mobile_toc_position"><?php esc_html_e( 'Mobile TOC Position', 'zipped-docs' ); ?></label></th>
                            <td>
                                <select id="mobile_toc_position" name="mobile_toc_position">
                                    <option value="top" <?php selected( $settings->get( 'mobile_toc_position' ), 'top' ); ?>><?php esc_html_e( 'Top', 'zipped-docs' ); ?></option>
                                    <option value="bottom" <?php selected( $settings->get( 'mobile_toc_position' ), 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'zipped-docs' ); ?></option>
                                    <option value="left" <?php selected( $settings->get( 'mobile_toc_position' ), 'left' ); ?>><?php esc_html_e( 'Left', 'zipped-docs' ); ?></option>
                                    <option value="right" <?php selected( $settings->get( 'mobile_toc_position' ), 'right' ); ?>><?php esc_html_e( 'Right', 'zipped-docs' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Controls the collapsed trigger position on mobile (<= 767px). The TOC panel itself is identical for all positions.', 'zipped-docs' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- TOC COLORS -->
                <section id="zipped-docs-tab-toc" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'TOC Settings', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Customize the sidebar table of contents appearance and behavior.', 'zipped-docs' ); ?></p>

                    <h3 style="margin-top:24px;"><?php esc_html_e( 'Display Mode', 'zipped-docs' ); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Show Subheadings as Hierarchy', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_toc_hierarchical" value="yes" <?php checked( $settings->get( 'zipped_docs_toc_hierarchical' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display subheadings in a collapsible tree structure (H2 nested under H1, H3 nested under H2). When disabled, headings appear in a flat column with subtle indentation.', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <h3 style="margin-top:24px;"><?php esc_html_e( 'Colors', 'zipped-docs' ); ?></h3>

                    <table class="form-table">
                        <tr>
                            <th><label for="toc_bg"><?php esc_html_e( 'Sidebar Background', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_bg" name="toc_bg" value="<?php echo esc_attr( $settings->get( 'toc_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_text"><?php esc_html_e( 'TOC Text Color', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_text" name="toc_text" value="<?php echo esc_attr( $settings->get( 'toc_text' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_hover"><?php esc_html_e( 'TOC Hover Background', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_hover" name="toc_hover" value="<?php echo esc_attr( $settings->get( 'toc_hover' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_text"><?php esc_html_e( 'Active Item Text', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_active_text" name="toc_active_text" value="<?php echo esc_attr( $settings->get( 'toc_active_text' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_bar"><?php esc_html_e( 'Active Bar Color', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_active_bar" name="toc_active_bar" value="<?php echo esc_attr( $settings->get( 'toc_active_bar' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Active Background Highlight', 'zipped-docs' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_active_bg" name="enable_active_bg" value="yes" <?php checked( $settings->get( 'enable_active_bg' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Enable active heading background', 'zipped-docs' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_active_bg"><?php esc_html_e( 'Active Item Background', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_active_bg" name="toc_active_bg" value="<?php echo esc_attr( $settings->get( 'toc_active_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Heading Background Blocks', 'zipped-docs' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enable_heading_bg" name="enable_heading_bg" value="yes" <?php checked( $settings->get( 'enable_heading_bg' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Show background blocks on TOC headings', 'zipped-docs' ); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="toc_heading_bg"><?php esc_html_e( 'Heading Block Background', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="toc_heading_bg" name="toc_heading_bg" value="<?php echo esc_attr( $settings->get( 'toc_heading_bg' ) ); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <!-- HIGHLIGHT -->
                <section id="zipped-docs-tab-highlight" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Search Highlight', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Control the color of search result highlights in the documentation content.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><label for="highlight_bg"><?php esc_html_e( 'Highlight Background', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="highlight_bg" name="highlight_bg" value="<?php echo esc_attr( $settings->get( 'highlight_bg' ) ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="highlight_text"><?php esc_html_e( 'Highlight Text Color', 'zipped-docs' ); ?></label></th>
                            <td><input type="color" id="highlight_text" name="highlight_text" value="<?php echo esc_attr( $settings->get( 'highlight_text' ) ); ?>" /></td>
                        </tr>
                    </table>
                </section>

                <!-- BEHAVIOR -->
                <section id="zipped-docs-tab-behavior" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Behavior', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Miscellaneous plugin behavior options.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Allow Editors', 'zipped-docs' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="zipped_docs_allow_editors" value="yes" <?php checked( $settings->get( 'zipped_docs_allow_editors' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Allow Editors to Manage Documentation', 'zipped-docs' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'When enabled, the built-in WordPress Editor role will be able to create, edit, and publish documentation.', 'zipped-docs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Admin Hints', 'zipped-docs' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="show_admin_hint" value="yes" <?php checked( $settings->get( 'show_admin_hint' ), 'yes' ); ?> />
                                    <?php esc_html_e( 'Show hints to admins when a doc section is empty', 'zipped-docs' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- DISPLAY CONTROLS -->
                <section id="zipped-docs-tab-display" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Display Controls', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Show or hide frontend UI elements without editing code.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Search Bar', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_search" value="yes" <?php checked( $settings->get( 'zipped_docs_show_search' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show search bar in the documentation sidebar', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Breadcrumbs', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_breadcrumbs" value="yes" <?php checked( $settings->get( 'zipped_docs_show_breadcrumbs' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Display breadcrumb navigation above documentation content', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Previous Navigation', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_previous" value="yes" <?php checked( $settings->get( 'zipped_docs_show_previous' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the Previous button in the page navigation', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Next Navigation', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_next" value="yes" <?php checked( $settings->get( 'zipped_docs_show_next' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the Next button in the page navigation', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Navigation Block', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_navigation" value="yes" <?php checked( $settings->get( 'zipped_docs_show_navigation' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the entire Previous / Next navigation section at the bottom of each doc', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Table of Contents', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_toc" value="yes" <?php checked( $settings->get( 'zipped_docs_show_toc' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the table of contents in the sidebar', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Doc Categories', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_categories" value="yes" <?php checked( $settings->get( 'zipped_docs_show_categories' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the category section in the documentation sidebar', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Related Articles', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_related_articles" value="yes" <?php checked( $settings->get( 'zipped_docs_show_related_articles' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show the related articles section at the bottom of each doc', 'zipped-docs' ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Navigation Rail', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_show_navigation_rail" value="yes" <?php checked( $settings->get( 'zipped_docs_show_navigation_rail' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Show a fixed navigation rail with H1/H2 section headings on the opposite side of the TOC', 'zipped-docs' ); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Desktop only. Uses IntersectionObserver for active-state tracking.', 'zipped-docs' ); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>

                <!-- ADVANCED -->
                <section id="zipped-docs-tab-advanced" class="zipped-docs-tab-panel">
                    <h2><?php esc_html_e( 'Advanced', 'zipped-docs' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Data persistence and advanced plugin behavior.', 'zipped-docs' ); ?></p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'Keep Data After Uninstall', 'zipped-docs' ); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="zipped_docs_preserve_data" value="yes" <?php checked( $settings->get( 'zipped_docs_preserve_data' ), 'yes' ); ?> />
                                        <?php esc_html_e( 'Keep plugin data after uninstall', 'zipped-docs' ); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e( 'When enabled, uninstalling Zipped Docs will remove only the plugin files while preserving all documentation, categories, settings, and configuration in the database. Reinstalling the plugin later allows restoring the previous data.', 'zipped-docs' ); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </section>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Save Settings', 'zipped-docs' ); ?></button>
            </p>
        </form>

        <hr />

        <div class="zipped-docs-settings-info">
            <h2><?php esc_html_e( 'Shortcode Usage', 'zipped-docs' ); ?></h2>
            <pre><code>[zippeddocs product="category-slug"]</code></pre>
            <p>
                <?php esc_html_e( 'The', 'zipped-docs' ); ?>
                <code>product</code>
                <?php esc_html_e( 'attribute matches a', 'zipped-docs' ); ?>
                <code>zipped_docs_category</code>
                <?php esc_html_e( 'term slug. Use', 'zipped-docs' ); ?>
                <code>doc_id="123"</code>
                <?php esc_html_e( 'to show a specific doc by ID.', 'zipped-docs' ); ?>
            </p>

            <h2><?php esc_html_e( 'Plugin Info', 'zipped-docs' ); ?></h2>
            <table class="widefat fixed" style="width:auto">
                <tr><td><strong><?php esc_html_e( 'Version', 'zipped-docs' ); ?></strong></td><td><?php echo esc_html( ZIPPED_DOCS_VERSION ); ?></td></tr>
                <tr><td><strong><?php esc_html_e( 'Post Type', 'zipped-docs' ); ?></strong></td><td><code>zipped_docs_doc</code></td></tr>
                <tr><td><strong><?php esc_html_e( 'Categories', 'zipped-docs' ); ?></strong></td><td><code>zipped_docs_category</code></td></tr>
                <tr><td><strong><?php esc_html_e( 'PHP Required', 'zipped-docs' ); ?></strong></td><td><?php echo esc_html( '7.4+' ); ?></td></tr>
            </table>

            <h2><?php esc_html_e( 'Documentation Cache', 'zipped-docs' ); ?></h2>
            <?php
            $graph = zipped_docs_get_graph();
            $cache_time = isset( $graph['built'] ) ? $graph['built'] : 0;
            $total_docs = 0;
            if ( isset( $graph['doc_tree'] ) && is_array( $graph['doc_tree'] ) ) {
                foreach ( $graph['doc_tree'] as $slug => $tree ) {
                    $total_docs += isset( $tree['flat_list'] ) ? count( $tree['flat_list'] ) : 0;
                }
            }
            ?>
            <p><?php esc_html_e( 'The plugin uses a precomputed documentation graph for instant page loads. The cache is rebuilt automatically when docs are saved.', 'zipped-docs' ); ?></p>
            <table class="widefat fixed" style="width:auto">
                <tr><td><strong><?php esc_html_e( 'Cached Docs', 'zipped-docs' ); ?></strong></td><td><?php echo esc_html( $total_docs ); ?></td></tr>
                <tr><td><strong><?php esc_html_e( 'Last Built', 'zipped-docs' ); ?></strong></td><td><?php echo $cache_time ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $cache_time ) ) : '—'; ?></td></tr>
            </table>
            <br>
            <form method="post" action="" style="display:inline">
                <?php wp_nonce_field( 'zipped_docs_rebuild_cache', 'zipped_docs_rebuild_cache_nonce' ); ?>
                <button type="submit" name="zipped_docs_rebuild_cache" class="button"><?php esc_html_e( 'Rebuild Cache Now', 'zipped-docs' ); ?></button>
            </form>
        </div>
    </div>
    <?php
}
