<?php
/**
 * EDD Most Recent Widget
 *
 * @package      EDD Widgets Pack
 * @author       Sandhills Development, LLC
 * @copyright    Copyright (c) 2021, Sandhills Development, LLC
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

/**
 * EDD Most Recent Widget Class
 *
 * A list of EDD most recent downloads.
 *
 * @access   private
 * @return   void
 * @since    1.0
 */

if ( ! class_exists( 'EDD_Most_Recent' ) ) {
	class EDD_Most_Recent extends WP_Widget {

		/**
		 * Construct
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function __construct() {
			// hook updates.
			add_action( 'save_post', array( &$this, 'delete_cache' ) );
			add_action( 'delete_post', array( &$this, 'delete_cache' ) );
			add_action( 'update_option_start_of_week', array( &$this, 'delete_cache' ) );
			add_action( 'update_option_gmt_offset', array( &$this, 'delete_cache' ) );

			// construct widget.
			parent::__construct( false, __( 'EDD Most Recent', 'edd-widgets-pack' ), array( 'description' => sprintf( __( 'A list of EDD most recent %s.', 'edd-widgets-pack' ), edd_get_label_plural( true ) ) ) );
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

			// get the offset.
			$offset = $instance['offset'] ? $instance['offset'] : 0;

			// set the limit.
			$limit = isset( $instance['limit'] ) ? $instance['limit'] : 4;

			// get show price boolean.
			$show_price = isset( $instance['show_price'] ) && 1 === $instance['show_price'] ? 1 : 0;

			// get the thumbnail boolean.
			$thumbnail = isset( $instance['thumbnail'] ) && 1 === $instance['thumbnail'] ? 1 : 0;

			// set the thumbnail size.
			$thumbnail_size = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : 80;

			// get the category.
			$category = isset( $instance['category'] ) ? $instance['category'] : 'edd-all-categories';

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
				'posts_per_page' => absint( $limit ),
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'offset'         => absint( $offset ),
			);

			// adjust params if we're only pulling one category.
			if ( isset( $category ) && 'edd-all-categories' !== $category ) {
				$params['tax_query'][] = array(
					'taxonomy' => 'download_category',
					'field'    => 'slug',
					'terms'    => $category,
				);
			}

			$most_recent = get_posts( $params );

			// check the downloads.
			if ( is_null( $most_recent ) || empty( $most_recent ) ) {
				// return if there are no downloads.
				return;
			} else {
				// start the list output.
				$out .= "<ul class=\"widget-most-recent\">\n";

				// set the link structure.
				$link = "<a href=\"%s\" title=\"%s\" class=\"%s\" rel=\"bookmark\">%s</a>\n";

				// filter the thumbnail size.
				$thumbnail_size = apply_filters( 'edd_widgets_most_recent_thumbnail_size', array( $thumbnail_size, $thumbnail_size ) );

				// loop trough all downloads.
				foreach ( $most_recent as $download ) {
					// get the title.
					$title      = apply_filters( 'the_title', $download->post_title, $download->ID );
					$title_attr = apply_filters( 'the_title_attribute', $download->post_title, $download->ID );

					// get the post thumbnail.
					if ( 1 === $thumbnail && has_post_thumbnail( $download->ID ) ) {
						$post_thumbnail = get_the_post_thumbnail( $download->ID, $thumbnail_size, array( 'title' => esc_attr( $title_attr ) ) ) . "\n";
						$out           .= "<li class=\"widget-download-with-thumbnail\">\n";
						$out           .= sprintf( $link, get_permalink( $download->ID ), esc_attr( $title_attr ), 'widget-download-thumb', $post_thumbnail );
					} else {
						$out .= "<li>\n";
					}

					// append the download's title.
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

			// sanitize limit.
			$instance['limit'] = isset( $new_instance['limit'] ) ? (int) $new_instance['limit'] : 4;

			// sanitize offset.
			$instance['offset'] = ! empty( $new_instance['offset'] ) ? (int) $new_instance['offset'] : 0;

			// sanitize show price.
			$instance['show_price'] = ! empty( $new_instance['show_price'] ) && '1' === $new_instance['show_price'] ? 1 : 0;

			// sanitize thumbnail.
			$instance['thumbnail'] = ! empty( $new_instance['thumbnail'] ) && '1' === $new_instance['thumbnail'] ? 1 : 0;

			// sanitize thumbnail size.
			$instance['thumbnail_size'] = ! empty( $new_instance['thumbnail_size'] ) ? absint( $new_instance['thumbnail_size'] ) : 80;

			// sanitize category.
			$instance['category'] = $new_instance['category'] ? strip_tags( $new_instance['category'] ) : 'edd-all-categories';

			// delete cache.
			$this->delete_cache();

			return $instance;
		}


		/**
		 * Delete Cache
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function delete_cache() {
			delete_transient( 'edd_widgets_most_recent' );
		}


		/**
		 * Form
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function form( $instance ) {
			$title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
			$offset         = isset( $instance['offset'] ) ? esc_attr( $instance['offset'] ) : 0;
			$limit          = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : 4;
			$show_price     = isset( $instance['show_price'] ) ? esc_attr( $instance['show_price'] ) : 0;
			$thumbnail      = isset( $instance['thumbnail'] ) ? esc_attr( $instance['thumbnail'] ) : 0;
			$thumbnail_size = isset( $instance['thumbnail_size'] ) ? esc_attr( $instance['thumbnail_size'] ) : 80;
			$category       = isset( $instance['category'] ) ? esc_attr( $instance['category'] ) : 'all';

			$category_list = get_terms( 'download_category' );

			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'edd-widgets-pack' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_html( $title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'offset' ) ); ?>">
				<?php
				printf(
					/* translators: The plural post type label */
					esc_attr__( 'Number of %s to skip:', 'edd-widgets-pack' ),
					esc_attr( edd_get_label_plural( true ) )
				);
				?>
				</label>
				<input type="number" min="0" class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'offset' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'offset' ) ); ?>" value="<?php echo esc_html( $offset ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
				<?php
				printf(
					/* translators: the plural post type label */
					esc_attr__( 'Number of %s to show:', 'edd-widgets-pack' ),
					esc_attr( edd_get_label_plural( true ) )
				);
				?>
				</label>
				<input type="number" min="-1" class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" value="<?php echo esc_html( $limit ); ?>"/>
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
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>">
				<?php
				printf(
					/* translators: the plural post type label */
					esc_html__( 'Display %s from category:', 'edd-widgets-pack' ),
					esc_attr( edd_get_label_plural( true ) )
				);
				?>
				</label>
				<select class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>">
				<option value="edd-all-categories"><?php esc_html_e( 'All', 'edd-widgets-pack' ); ?></option>
				<?php
				if ( ! empty( $category_list ) ) {
					foreach ( $category_list as $key => $category_details ) {
						echo '<option value="' . esc_attr( $category_details->slug ) . '"' . selected( $category_details->slug, $category ) . '>' . esc_attr( $category_details->name ) . '</option>';
					}
				}
				?>
				</select>
			</p>
			<?php
		}
	}
}


/**
 * Register Most Recent Widget
 *
 * @access   private
 * @return   void
 * @since    1.0
 */

if ( ! function_exists( 'edd_widgets_pack_register_most_recent_widget' ) ) {
	function edd_widgets_pack_register_most_recent_widget() {
		register_widget( 'EDD_Most_Recent' );
	}
}
add_action( 'widgets_init', 'edd_widgets_pack_register_most_recent_widget', 10 );
