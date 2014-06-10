<?php
/** 
 * EDD Archives Widget
 *
 * @package      EDD Widgets Pack
 * @author       Matt Varone <contact@mattvarone.com>
 * @copyright    Copyright (c) 2012, Matt Varone
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
*/


/**
 * EDD Archives Widget Class
 *
 * EDD Downloads archive.
 *  
 * @access   private
 * @return   void
 * @since    1.0
*/

if ( ! class_exists( 'EDD_Archives' ) ) {
    class EDD_Archives extends WP_Widget {

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

            // enable date based archives for downloads
            add_action( 'pre_get_posts', array( &$this, 'pre_get_posts_filter' ) );
            
            // contruct widget
            parent::__construct( false, __( 'EDD Archives', 'edd-widgets-pack' ), array( 'description' => sprintf( __( 'A monthly archive of your site\'s %s.', 'edd-widgets-pack' ), edd_get_label_plural( true ) ) ) );
        }


        /**
         * Widget
         *
         * @return   void
         * @since    1.0
        */

        function widget( $args, $instance )
        {

           if ( false == $cache = get_transient( 'edd_widgets_archives' ) ) {

                // get the title and apply filters
                $title = apply_filters( 'widget_title', $instance['title'] ? $instance['title'] : '' );
                
                // get show count boolean
                $show_count = isset( $instance['show_count'] ) && $instance['show_count'] === 1 ? 1 : 0;

                // start collecting the output
                $out = "";

                // check if there is a title
                if ( $title ) {
                    // add the title to the ouput
                    $out .= $args['before_title'] . $title . $args['after_title'];
                }

                $out .= "<ul>\n";

                // add download post type to archives
                add_filter( 'getarchives_where' , array( &$this, 'getarchives_where_filter' ) , 10 , 2 );
                
                // output the archives
                $out .= wp_get_archives( array( 'echo' => 0, 'show_post_count' => $show_count ) );
                
                // remove filter
                remove_filter( 'getarchives_where' , array( &$this, 'getarchives_where_filter' ), 10, 2 );

                // finish the list
                $out .= "</ul>\n";

                // set the widget's containers
                $cache = $args['before_widget'] . $out . $args['after_widget'];

                // store the result on a temporal transient
                set_transient( 'edd_widgets_archives', $cache );

            }

            echo $cache;

        }


        /**
         * Get Archives Where Filter
         *
         * @return   string
         * @since    1.0
        */
        
        function getarchives_where_filter( $where , $r )
        {
            return str_replace( "post_type = 'post'" , "post_type = 'download'" , $where );
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
            
            // sanitize show price
            $instance['show_count'] = strip_tags( $new_instance['show_count'] );
            $instance['show_count'] = $instance['show_count'] === '1' ? 1 : 0;

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
            delete_transient( 'edd_widgets_archives' );
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
            $show_count = isset( $instance['show_count'] ) ? esc_attr( $instance['show_count'] ) : 0;
            ?>
                <p>
                    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'edd-widgets-pack' ); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
                </p>
                <p>
                    <input id="<?php echo $this->get_field_id( 'show_count' ); ?>" name="<?php echo $this->get_field_name( 'show_count' ); ?>" type="checkbox" value="1" <?php checked( '1', $show_count ); ?>/>
                    <label for="<?php echo $this->get_field_id( 'show_count' ); ?>"><?php printf( __('Show %s counts?', 'edd-widgets-pack' ), edd_get_label_plural( true ) ); ?></label> 
                </p>
            <?php
        }


        /**
         * Rewrite Rules Array
         *
         * @author   Copyright 2012 Jennifer M. Dodd <jmdodd@gmail.com>
         * @license  Released under the GPLv2 ( or later ).
         * @return   void
         * @since    1.0
        */

        function pre_get_posts_filter( $query ) {

            if ( ! is_active_widget( false, false, $this->id_base, true ) )
            return;

            if ( ! is_preview() && ! is_admin() && ! is_singular() && ! is_404() && ! is_home() ) {
                if ( ! $query->is_feed ) {
                    $my_post_type = get_query_var( 'post_type' );
                    if ( empty( $my_post_type ) ) {
                        $args = array( 
                            'public' => true , 
                            '_builtin' => false
                         );
                        $output = 'names';
                        $operator = 'and';

                        $post_types = array_merge( array( 'download' ), array( 'post' ) );
                        $query->set( 'post_type', $post_types );
                    }
                }
            } 
        }

    }
}


/**
 * Register Archives Widget
 *  
 * @access   private
 * @return   void
 * @since    1.0
*/

if ( ! function_exists( 'edd_widgets_pack_register_archives_widget' ) ) {
    function edd_widgets_pack_register_archives_widget() {
        register_widget( 'EDD_Archives' );
    }
}
add_action( 'widgets_init', 'edd_widgets_pack_register_archives_widget', 10 );