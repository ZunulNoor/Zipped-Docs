<?php
/**
 * Zipped Docs — Centralized Settings Service
 *
 * Single source of truth for all plugin settings.
 * No component should call get_option() directly.
 *
 * @package zipped_docs
 */

defined( 'ABSPATH' ) || exit;

class Zipped_Docs_Settings {

    const OPTION_NAME = 'zipped_docs_settings';

    private static $instance = null;
    private $settings = array();
    private $loaded = false;

    private function __construct() {}

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get( $key, $default = null ) {
        $this->ensure_loaded();
        return array_key_exists( $key, $this->settings ) ? $this->settings[ $key ] : $default;
    }

    public function all() {
        $this->ensure_loaded();
        return $this->settings;
    }

    public function reload() {
        $this->loaded = false;
        $this->settings = array();
        $this->ensure_loaded();
    }

    public static function get_defaults() {
        return array(
            // Appearance
            'zipped_docs_theme_color'         => '#2563EB',
            'zipped_docs_show_reading_progress' => 'no',

            // Typography
            'h1_size'         => 32,
            'h2_size'         => 24,
            'h3_size'         => 19,
            'h4_size'         => 17,
            'h5_size'         => 16,
            'h6_size'         => 15,
            'p_size'          => 14,
            'line_height'     => 1.7,
            'zipped_docs_font_family' => 'inherit',
            'zipped_docs_google_font' => '',

            // Layout
            'toc_depth'             => 6,
            'toc_position'          => 'left',
            'sidebar_width'         => 30,
            'mobile_toc_position'   => 'top',
            'zipped_docs_toc_hierarchical' => 'no',

            // TOC Colors
            'toc_bg'           => '#f8f9fb',
            'toc_text'         => '#475569',
            'toc_hover'        => '#f0f2f5',
            'toc_active_text'  => '#993C1D',
            'enable_active_bg' => 'no',
            'toc_active_bg'    => '#fef0e9',
            'toc_active_bar'   => '#E8500A',
            'enable_heading_bg' => 'no',
            'toc_heading_bg'   => '#f0f2f5',

            // Highlight
            'highlight_bg'     => '#ffcc00',
            'highlight_text'   => '#000000',

            // Behavior
            'zipped_docs_allow_editors'  => 'no',
            'show_admin_hint'          => 'yes',

            // Display toggles
            'zipped_docs_show_search'          => 'yes',
            'zipped_docs_show_breadcrumbs'     => 'yes',
            'zipped_docs_show_previous'        => 'yes',
            'zipped_docs_show_next'            => 'yes',
            'zipped_docs_show_navigation'      => 'yes',
            'zipped_docs_show_toc'             => 'yes',
            'zipped_docs_show_categories'      => 'yes',
            'zipped_docs_show_related_articles'  => 'yes',
            'zipped_docs_show_navigation_rail'   => 'yes',

            // Data persistence
            'zipped_docs_preserve_data'        => 'yes',
        );
    }

    public static function get_toggle_keys() {
        return array(
            'zipped_docs_show_reading_progress',
            'zipped_docs_allow_editors',
            'show_admin_hint',
            'enable_active_bg',
            'enable_heading_bg',
            'zipped_docs_show_search',
            'zipped_docs_show_breadcrumbs',
            'zipped_docs_show_previous',
            'zipped_docs_show_next',
            'zipped_docs_show_navigation',
            'zipped_docs_show_toc',
            'zipped_docs_show_categories',
            'zipped_docs_show_related_articles',
            'zipped_docs_show_navigation_rail',
            'zipped_docs_preserve_data',
            'zipped_docs_toc_hierarchical',
        );
    }

    public static function get_display_settings() {
        $settings = self::get_instance()->all();
        return array(
            'show_breadcrumbs'      => 'yes' === $settings['zipped_docs_show_breadcrumbs'],
            'show_previous'         => 'yes' === $settings['zipped_docs_show_previous'],
            'show_next'             => 'yes' === $settings['zipped_docs_show_next'],
            'show_navigation'       => 'yes' === $settings['zipped_docs_show_navigation'],
            'show_related'          => 'yes' === $settings['zipped_docs_show_related_articles'],
            'show_navigation_rail'  => 'yes' === $settings['zipped_docs_show_navigation_rail'],
            'toc_hierarchical'      => 'yes' === $settings['zipped_docs_toc_hierarchical'],
        );
    }

