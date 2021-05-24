<?php
/**
 * Plugin Name: Easy Digital Downloads - Widgets Pack
 * Plugin URI: https://easydigitaldownloads.com/downloads/widgets-bundle/
 * Description: A pack of widgets for Easy Digital Downloads.
 * Version: 1.2.6
 * Author: Sandhills Development, LLC
 * Author URI: https://sandhillsdev.com
 * Contributors: sksmatt
 * Text Domain: edd-widgets-pack
 */

/**
 * Initalization
 *
 * Runs on plugins_loaded, Loads inteinternationalization and
 * requires the bundled widgets.
 *
 * @return   void
 * @access   private
 * @since    1.0
 */
if ( ! function_exists( 'edd_widgets_pack_init' ) ) {
	function edd_widgets_pack_init() {

		if ( ! function_exists( 'edd_price' ) ) {
			return;
		}

		// load internationalization.
		load_plugin_textdomain( 'edd-widgets-pack', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Handle licensing.
		if ( class_exists( 'EDD_License' ) ) {
			$license = new EDD_License( __FILE__, 'Widgets Pack', '1.2.6', 'Sandhills Development, LLC', null, null, 1514 );
		}

		// register widgets.
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-top-sellers.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-most-commented.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-most-recent.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-related-downloads.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-featured-download.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-random-download.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-archives.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-downloads-calendar.php';
	}
}
add_action( 'plugins_loaded', 'edd_widgets_pack_init' );
