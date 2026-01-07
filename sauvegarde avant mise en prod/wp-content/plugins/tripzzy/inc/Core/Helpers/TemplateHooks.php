<?php
/**
 * Tripzzy TemplateHooks.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Modules;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TemplateHooks' ) ) {
	/**
	 * Tripzzy TemplateHooks Class.
	 *
	 * @since 1.0.0
	 */
	class TemplateHooks {
		use SingletonTrait;
		use DataTrait;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'tripzzy_archive_before_content', array( 'Tripzzy\Core\Helpers\TripFilter', 'render_trip_filters' ) );
			add_action( 'tripzzy_archive_after_listing', array( 'Tripzzy\Core\Helpers\Pagination', 'init' ) );
			add_action( 'tripzzy_archive_after_listing', array( __CLASS__, 'load_more' ) );

			// Single page.
			add_action( 'tripzzy_single_page_content', array( __CLASS__, 'render_trip_details' ) );

			// Sticky tab. @since 1.0.2.
			add_action( 'tripzzy_before_main_content', array( 'Tripzzy\Core\Helpers\TripStickyTab', 'render' ) );

			// Body Class For Tripzzy pages. @sicne 1.0.9.
			add_action( 'body_class', array( __CLASS__, 'body_class' ) );

			// Tripzzy query var support to get variable using get_query_var(). @since 1.1.1.
			add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
			// Dashboard Page login redirect. @since 1.1.1 @deprecated 1.1.9.
			// add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
			// Add Markup just above login form input. @since 1.1.1.
			add_filter( 'login_form_top', array( __CLASS__, 'login_form_top' ) );

			/**
			 * Modify Query args as per sticky trips.
			 *
			 * @since 1.1.4
			 */
			add_action( 'pre_get_posts', array( __CLASS__, 'pagination_query' ) );

			/**
			 * Display min people info.
			 *
			 * @since 1.2.1
			 */
			add_action( 'tripzzy_date_availability_after_packages', array( __CLASS__, 'add_min_people' ) );
		}

		/**
		 * Render All single trip page sections.
		 *
		 * @since 1.0.2
		 *
		 * @return void
		 */
		public static function render_trip_details() {
			$trip_tabs    = TripStickyTab::get( get_the_ID() );
			$default_tabs = TripStickyTab::get_default_sticky_tab_items();
			foreach ( $trip_tabs as $trip_tab ) {
				$enabled = (bool) $trip_tab['enabled'] ?? false;
				if ( ! $enabled ) {
					continue;
				}
				$render_class = $trip_tab['render_class'] ?? '';
				if ( ! $render_class ) {
					// check in default.
					$key = array_search( $trip_tab['link'] ?? '', array_column( $default_tabs, 'link' ) ); // @phpcs:ignore
					if ( false !== $key ) {
						$render_class = $default_tabs[ $key ]['render_class'] ?? '';
					}
				}
				if ( $render_class ) { // need to check method exists.
					call_user_func( array( $render_class, 'render' ) );
				}
			}
		}

		/**
		 * Wrapper element to add load more button in archive page.
		 *
		 * @return void
		 */
		public static function load_more() {
			$strings   = Strings::get();
			$labels    = $strings['labels'] ?? array();
			$load_more = $labels['load_more'] ?? '';
			?>
			<div id="tripzzy-load-more-trips" class="tripzzy-load-more-trips tripzzy-load-more-link" style="display:none"><a href="#" id="tripzzy-load-more" class="tripzzy-load-more" ><?php echo esc_html( $load_more ); ?></a></div><!-- Added in one line to resolve wpautop issue in block template -->
			<?php
		}

		/**
		 * List of body class for the pages.
		 *
		 * @param array $body_class Body Class list.
		 *
		 * @since 1.0.9
		 * @since 1.1.8 Added body class naming with dash(-) keeping the name with underscore(_) for legacy.
		 * @return array
		 */
		public static function body_class( $body_class ) {

			// need tripzzy single page class and common tripzzy page class logic.
			if ( Page::is( 'search-result' ) ) {
				$body_class[] = self::get_prefix( 'page' );
				$body_class[] = self::get_prefix( 'search-result-page' ); // For Legacy. Need to remove in future version.
				$body_class[] = self::get_prefix( 'search-result-page', true );
			} elseif ( Page::is( 'trips' ) ) {
				$body_class[] = self::get_prefix( 'page' );
				$body_class[] = self::get_prefix( 'trips-page' );
				$body_class[] = self::get_prefix( 'trips-page', true );
			} elseif ( Page::is( 'trip' ) ) {
				$body_class[] = self::get_prefix( 'page', true );
				$body_class[] = self::get_prefix( 'trip-page', true );
			} elseif ( Page::is( 'dashboard' ) ) {
				$body_class[] = self::get_prefix( 'page', true );
				$body_class[] = self::get_prefix( 'dashboard-page' );
				$body_class[] = self::get_prefix( 'dashboard-page', true );
			} elseif ( Page::is( 'checkout' ) ) {
				$body_class[] = self::get_prefix( 'page', true );
				$body_class[] = self::get_prefix( 'checkout-page' );
				$body_class[] = self::get_prefix( 'checkout-page', true );
			} elseif ( Page::is( 'thankyou' ) ) {
				$body_class[] = self::get_prefix( 'page', true );
				$body_class[] = self::get_prefix( 'thankyou-page' );
				$body_class[] = self::get_prefix( 'thankyou-page', true );
			}
			return $body_class;
		}

		/**
		 * Additional query vars spport for get_query_var().
		 *
		 * @param array $qvars list of query vars.
		 * @since 1.3.0 Added tripzzy_trip_duration in query vars.
		 * @since 1.1.4 Added tripzzy_price in query vars.
		 * @since 1.1.3 Added booking_id in query vars.
		 * @since 1.1.1
		 * @return array
		 */
		public static function query_vars( $qvars ) {
			$qvars[] = 'tz_reason'; // Login failed reason in tripzzy dashboard login.
			$qvars[] = 'booking_id'; // Thank you page.
			$qvars[] = 'tripzzy_key'; // Nonce in thankyou and other page.
			$qvars[] = 'tripzzy_price'; // Price filter and search form.
			$qvars[] = 'tripzzy_price_changed'; // Price filter and search form.
			$qvars[] = 'tripzzy_trip_duration'; // Duration filter and search form.
			$qvars[] = 'tripzzy_trip_duration_changed'; // Duration filter and search form.
			return $qvars;
		}

		/**
		 * Conditional redirect of login action.
		 *
		 * @param string $redirect_to Url to redirect.
		 * @param string $referrer From where request is made.
		 * @param object $user Either user object or error object.
		 * @since 1.1.1
		 * @deprecated 1.1.9 Merged this method with User::login_redirect().
		 * @return string
		 */
		public static function login_redirect( $redirect_to, $referrer, $user ) {
			$dashboard_url = Page::get_url( 'dashboard' );
			if ( str_contains( $referrer, $dashboard_url ) ) {
				if ( is_wp_error( $user ) ) {

					$error_types = array_keys( $user->errors );
					$error_type  = 'both_empty';
					if ( is_array( $error_types ) && ! empty( $error_types ) ) {
						$empty_username = in_array( 'empty_username', $error_types, true );
						$empty_password = in_array( 'empty_password', $error_types, true );
						if ( $empty_username && $empty_password ) {
							$error_type = 'both_empty';
						} else {
							$error_type = $error_types[0];
						}
					}
					wp_safe_redirect( $dashboard_url . '?login=failed&tz_reason=' . $error_type );
					exit;
				}
				wp_safe_redirect( $dashboard_url );
				exit;
			}
			return $redirect_to;
		}

		/**
		 * Supports markups and text to palce it just above the login form.
		 *
		 * @param string $top Markup String.
		 * @since 1.1.1
		 * @return string
		 */
		public static function login_form_top( $top ) {

			$key = get_query_var( 'tz_reason' );
			if ( ! empty( $key ) ) {
				$error = new \WP_Error();
				switch ( $key ) {
					case 'both_empty':
						$error->add( 'empty_username', __( '<strong>Error:</strong> The username field is empty.', 'tripzzy' ) );
						$error->add( 'empty_password', __( '<strong>Error:</strong> The password field is empty.', 'tripzzy' ) );
						break;
					case 'empty_username':
						$error->add( 'empty_username', __( '<strong>Error:</strong> The username field is empty.', 'tripzzy' ) );
						break;
					case 'empty_password':
						$error->add( 'empty_password', __( '<strong>Error:</strong> The password field is empty.', 'tripzzy' ) );
						break;
					case 'invalid_username':
					case 'incorrect_password':
						$error->add( 'invalid_username', __( '<strong>Error:</strong> Username or password is incorrect.', 'tripzzy' ) );
						break;
				}
				if ( $error && $error->errors ) {
					$error_list = array();
					$messages   = '';
					foreach ( $error->errors as $error_message ) {
						$error_list[] = $error_message[0];
					}
					if ( ! empty( $error_list ) ) {
						$errors = '<div id="login_error" class="notice notice-error">';

						if ( count( $error_list ) > 1 ) {
							$errors .= '<ul class="login-error-list">';

							foreach ( $error_list as $item ) {
								$errors .= '<li>' . $item . '</li>';
							}

							$errors .= '</ul>';
						} else {
							$errors .= '<p>' . $error_list[0] . '</p>';
						}
						$errors .= '</div>';
						return $errors;
					}
				}
			}
			return $top;
		}

		/**
		 * Modify Query args as per sticky trips.
		 * Add sticky_post option in trips query.
		 *
		 * @param array $query Query args for pre_get_posts.
		 *
		 * @since 1.1.4
		 * @return array;
		 */
		public static function pagination_query( $query ) {
			if ( ! Page::is( 'trips' ) || is_admin() || ! $query->is_main_query() || get_query_var( 'post_type' ) !== 'tripzzy' ) {
				return;
			}
			$sticky = get_option( 'sticky_posts' );
			$query->set( 'post__not_in', $sticky );
		}

		/**
		 * Add Min People in trip single page.
		 *
		 * @param object $trip Trip object.
		 *
		 * @since 1.2.1
		 * @return void
		 */
		public static function add_min_people( $trip ) {
			$min_people = $trip->get_meta( 'min_people' );

			if ( ! $min_people ) {
				return;
			}
			$enable_advanced = (bool) $trip->get_meta( 'enable_advanced_min_people' );
			if ( $enable_advanced && Modules::is_active( 'tripzzy_utilities_module' ) ) {
				return;
			}
			/* translators: 1: Min People  */
			$min_people_html = sprintf( __( '( Min: %d )', 'tripzzy' ), esc_html( $min_people ) );
			/* translators: 1: Min People  */
			$min_people_tooltip = sprintf( _n( 'Please select minimum %d person', 'Please select minimum %d people.', $min_people, 'tripzzy' ), number_format_i18n( $min_people ) );

			?>
			<div class="tripzzy__category-min-people-container">
				<div class="tripzzy__category-min-people">
					<?php echo esc_html( $min_people_html ); ?>
				</div>
				<div class="tripzzy-tooltip-container">
					<div class="tripzzy-tooltip-icon">
						<i class="fa fa-info-circle" aria-hidden="true"></i>
					</div>
					<div class="tripzzy-tooltip"><?php echo esc_html( $min_people_tooltip ); ?></div>
				</div>
			</div>
			<?php
		}
	}
}