    public function save( $input ) {
        $defaults = self::get_defaults();
        $toggle_keys = self::get_toggle_keys();
        $sanitized = array();

        foreach ( $defaults as $key => $default_value ) {
            $value = array_key_exists( $key, $input ) ? $input[ $key ] : null;

            if ( null === $value && in_array( $key, $toggle_keys, true ) ) {
                $value = 'no';
            }

            if ( null === $value ) {
                $sanitized[ $key ] = $default_value;
                continue;
            }

            $sanitized[ $key ] = $this->sanitize_field( $key, $value, $default_value );
        }

        update_option( self::OPTION_NAME, $sanitized );
        $this->reload();

        do_action( 'zipped_docs_settings_saved', $sanitized );
    }

    private function sanitize_field( $key, $value, $default_value ) {
        switch ( true ) {
            case in_array( $key, array( 'h1_size', 'h2_size', 'h3_size', 'h4_size', 'h5_size', 'h6_size', 'p_size' ), true ):
                $min = 10;
                $max = 72;
                if ( 'h1_size' === $key ) { $min = 14; $max = 72; }
                if ( 'h2_size' === $key ) { $min = 14; $max = 60; }
                if ( 'h3_size' === $key ) { $min = 12; $max = 48; }
                if ( 'h4_size' === $key ) { $min = 12; $max = 40; }
                if ( 'h5_size' === $key ) { $min = 12; $max = 40; }
                if ( 'h6_size' === $key ) { $min = 12; $max = 40; }
                if ( 'p_size'  === $key ) { $min = 10; $max = 32; }
                return max( $min, min( $max, (int) $value ) );

            case 'line_height' === $key:
                return round( max( 1.0, min( 3.0, (float) $value ) ), 1 );

            case 'toc_depth' === $key:
                return max( 2, min( 6, (int) $value ) );

            case 'toc_position' === $key:
                return in_array( (string) $value, array( 'left', 'right' ), true ) ? $value : 'left';

            case 'mobile_toc_position' === $key:
                return in_array( (string) $value, array( 'top', 'bottom', 'left', 'right' ), true ) ? $value : 'top';

            case 'sidebar_width' === $key:
                return max( 20, min( 50, (int) $value ) );

            case in_array( $key, array(
                'zipped_docs_theme_color',
                'toc_bg', 'toc_text', 'toc_hover', 'toc_active_text',
                'toc_active_bg', 'toc_active_bar', 'toc_heading_bg',
                'highlight_bg', 'highlight_text',
            ), true ):
                $sanitized = sanitize_hex_color( (string) $value );
                return $sanitized ?: $default_value;

            case 'zipped_docs_font_family' === $key:
                return in_array( (string) $value, array( 'inherit', 'google' ), true ) ? $value : 'inherit';

            case 'zipped_docs_google_font' === $key:
                return sanitize_text_field( (string) $value );

            case in_array( $key, self::get_toggle_keys(), true ):
                return ! empty( $value ) && 'yes' === (string) $value ? 'yes' : 'no';

            default:
                return sanitize_text_field( (string) $value );
        }
    }

    private function ensure_loaded() {
        if ( $this->loaded ) {
            return;
        }

        $saved = get_option( self::OPTION_NAME, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $this->settings = array_merge( self::get_defaults(), $saved );
        $this->loaded = true;
    }

    public static function register() {
        $group = 'zipped_docs_settings_group';
        register_setting( $group, self::OPTION_NAME, array(
            'sanitize_callback' => array( self::class, 'sanitize_callback' ),
            'default'           => self::get_defaults(),
            'show_in_rest'      => false,
        ) );
    }

    public static function sanitize_callback( $input ) {
        if ( ! is_array( $input ) ) {
            return self::get_defaults();
        }
        $instance = self::get_instance();
        $defaults = self::get_defaults();
        $toggle_keys = self::get_toggle_keys();
        $sanitized = array();

        foreach ( $defaults as $key => $default_value ) {
            $value = array_key_exists( $key, $input ) ? $input[ $key ] : null;

            if ( null === $value && in_array( $key, $toggle_keys, true ) ) {
                $value = 'no';
            }

            if ( null === $value ) {
                $sanitized[ $key ] = $default_value;
                continue;
            }

            $sanitized[ $key ] = $instance->sanitize_field( $key, $value, $default_value );
        }

        return $sanitized;
    }

}

function zipped_docs_get_settings() {
    return Zipped_Docs_Settings::get_instance()->all();
}
