<?php
/**
 * EDD Random Download Widget
 *
 * @package      EDD Widgets Pack
 * @author       Sandhills Development, LLC
 * @copyright    Copyright (c) 2021, Sandhills Development, LLC
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

/**
 * EDD Random Download Widget Class
 *
 * A random EDD download.
 *
 * @access   private
 * @return   void
 * @since    1.0
 */
if ( ! class_exists( 'EDD_Random_Download' ) ) {
	class EDD_Random_Download extends WP_Widget {

		/**
		 * Construct
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function __construct() {
			parent::__construct( false, sprintf( __( 'EDD Random %s', 'edd-widgets-pack' ), edd_get_label_singular() ), array( 'description' => sprintf( __( 'A random EDD %s.', 'edd-widgets-pack' ), edd_get_label_singular( true ) ) ) );
		}

		/**
		 * Widget
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function widget( $args, $instance ) {

			// get the title and apply filters.
			$title = apply_filters( 'widget_title', $instance['title'] ? $instance['title'] : '' );

			// get show price boolean.
			$show_price = isset( $instance['show_price'] ) && 1 === $instance['show_price'] ? 1 : 0;

			// get the thumbnail boolean.
			$thumbnail = isset( $instance['thumbnail'] ) && 1 === $instance['thumbnail'] ? 1 : 0;

			// set the thumbnail size.
			$thumbnail_size = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : 80;

			// start collecting the output.
			$out = '';

			// check if there is a title.
			if ( $title ) {
				// add the title to the ouput.
				$out .= $args['before_title'] . $title . $args['after_title'];
			}

			// set the params.
			$params = array(
				'post_type'      => 'download',
				'posts_per_page' =>  1,
				'post_status'    => 'publish',
				'orderby'        => 'rand',
			);

			// get the random download.
			$random_download = get_posts( $params );

			// check download.
			if ( is_null( $random_download ) || empty( $random_download ) ) {
			// return if there is no download.
				return;

			} else {
				// start the list output.
				$out .= "<ul class=\"widget-random-download\">\n";

				// set the link structure.
				$link = "<a href=\"%s\" title=\"%s\" class=\"%s\" rel=\"bookmark\">%s</a>\n";

				// filter the thumbnail size.
				$thumbnail_size = apply_filters( 'edd_widgets_random_download_thumbnail_size', array( $thumbnail_size, $thumbnail_size ) );

				// loop through the random download.
				foreach ( $random_download as $download ) {
					// get the title.
					$title = apply_filters( 'the_title', $download->post_title, $download->ID );
					$title_attr = apply_filters( 'the_title_attribute', $download->post_title, $download->ID );

					// get the post thumbnail.
					if ( 1 === $thumbnail && has_post_thumbnail( $download->ID ) ) {
						$post_thumbnail = get_the_post_thumbnail( $download->ID, $thumbnail_size, array( 'title' => esc_attr( $title_attr ) ) ) . "\n";
						$out           .= "<li class=\"widget-download-with-thumbnail\">\n";
						$out           .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-thumb', $post_thumbnail );
					} else {
						$out .= "<li>\n";
					}

					// append the downloads title.
					$out .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-title', $title );

					// get the price.
					if ( 1 === $show_price ) {
						if ( edd_has_variable_prices( $download->ID ) ) {
							$price = edd_price_range( $download->ID );
						} else {
							$price = edd_currency_filter( edd_get_download_price( $download->ID ) );
						}
						$out .= sprintf( "<span class=\"widget-download-price\">%s</span>\n", $price );
					}

					// finish this element.
					$out .= "</li>\n";
				}

				// finish the list.
				$out .= "</ul>\n";

			}

			// set the widget's containers.
			echo $args['before_widget'] . $out . $args['after_widget'];

		}

		/**
		 * Update
		 *
		 * @return   array
		 * @since    1.0
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $old_instance;

			// sanitize title.
			$instance['title'] = strip_tags( $new_instance['title'] );

			// sanitize show price.
			$instance['show_price'] = ! empty( $new_instance['show_price'] ) && '1' === $new_instance['show_price'] ? 1 : 0;

			// sanitize thumbnail.
			$instance['thumbnail'] = ! empty( $new_instance['thumbnail'] ) && '1' === $new_instance['thumbnail'] ? 1 : 0;

			// sanitize thumbnail size.
			$instance['thumbnail_size'] = ( (bool) preg_match( '/^[0-9]+$/', $new_instance['thumbnail_size'] ) );

			return $instance;
		}


		/**
		 * Form
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function form( $instance ) {
			$title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			$show_price     = isset( $instance['show_price'] ) ? esc_attr( $instance['show_price'] ) : 0;
			$thumbnail      = isset( $instance['thumbnail'] ) ? esc_attr( $instance['thumbnail'] ) : 0;
			$thumbnail_size = isset( $instance['thumbnail_size'] ) ? esc_attr( $instance['thumbnail_size'] ) : 80;

			?>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'edd-widgets-pack' ); ?></label>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_html( $title ); ?>"/>
				</p>
				<p>
					<input id="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $show_price ); ?>/>
					<label for="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"><?php esc_html_e( 'Display price?', 'edd-widgets-pack' ); ?></label>
				</p>
				<p>
					<input id="<?php echo esc_attr( $this->get_field_id( 'thumbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $thumbnail ); ?>/>
					<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail' ) ); ?>"><?php esc_html_e( 'Display thumbnails?', 'edd-widgets-pack' ); ?></label>
				</p>
				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_size' ) ); ?>"><?php echo wp_kses( 'Size of the thumbnails, e.g. <em>80</em> = 80x80px', array( 'em' => array() ) ); ?></label>
					<input type="number" min="0" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_size' ) ); ?>" value="<?php echo esc_html( $thumbnail_size ); ?>" />
				</p>
			<?php
		}

	}
}


/**
 * Register Random Download Widget
 *
 * @access   private
 * @return   void
 * @since    1.0
 */

if ( ! function_exists( 'edd_widgets_pack_register_random_download_widget' ) ) {
	function edd_widgets_pack_register_random_download_widget() {
		register_widget( 'EDD_Random_Download' );
	}
}
add_action( 'widgets_init', 'edd_widgets_pack_register_random_download_widget', 10 );
