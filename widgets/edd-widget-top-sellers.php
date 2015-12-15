<?php
/**
 * EDD Top Sellers Widget
 *
 * @package      EDD Widgets Pack
 * @author       Matt Varone <contact@mattvarone.com>
 * @copyright    Copyright (c) 2012, Matt Varone
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
*/


/**
 * EDD Top Sellers Widget Class
 *
 * A list of EDD top selling downloads.
 *
 * @access   private
 * @return   void
 * @since    1.0
*/
if ( ! class_exists( 'EDD_Top_Sellers' ) ) {
    class EDD_Top_Sellers extends WP_Widget {

        /**
         * Construct
         *
         * @return   void
         * @since    1.0
        */

        function __construct()
        {
            // hook updates
            add_action( 'save_post', array( &$this, 'delete_cache' ) );
            add_action( 'delete_post', array( &$this, 'delete_cache' ) );
            add_action( 'update_option_start_of_week', array( &$this, 'delete_cache' ) );
            add_action( 'update_option_gmt_offset', array( &$this, 'delete_cache' ) );

            // contruct widget
            parent::__construct( false, __( 'EDD Top Sellers', 'edd-widgets-pack' ), array( 'description' => sprintf( __( 'A list of EDD top selling %s.', 'edd-widgets-pack' ), edd_get_label_plural( true ) ) ) );
        }


        /**
         * Widget
         *
         * @return   void
         * @since    1.0
        */

        function widget( $args, $instance )
        {

            // get the title and apply filters
            $title = apply_filters( 'widget_title', $instance['title'] ? $instance['title'] : '' );

            // set the limit
            $limit = isset( $instance['limit'] ) ? $instance['limit'] : 4;

            // get show price boolean
            $show_price = isset( $instance['show_price'] ) && $instance['show_price'] == 1 ? 1 : 0;

            // get the thumbnail boolean
            $thumbnail = isset( $instance['thumbnail'] ) && $instance['thumbnail'] == 1 ? 1 : 0;

            // set the thumbnail size
            $thumbnail_size = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : 80;

            // start collecting the output
            $out = "";

            // check if there is a title
            if ( $title ) {
                // add the title to the ouput
                $out .= $args['before_title'] . $title . $args['after_title'];
            }

            // set the params
            $params = array(
                'post_type'      => 'download',
                'posts_per_page' => absint( $limit ),
                'post_status'    => 'publish',
                'orderby' => 'meta_value_num',
                'meta_query'     => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_edd_download_sales',
                        'compare' => '>',
                        'value'   => 0,
                        'order'   => 'DESC',
                    ),
                ),
             );

            if ( $instance['exclude_free'] ) {
                $params['meta_query'][] = array(
                    'key'     => 'edd_price',
                    'value'   => 0.00,
                    'type'    => 'numeric',
                    'compare' => '!='
                );
            }

            // get top sellers
            $top_sellers = get_posts( $params );

            // check top sellers
            if ( is_null( $top_sellers ) || empty( $top_sellers ) ) {
                // return if there are no top sellers
                return;

            } else {

                // start the list output
                $out .= "<ul class=\"widget-top-sellers\">\n";

                // set the link structure
                $link = "<a href=\"%s\" title=\"%s\" class=\"%s\" rel=\"bookmark\">%s</a>\n";

                // filter the thumbnail size
                $thumbnail_size = apply_filters( 'edd_widgets_top_sellers_thumbnail_size', array( $thumbnail_size, $thumbnail_size ) );

                // loop trough all downloads
                foreach ( $top_sellers as $download ) {

                    setup_postdata( $download );

                    // get the title
                    $title = apply_filters( 'the_title', $download->post_title, $download->ID );
					$title_attr = apply_filters( 'the_title_attribute', $download->post_title, $download->ID );

                    // get the post thumbnail
                    if ( $thumbnail === 1 && function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $download->ID ) ) {
                        $post_thumbnail = get_the_post_thumbnail( $download->ID, $thumbnail_size, array( 'title' => esc_attr( $title_attr ) ) ) . "\n";
                        $out .= "<li class=\"widget-download-with-thumbnail\">\n";
                        $out .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-thumb', $post_thumbnail );
                    } else {
                        $out .= "<li>\n";
                    }

                    // append the download's title
                    $out .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-title', $title );

                    // get the price
                    if ( $show_price === 1 ) {
                        if ( edd_has_variable_prices( $download->ID ) ) {
                            $price = edd_price_range( $download->ID );
                        } else {
                            $price = edd_currency_filter( edd_get_download_price( $download->ID ) );
                        }
                        $out .= sprintf( "<span class=\"widget-download-price\">%s</span>\n", $price );
                    }

                    // finish this element
                    $out .= "</li>\n";
                }
                wp_reset_postdata();
                // finish the list
                $out .= "</ul>\n";
            }

            // set the widget's containers
            echo $args['before_widget'] . $out . $args['after_widget'];
        }


        /**
         * Update
         *
         * @return   array
         * @since    1.0
        */

        function update( $new_instance, $old_instance )
        {
            $instance = $old_instance;

            // sanitize title
            $instance['title'] = strip_tags( $new_instance['title'] );

            // sanitize limit
            $instance['limit'] = strip_tags( $new_instance['limit'] );
            $instance['limit'] = ( ( bool ) preg_match( '/^\-?[0-9]+$/', $instance['limit'] ) ) && $instance['limit'] > -2 ? $instance['limit'] : 4;

            // sanitize show price
            $instance['show_price'] = isset( $new_instance['show_price'] ) ? (bool) $new_instance['show_price'] : 0;

            // sanitize exclude free
            $instance['exclude_free'] = isset( $new_instance['exclude_free'] ) ? (bool) $new_instance['exclude_free'] : 0;

            // sanitize thumbnail
            $instance['thumbnail'] = isset( $new_instance['thumbnail'] ) ? (bool) $new_instance['thumbnail'] : 0;

            // sanitize thumbnail size
            $instance['thumbnail_size'] = strip_tags( $new_instance['thumbnail_size'] );
            $instance['thumbnail_size'] = ( ( bool ) preg_match( '/^[0-9]+$/', $instance['thumbnail_size'] ) ) ? $instance['thumbnail_size'] : 80;

            // delete cache
            $this->delete_cache();

            return $instance;
        }


        /**
         * Delete Cache
         *
         * @return   void
         * @since    1.0
        */

        function delete_cache()
        {
            delete_transient( 'edd_widgets_top_sellers' );
        }


        /**
         * Form
         *
         * @return   void
         * @since    1.0
        */

        function form( $instance )
        {
            $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
            $limit = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : 4;
            $show_price = isset( $instance['show_price'] ) ? esc_attr( $instance['show_price'] ) : 0;
            $exclude_free = isset( $instance['exclude_free'] ) ? esc_attr( $instance['exclude_free'] ) : 0;
            $thumbnail = isset( $instance['thumbnail'] ) ? esc_attr( $instance['thumbnail'] ) : 0;
            $thumbnail_size = isset( $instance['thumbnail_size'] ) ? esc_attr( $instance['thumbnail_size'] ) : 80;

            ?>
                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'edd-widgets-pack' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php printf( __('Number of %s to show:', 'edd-widgets-pack' ), edd_get_label_plural( true ) ); ?></label>
                    <input class="small" size="3" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $limit; ?>"/>
                </p>
                <p>
                    <input id="<?php echo $this->get_field_id( 'show_price' ); ?>" name="<?php echo $this->get_field_name( 'show_price' ); ?>" type="checkbox" value="1" <?php checked( '1', $show_price ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'show_price' ); ?>"><?php _e( 'Display price?', 'edd-widgets-pack' ); ?></label>
                </p>
                <p>
                    <input id="<?php echo $this->get_field_id( 'exclude_free' ); ?>" name="<?php echo $this->get_field_name( 'exclude_free' ); ?>" type="checkbox" value="1" <?php checked( '1', $exclude_free ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'exclude_free' ); ?>"><?php printf( __( 'Exclude free %s?', 'edd-widgets-pack' ), strtolower( edd_get_label_plural() ) ); ?></label>
                </p>
                <p>
                    <input id="<?php echo $this->get_field_id( 'thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail' ); ?>" type="checkbox" value="1" <?php checked( '1', $thumbnail ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'thumbnail' ); ?>"><?php _e( 'Display thumbnails?', 'edd-widgets-pack' ); ?></label>
                </p>
                <p>
                    <label for="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>"><?php _e( 'Size of the thumbnails, e.g. <em>80</em> = 80x80px', 'edd-widgets-pack' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'thumbnail_size' ); ?>" name="<?php echo $this->get_field_name( 'thumbnail_size' ); ?>" type="text" value="<?php echo $thumbnail_size; ?>" />
                </p>
            <?php
        }

    }
}


/**
 * Register Top Sellers Widget
 *
 * @access   private
 * @return   void
 * @since    1.0
*/

if ( ! function_exists( 'edd_widgets_pack_register_top_sellers_widget' ) ) {
    function edd_widgets_pack_register_top_sellers_widget() {
        register_widget( 'EDD_Top_Sellers' );
    }
}
add_action( 'widgets_init', 'edd_widgets_pack_register_top_sellers_widget', 10 );