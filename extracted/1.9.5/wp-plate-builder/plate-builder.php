<?php
/*
 * Plugin Name: WP Plate Builder
 * Description: Adds an interactive number plate builder widget for Elementor.  Site editors can let visitors design and preview their own UK registration plates with custom sizes, colours, borders, text styles and more.
 * Version: 1.9.5
 * Author: You Can Fly Media
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class to register the Elementor widget.
 */
class WP_Plate_Builder_Plugin {

    /**
     * Constructor hooks into WordPress actions.
     */
    public function __construct() {
        // Hook into Elementor widgets registration. Register on both new and legacy
        // hooks to maximise compatibility across Elementor versions.
        // Register the widget on Elementor's widgets/register action (3.5+) and
        // on the legacy widgets_registered action for older versions. A guard
        // inside register_widget ensures the widget is only registered once.
        add_action( 'elementor/widgets/register', [ $this, 'register_widget' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widget' ] );
        // Register scripts and styles when Elementor is enqueued.
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

        // Note: This plugin is primarily designed for Elementor and provides a
        // dedicated widget. A shortcode is no longer registered by default.

        // Add an admin menu page for general settings and information.
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
    }

    /**
     * Registers the widget with Elementor's widgets manager.
     *
     * @param Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
     */
    public function register_widget( $widgets_manager ) {
        // Ensure Elementor is loaded before registering.
        if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }
        // Prevent registering the widget multiple times if both hooks fire.
        static $registered = false;
        if ( $registered ) {
            return;
        }
        $registered = true;
        require_once __DIR__ . '/widgets/class-plate-builder-widget.php';
        $widgets_manager->register( new \WP_Plate_Builder_Widget() );
    }

    /**
     * Registers and enqueues front‑end assets used by the widget.
     */
    public function register_assets() {
        /*
         * Bump the version numbers on the registered assets to ensure browsers
         * and caching proxies fetch the latest CSS/JS when the plugin is
         * updated.  Updating this constant will append a new query string
         * (e.g. ?ver=1.0.1) to the asset URLs, forcing cache invalidation.
         */
        if ( ! defined( 'WP_PLATE_BUILDER_VERSION' ) ) {
            // Update the asset version whenever the plugin changes to force caches to reload assets
            define( 'WP_PLATE_BUILDER_VERSION', '1.9.5' );
        }
        // Register CSS with the plugin version.  Additional dependencies can be
        // added to the third parameter if needed.
        wp_register_style(
            'wp-plate-builder-style',
            plugins_url( 'assets/css/plate-builder.css', __FILE__ ),
            [],
            WP_PLATE_BUILDER_VERSION
        );
        // Register JS with the plugin version and elementor-frontend as a dependency.
        wp_register_script(
            'wp-plate-builder-script',
            plugins_url( 'assets/js/plate-builder.v170.js', __FILE__ ),
            [ 'elementor-frontend' ],
            WP_PLATE_BUILDER_VERSION,
            true
        );
    }

    /**
     * Adds a top‑level menu page in the WordPress admin sidebar. The page
     * provides information and future settings for the plate builder plugin.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Plate Builder', 'wp-plate-builder' ),
            __( 'Plate Builder', 'wp-plate-builder' ),
            'manage_options',
            'wp-plate-builder-settings',
            [ $this, 'render_settings_page' ],
            'dashicons-admin-generic',
            56
        );
    }

    /**
     * Renders the admin settings page. Currently this page provides an overview
     * of the plugin and explains that most customisation happens in the
     * Elementor editor. Future versions may expose global defaults here.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WP Plate Builder Settings', 'wp-plate-builder' ); ?></h1>
            <p><?php esc_html_e( 'Thank you for using the WP Plate Builder plugin!', 'wp-plate-builder' ); ?></p>
            <p><?php esc_html_e( 'The plate builder is configured via the Elementor widget. When editing a page with Elementor, select the Number Plate Builder widget to customise plate sizes, styles, colours and pricing. These settings are saved per widget instance.', 'wp-plate-builder' ); ?></p>
            <p><?php esc_html_e( 'This settings page will be expanded in future versions to include global defaults and additional configuration options.', 'wp-plate-builder' ); ?></p>
        </div>
        <?php
    }

}

if ( ! function_exists( 'wp_plate_builder_render_markup' ) ) {
    /**
     * Outputs the HTML markup for the plate builder. Used by both the Elementor
     * widget and the shortcode. This function echoes directly and assumes
     * styles/scripts have been enqueued prior to invocation.
     *
     * @param string $heading    Heading text displayed above the builder.
     * @param string $subheading Subheading text displayed below the heading.
     */
    function wp_plate_builder_render_markup( $heading, $subheading ) {
        ?>
        <div class="wp-plate-builder-container">
            <h2 class="wp-plate-builder-heading"><?php echo esc_html( $heading ); ?></h2>
            <p class="wp-plate-builder-subheading"><?php echo esc_html( $subheading ); ?></p>
            <div class="wp-plate-builder-wrapper">
                <!-- Sidebar of options -->
                <div class="plate-options">
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate Type', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <label><input type="radio" name="plate_use_type" value="road" checked> <?php esc_html_e( 'Road legal', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_use_type" value="show"> <?php esc_html_e( 'Show plate', 'wp-plate-builder' ); ?></label>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plates required', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <label><input type="radio" name="plate_count" value="both" checked> <?php esc_html_e( 'Both', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_count" value="front"> <?php esc_html_e( 'Front only', 'wp-plate-builder' ); ?></label><br>
                            <label><input type="radio" name="plate_count" value="rear"> <?php esc_html_e( 'Rear only', 'wp-plate-builder' ); ?></label>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Registration', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <input type="text" name="registration" class="plate-registration" maxlength="10" placeholder="YOUR REG">
                            <!-- Error message for invalid registration format -->
                            <div class="reg-error" style="display:none;color:#c62828;font-size:0.8em; margin-top:4px;"></div>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate size', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="plate_size" class="plate-size">
                                <option value="std"><?php esc_html_e( 'Standard (520mm x 111mm)', 'wp-plate-builder' ); ?></option>
                                <option value="short"><?php esc_html_e( 'Short (406mm x 111mm)', 'wp-plate-builder' ); ?></option>
                                <option value="square"><?php esc_html_e( 'Square (285mm x 203mm)', 'wp-plate-builder' ); ?></option>
                                <option value="motorcycle"><?php esc_html_e( 'Motorcycle (178mm x 127mm)', 'wp-plate-builder' ); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Text style', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="text_style" class="text-style">
                                <option value="standard"><?php esc_html_e( 'Standard', 'wp-plate-builder' ); ?></option>
                                <option value="3d"><?php esc_html_e( '3D Gel Domed', 'wp-plate-builder' ); ?></option>
                                <option value="4d"><?php esc_html_e( '4D Perspex', 'wp-plate-builder' ); ?></option>
                                <option value="5d"><?php esc_html_e( '5D Gel Perspex', 'wp-plate-builder' ); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Badge & colour', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content badge-picker">
                            <div class="badge-colour" data-colour="none" style="background:transparent; border:1px solid #ccc;">None</div>
                            <div class="badge-colour" data-colour="#0055a5" style="background:#0055a5;"></div>
                            <div class="badge-colour" data-colour="#cf142b" style="background:#cf142b;"></div>
                            <div class="badge-colour" data-colour="#fcd116" style="background:#fcd116;"></div>
                            <div class="badge-colour" data-colour="#00a650" style="background:#00a650;"></div>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Border colour', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content border-picker">
                            <div class="border-colour" data-colour="transparent" style="background:transparent; border:1px solid #ccc;">None</div>
                            <div class="border-colour" data-colour="#000000" style="background:#000"></div>
                            <div class="border-colour" data-colour="#ffffff" style="background:#fff; border:1px solid #ccc;"></div>
                            <div class="border-colour" data-colour="#cf142b" style="background:#cf142b;"></div>
                            <div class="border-colour" data-colour="#0055a5" style="background:#0055a5;"></div>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Plate surround', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="plate_surround" class="plate-surround">
                                <option value="none"><?php esc_html_e( 'None', 'wp-plate-builder' ); ?></option>
                                <option value="plain"><?php esc_html_e( 'Plain black', 'wp-plate-builder' ); ?> (+£28)</option>
                                <option value="marque"><?php esc_html_e( 'Marque branded', 'wp-plate-builder' ); ?> (+£28)</option>
                            </select>
                            <small><?php esc_html_e( '£28 each or 2 for £50', 'wp-plate-builder' ); ?></small>
                        </div>
                    </div>
                    <div class="option-section">
                        <div class="option-header">
                            <?php esc_html_e( 'Electric car plate', 'wp-plate-builder' ); ?>
                        </div>
                        <div class="option-content">
                            <select name="electric" class="electric-option">
                                <option value="none"><?php esc_html_e( 'None', 'wp-plate-builder' ); ?></option>
                                <option value="green"><?php esc_html_e( 'Green flash', 'wp-plate-builder' ); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Preview area -->
                <div class="plate-preview">
                    <div class="preview-plates">
                        <!--
                          The preview plates are now rendered using inline SVG rather than nested
                          divs.  Each <svg> acts as a canvas for the plate background, arrow
                          shape (for hex plates), badge strip and registration text.  The
                          JavaScript associated with this widget dynamically populates these
                          SVGs based on the selected options.  Empty SVG elements are output
                          here as placeholders; their contents are injected client‑side.
                        -->
                        <svg class="plate front-plate" xmlns="http://www.w3.org/2000/svg"></svg>
                        <svg class="plate rear-plate" xmlns="http://www.w3.org/2000/svg"></svg>
                    </div>
                    <div class="plate-price">
                        <?php esc_html_e( 'Price:', 'wp-plate-builder' ); ?> £<span class="price-value">28.90</span>
                    </div>
                    <button class="plate-buy-button">
                        <?php esc_html_e( 'Buy Now', 'wp-plate-builder' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialise the plugin.
new WP_Plate_Builder_Plugin();