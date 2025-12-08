<?php
/**
 * Helper class for Tripzzy reviews and ratings.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Template;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Helper class for Tripzzy reviews and ratings.
 *
 * @since 1.0.0
 */
class Reviews {

	const RATINGS_METAKEY = 'ratings';

	/**
	 * Initialize reviews class.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'comments_template', array( __CLASS__, 'override_comments_template_path' ) );
		add_filter( 'comment_form_defaults', array( __CLASS__, 'filter_comment_form_defaults' ) );
		add_action( 'comment_post', array( __CLASS__, 'save_ratings' ) );
		add_filter( 'comment_text', array( __CLASS__, 'append_ratings' ), 50, 2 );
	}

	/**
	 * Override file path for the `comments_template` function.
	 *
	 * @param string $path Full path to comments template file.
	 * @return string New full path to comments template file.
	 */
	public static function override_comments_template_path( $path ) {

		if ( 'tripzzy' === get_post_type() ) {
			$path = Template::get_template_file( 'review-single-tripzzy.php' );
		}

		return $path;
	}

	/**
	 * Add star ratings to Tripzzy review form.
	 *
	 * @param string $comment_field Comment or review field.
	 * @return string
	 */
	protected static function add_ratings_to_form( $comment_field ) {
		if ( ! $comment_field ) {
			return $comment_field;
		}

		$ratings = '<div id="tripzzy-ratings"></div>';

		return $ratings . $comment_field;
	}

	/**
	 * Filter or modify comments default arguments.
	 *
	 * @param array $defaults The default comment form arguments.
	 * @return array
	 */
	public static function filter_comment_form_defaults( $defaults ) {

		if ( 'tripzzy' !== get_post_type() ) {
			return $defaults;
		}

		$defaults['comment_field'] = self::add_ratings_to_form( $defaults['comment_field'] );
		$defaults['title_reply']   = esc_html__( 'Leave a review', 'tripzzy' );
		$defaults['label_submit']  = esc_html__( 'Submit Review', 'tripzzy' );
		$defaults['id_submit']     = 'tripzzy-submit-review';
		$defaults['class_submit']  = 'tripzzy-submit-review tz-btn tz-btn-solid';

		return $defaults;
	}

	/**
	 * Save ratings on comment save.
	 *
	 * @param int $comment_id Comment ID.
	 * @since 1.0.0
	 * @since 1.2.2 Fixed displaying decimal review even added whole number review. like 5 star review shows 2.5.
	 * @return int|bool
	 */
	public static function save_ratings( $comment_id ) {

		if ( ! $comment_id ) {
			return;
		}
		if ( ! Nonce::verify() ) {
			return;
		}

		if ( isset( $_POST['tripzzy_ratings'], $_POST['comment_post_ID'] ) && 'tripzzy' === get_post_type( absint( $_POST['comment_post_ID'] ) ) ) {  // @codingStandardsIgnoreLine WPCS: input var ok, CSRF ok.
			$ratings = absint( $_POST['tripzzy_ratings'] ); // @codingStandardsIgnoreLine
			if ( ! $ratings || $ratings > 10 || $ratings < 0 ) {
				return;
			}
			$settings              = Settings::get();
			$allow_decimal_ratings = $settings['allow_decimal_ratings'] ?? true;
			$rating                = $allow_decimal_ratings ? $ratings / 2 : $ratings;
			return MetaHelpers::update_comment_meta( $comment_id, self::RATINGS_METAKEY, $rating );
		}
	}

	/**
	 * Returns or echos out html for average ratings.
	 *
	 * @param int|string $ratings Ratings.
	 * @param bool       $has_echo Whether to echo out or return html.
	 * @param int        $comments_number Number of comments.
	 * @since 1.0.0
	 * @since 1.0.9 Only display review excluding number of reviews text.
	 * @since 1.1.2 Added $comment_number as a param.
	 * @return string|void
	 */
	public static function ratings_average_html( $ratings, $has_echo = true, $comments_number = 0 ) {
		if ( ! $ratings ) {
			$ratings = 0;
		}

		$percent = $ratings ? absint( ( $ratings / 5 ) * 100 ) : 0;
		if ( ! $comments_number ) {
			$comments_number = (int) self::get_comments_number( get_the_ID() );
		}

		$comment_class = ! $comments_number ? 'no-reviews' : '';

		/* translators: %s is the rating. */
		$title = sprintf( __( 'Rated %s out of 5', 'tripzzy' ), esc_attr( $ratings ) );
		/* Translators: %d number of reviews */
		$title_placeholder = sprintf( _n( ' (%d Review)', ' (%d Reviews)', esc_html( $comments_number ), 'tripzzy' ), esc_html( $comments_number ) );
		$title            .= $title_placeholder;
		$title             = $ratings > 0 ? $title : __( 'No reviews.', 'tripzzy' );

		ob_start();
		?>
		<div class="tripzzy-average-rating <?php echo esc_attr( $comment_class ); ?>" title="<?php echo esc_attr( $title ); ?>">
			<div class="tripzzy-average-rating-value">
				<span style="width:<?php echo esc_attr( $percent ); ?>%">
					<?php
					printf(
						/* translators: %1$s is the rating given by customer and %2$s is total rating. */
						esc_html__( 'Rated %1$s out of %2$s', 'tripzzy' ),
						'<strong itemprop="ratingValue" class="rating">' . esc_html( $ratings ) . '</strong>',
						'<span itemprop="bestRating">5</span>'
					);
					?>
				</span>
			</div>
		</div>
		<?php
		$content = ob_get_clean();

		if ( ! $has_echo ) {
			return $content;
		}

		echo wp_kses_post( $content );
	}

