<?php
/**
 * EDD Downloads Calendar Widget
 *
 * @package      EDD Widgets Pack
 * @author       Sandhills Development, LLC
 * @copyright    Copyright (c) 2021, Sandhills Development, LLC
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since        1.0
 */

/**
 * EDD Downloads Calendar Widget Class
 *
 * A calendar of your site’s EDD downloads.
 *
 * @access   private
 * @return   void
 * @since    1.0
 */

if ( ! class_exists( 'EDD_Downloads_Calendar' ) ) {
	class EDD_Downloads_Calendar extends WP_Widget {

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
			parent::__construct( false, sprintf( __( 'EDD %s Calendar', 'edd-widgets-pack' ), edd_get_label_singular() ), array( 'description' => sprintf( __( 'A calendar of your site\'s EDD %s.', 'edd-widgets-pack' ), edd_get_label_plural( true ) ) ) );
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

			// set the limit.
			$limit = isset( $instance['limit'] ) ? $instance['limit'] : 4;

			// start collecting the output.
			$out = '';

			// check if there is a title.
			if ( $title ) {
				// add the title to the ouput.
				$out .= $args['before_title'] . $title . $args['after_title'];
			}

			$out .= $this->get_calendar();

			// set the widget's containers.
			$out = $args['before_widget'] . $out . $args['after_widget'];

			echo $out;

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
			// check if widget is active.
			if ( ! is_active_widget( false, false, $this->id_base, true ) ) {
				return;
			}

			// delete this widget's transient.
			delete_transient( 'edd_widgets_downloads_calendar' );
		}


		/**
		 * Form
		 *
		 * @return   void
		 * @since    1.0
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';

			?>
				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'edd-widgets-pack' ); ?></label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>"/>
				</p>
			<?php
		}


		/**
		 * Get Calendar
		 *
		 * @return   void
		 * @since    1.0
		 */
		protected function get_calendar( $initial = true, $echo = true ) {
			global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

			$cache = array();
			$key   = md5( $m . $monthnum . $year );

			if ( ! $posts ) {
				$gotsome = $wpdb->get_var( "SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'download' AND post_status = 'publish' LIMIT 1" );
				if ( ! $gotsome ) {
					$this->delete_cache();
					return;
				}
			}

			if ( isset( $_GET['w'] ) ) {
				$w = '' . intval( $_GET['w'] );
			}

			// week_begins = 0 stands for Sunday.
			$week_begins = intval( get_option( 'start_of_week' ) );

			// Let's figure out when we are.
			if ( ! empty( $monthnum ) && ! empty( $year ) ) {
				$thismonth = '' . zeroise( intval( $monthnum ), 2 );
				$thisyear = '' . intval( $year );
			} elseif ( ! empty( $w ) ) {
				// We need to get the month from MySQL.
				$thisyear = '' . intval( substr( $m, 0, 4 ) );
				$d        = ( ( $w - 1 ) * 7 ) + 6;
				// it seems MySQL's weeks disagree with PHP's.
				$thismonth = $wpdb->get_var( "SELECT DATE_FORMAT( ( DATE_ADD( '{$thisyear}0101', INTERVAL $d DAY ) ), '%m' )" );
			} elseif ( ! empty( $m ) ) {
				$thisyear = '' . intval( substr( $m, 0, 4 ) );
				if ( strlen( $m ) < 6 ) {
					$thismonth = '01';
				} else {
					$thismonth = '' . zeroise( intval( substr( $m, 4, 2 ) ), 2 );
				}
			} else {
				$thisyear = gmdate( 'Y', current_time( 'timestamp' ) );
				$thismonth = gmdate( 'm', current_time( 'timestamp' ) );
			}

			$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
			$last_day  = date( 't', $unixmonth );

			// Get the next and previous month and year with at least one download.
			$previous = $wpdb->get_row(
				"SELECT MONTH( post_date ) AS month, YEAR( post_date ) AS year
				FROM $wpdb->posts
				WHERE post_date < '$thisyear-$thismonth-01'
				AND post_type = 'download' AND post_status = 'publish'
					ORDER BY post_date DESC
					LIMIT 1"
			);
			$next = $wpdb->get_row(
				"SELECT MONTH( post_date ) AS month, YEAR( post_date ) AS year
				FROM $wpdb->posts
				WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
				AND post_type = 'download' AND post_status = 'publish'
					ORDER BY post_date ASC
					LIMIT 1"
			);

			/* translators: Calendar caption: 1: month name, 2: 4-digit year */
			$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
			$calendar_output = '<table id="wp-calendar">
			<caption>' . sprintf( $calendar_caption, $wp_locale->get_month( $thismonth ), date( 'Y', $unixmonth ) ) . '</caption>
			<thead>
			<tr>';

			$myweek = array();

			for ( $wdcount=0; $wdcount <= 6; $wdcount++ ) {
				$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
			}

			foreach ( $myweek as $wd ) {
				$day_name = ( true == $initial ) ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
				$wd = esc_attr( $wd );
				$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
			}

			$calendar_output .= '
			</tr>
			</thead>

			<tfoot>
			<tr>';

			if ( $previous ) {
				$calendar_output .= "\n\t\t" . '<td colspan="3" id="prev"><a href="' . get_month_link( $previous->year, $previous->month ) . '?post_type=download" title="' . esc_attr( sprintf( __( 'View %3$s for %1$s %2$s', 'edd-widgets-pack' ), $wp_locale->get_month( $previous->month ), date( 'Y', mktime( 0, 0, 0, $previous->month, 1, $previous->year ) ), edd_get_label_plural( true ) ) ) . '">&laquo; ' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) . '</a></td>';
			} else {
				$calendar_output .= "\n\t\t" . '<td colspan="3" id="prev" class="pad">&nbsp;</td>';
			}

			$calendar_output .= "\n\t\t" . '<td class="pad">&nbsp;</td>';

			if ( $next ) {
				$calendar_output .= "\n\t\t" . '<td colspan="3" id="next"><a href="' . get_month_link( $next->year, $next->month ) . '?post_type=download" title="' . esc_attr( sprintf( __( 'View %3$s for %1$s %2$s', 'edd-widgets-pack' ), $wp_locale->get_month( $next->month ), date( 'Y', mktime( 0, 0, 0, $next->month, 1, $next->year ) ), edd_get_label_plural( true ) ) ) . '">' . $wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) . ' &raquo;</a></td>';
			} else {
				$calendar_output .= "\n\t\t" . '<td colspan="3" id="next" class="pad">&nbsp;</td>';
			}

			$calendar_output .= '
			</tr>
			</tfoot>

			<tbody>
			<tr>';

			// Get days with posts.
			$dayswithposts = $wpdb->get_results(
				"SELECT DISTINCT DAYOFMONTH( post_date )
				FROM $wpdb->posts WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
				AND post_type = 'download' AND post_status = 'publish'
				AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'",
				ARRAY_N
			);
			if ( $dayswithposts ) {
				foreach ( (array) $dayswithposts as $daywith ) {
					$daywithpost[] = $daywith[0];
				}
			} else {
				$daywithpost = array();
			}

			if ( strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) !== false || stripos( $_SERVER['HTTP_USER_AGENT'], 'camino' ) !== false || stripos( $_SERVER['HTTP_USER_AGENT'], 'safari' ) !== false ) {
				$ak_title_separator = "\n";
			} else {
				$ak_title_separator = ', ';
			}

			$ak_titles_for_day = array();
			$ak_post_titles    = $wpdb->get_results(
				"SELECT ID, post_title, DAYOFMONTH( post_date ) as dom "
				. "FROM $wpdb->posts "
				. "WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00' "
				. "AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' "
				. "AND post_type = 'download' AND post_status = 'publish'"
			);
			if ( $ak_post_titles ) {
				foreach ( (array) $ak_post_titles as $ak_post_title ) {

					$post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );

					if ( empty( $ak_titles_for_day[ 'day_' . $ak_post_title->dom ] ) ) {
						$ak_titles_for_day[ 'day_' . $ak_post_title->dom ] = '';
					}
					if ( empty( $ak_titles_for_day[ "$ak_post_title->dom" ] ) ) {
						// first one.
						$ak_titles_for_day[ "$ak_post_title->dom" ] = $post_title;
					} else {
						$ak_titles_for_day[ "$ak_post_title->dom" ] .= $ak_title_separator . $post_title;
					}
				}
			}

			// See how much we should pad in the beginning.
			$pad                  = calendar_week_mod( date( 'w', $unixmonth ) - $week_begins );
			if ( 0 !== $pad ) {
				$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
			}
			$daysinmonth = intval( date( 't', $unixmonth ) );
			for ( $day = 1; $day <= $daysinmonth; ++$day ) {
				if ( isset( $newrow ) && $newrow ) {
					$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
				}
				$newrow = false;

				if ( gmdate( 'j', current_time( 'timestamp' ) ) === $day && gmdate( 'm', current_time( 'timestamp' ) ) === $thismonth && gmdate( 'Y', current_time( 'timestamp' ) ) === $thisyear ) {
					$calendar_output .= '<td id="today">';
				} else {
					$calendar_output .= '<td>';
				}
				if ( in_array( $day, $daywithpost ) ) {
					// any posts today?
						$calendar_output .= '<a href="' . get_day_link( $thisyear, $thismonth, $day ) . '?post_type=download" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
				} else {
					$calendar_output .= $day;
				}
				$calendar_output .= '</td>';

				if ( 6 === calendar_week_mod( date( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
					$newrow = true;
				}
			}

			$pad                  = 7 - calendar_week_mod( date( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
			if ( 0 !== $pad && 7 !== $pad ) {
				$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
			}
			$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

			return apply_filters( 'edd_widgets_pack_get_downloads_calendar', $calendar_output );

		}
	}

}


/**
 * Register Downloads Calendar Widget
 *
 * @access   private
 * @return   void
 * @since    1.0
 */

if ( ! function_exists( 'edd_widgets_pack_register_downloads_calendar_widget' ) ) {
	function edd_widgets_pack_register_downloads_calendar_widget() {

		// get the EDD archives constant.
		$archives = true;
		if ( defined( 'EDD_DISABLE_ARCHIVE' ) && EDD_DISABLE_ARCHIVE === true ) {
			$archives = false;
		}

		// no archives? then nothing to do here.
		if ( false === $archives ) {
			return;
		}

		register_widget( 'EDD_Downloads_Calendar' );
	}
}
add_action( 'widgets_init', 'edd_widgets_pack_register_downloads_calendar_widget', 10 );
