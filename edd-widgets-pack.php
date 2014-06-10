<?php
/*
Plugin Name: Easy Digital Downloads - Widgets Pack
Plugin URL: http://easydigitaldownloads.com/extension/widgets-pack
Description: A pack of widgets for Easy Digital Downloads.
Version: 1.0.9
Author: Matt Varone
Author URI: http://www.mattvarone.com
Contributors: sksmatt
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
        
        if( ! function_exists( 'edd_price' ) )
            return;

        // load internationalization
        load_plugin_textdomain( 'edd-widgets-pack', false, dirname( plugin_basename( __FILE__ ) ) . '/lan/' );
        
        // register widgets
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-top-sellers.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-most-commented.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-most-recent.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-related-downloads.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-featured-download.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-random-download.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-archives.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'widgets/edd-widget-downloads-calendar.php' );
    }
}
add_action( 'plugins_loaded', 'edd_widgets_pack_init' );