	/**
	 * Append ratings to comment text.
	 *
	 * @param string      $comment_text Comment text.
	 * @param \WP_Comment $comment WP_Comment object.
	 * @return string
	 */
	public static function append_ratings( $comment_text, $comment ) {

		$comment_id = $comment->comment_ID;

		$ratings = self::get_ratings( $comment_id );

		if ( ! $ratings ) {
			return $comment_text;
		}

		return self::ratings_average_html( $ratings, false ) . $comment_text;
	}

	/**
	 * Returns total number of comment excluding replies.
	 * only top level comments are counted.
	 *
	 * @param int $trip_id Comment ID.
	 * @return int|float
	 */
	public static function get_comments_number( $trip_id ) {

		if ( ! $trip_id ) {
			return 0;
		}
		$comments = get_comments(
			array(
				'post_id' => $trip_id,
				'parent'  => 0, // Only retrieve top-level comments.
			)
		);
		return count( $comments );
	}

	/**
	 * Returns reviews ratings by comment id.
	 *
	 * @param int $comment_id Comment ID.
	 * @return int|float
	 */
	public static function get_ratings( $comment_id ) {

		if ( ! $comment_id ) {
			return 0;
		}

		$ratings = MetaHelpers::get_comment_meta( $comment_id, self::RATINGS_METAKEY );
		return $ratings ? $ratings : 0;
	}

	/**
	 * Tripzzy compatible wrapper function for `get_comments`.
	 *
	 * @param array $args Review args.
	 * @return array
	 */
	public static function get_reviews( $args = array() ) {

		$key     = md5( wp_json_encode( $args ) );
		$reviews = wp_cache_get( $key );

		if ( $reviews ) {
			return $reviews;
		}

		$reviews  = array();
		$comments = get_comments( $args );

		if ( is_array( $comments ) && ! empty( $comments ) ) {
			foreach ( $comments as $comment ) {

				$comment_id = $comment->comment_ID;

				$ratings = self::get_ratings( $comment_id );

				$reviews[] = array(
					'id'           => $comment_id,
					'link'         => get_comment_link( $comment_id ),
					'review'       => $comment->comment_content,
					'replies'      => $comment->get_children( array( 'format' => 'flat' ) ),
					'ratings'      => $ratings,
					'ratings_html' => self::ratings_average_html( $ratings, false ),
					'timestamp'    => strtotime( $comment->comment_date_gmt ),
					'comment_post' => array(
						'id'    => $comment->comment_post_ID,
						'title' => get_the_title( $comment->comment_post_ID ),
						'link'  => get_the_permalink( $comment->comment_post_ID ),
					),
				);
			}
		}

		wp_cache_set( $key, $reviews );

		return $reviews;
	}

	/**
	 * Returns array of reviews with ratings.
	 *
	 * @param int|\WP_Post|null $trip    Trip ID or Trip post type object.
	 * @param int|null          $user_id Pass user id to include reviews for a specific user ID.
	 * @return array
	 */
	public static function get_trip_reviews( $trip = null, $user_id = null ) {
		$post = get_post( $trip );

		if ( empty( $post->ID ) ) {
			return array();
		}

		if ( 'tripzzy' !== $post->post_type ) {
			return array();
		}

		$args = array(
			'status'       => 'approve',
			'post_id'      => $post->ID,
			'hierarchical' => 'threaded',
		);

		if ( ! is_null( $user_id ) ) {
			$args['user_id'] = $user_id;
		}

		return self::get_reviews( $args );
	}

	/**
	 * Returns array of reviews with ratings by user id.
	 *
	 * @param int|null $user_id User Id.
	 * @return array
	 */
	public static function get_user_reviews( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$args = array(
			'post_type'    => 'tripzzy',
			'status'       => 'approve',
			'user_id'      => $user_id,
			'hierarchical' => 'threaded',
		);

		return self::get_reviews( $args );
	}

	/**
	 * Returns trip ratings average.
	 *
	 * @param int|\WP_Post|null $trip Trip ID or Trip post type object.
	 * @return float|int
	 */
	public static function get_trip_ratings_average( $trip = null ) {
		$reviews = self::get_trip_reviews( $trip );

		if ( ! $reviews ) {
			return 0;
		}

		$sum = 0;

		if ( is_array( $reviews ) && ! empty( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$sum += $review['ratings'];
			}
		}

		if ( ! $sum ) {
			return 0;
		}

		return round( $sum / count( $reviews ), 1 );
	}

	/**
	 * Render template.
	 *
	 * @return void|null
	 */
	public static function render() {
		if ( comments_open() || ! post_password_required() ) {
			return comments_template();
		}
	}
}
