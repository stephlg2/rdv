<?php
/**
 * Trip Faqs.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Bases\TaxonomyBase;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripFaqs' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripFaqs {
		use TripTrait;

		/**
		 * Trip Object.
		 *
		 * @var $trip.
		 */
		public static $trip;

		/**
		 * All Post Metas.
		 *
		 * @var $all_meta.
		 */
		public static $all_meta;

		/**
		 * Only trip Metas.
		 *
		 * @var $trip_meta.
		 */
		public static $trip_meta;


		/**
		 * Trip Init.
		 *
		 * @param mixed $trip either trip id or trip object.
		 */
		public function __construct( $trip = null ) {
			if ( is_object( $trip ) ) {
				self::$trip = $trip;
			} elseif ( is_numeric( $trip ) ) {
				self::$trip = get_post( $trip );
			} else {
				self::$trip = get_post( get_the_ID() );
			}
			self::$all_meta  = get_post_meta( self::$trip->ID );
			self::$trip_meta = MetaHelpers::get_post_meta( self::$trip->ID, 'trip' );
		}

		/**
		 * Get Trip Infos data as per trip id for the frontend.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get( $trip_id ) {
			return Trip::get_faqs( $trip_id );
		}

		/**
		 * Format FAQ answer with proper line breaks and lists.
		 *
		 * @param string $answer The FAQ answer text.
		 * @since 1.0.0
		 * @return string Formatted answer HTML.
		 */
		private static function format_faq_answer( $answer ) {
			if ( empty( $answer ) ) {
				return '';
			}

			// Check if answer already contains HTML tags (from WYSIWYG editor)
			// Strip tags and compare - if different, HTML is present
			$stripped = strip_tags( $answer );
			$has_html = $stripped !== $answer;
			
			if ( $has_html ) {
				// If HTML is present, return it as-is without modification
				// The HTML from TinyMCE is already properly formatted with <p>, <br>, <strong>, etc.
				// Just ensure it's properly escaped for output
				return $answer;
			}

			// Split by line breaks to process line by line
			$lines = preg_split( '/\r\n|\r|\n/', $answer );
			$formatted_parts = array();
			$current_list = array();
			$in_list = false;
			$in_paragraph = false;

			foreach ( $lines as $index => $line ) {
				$trimmed_line = trim( $line );
				$is_empty = empty( $trimmed_line );
				
				// Check if line starts with a dash (bullet point) - supports -, –, —
				if ( ! $is_empty && preg_match( '/^[-–—]\s*(.+)$/u', $trimmed_line, $matches ) ) {
					// Close any open paragraph before starting list
					if ( $in_paragraph ) {
						$formatted_parts[] = '</p>';
						$in_paragraph = false;
					}
					
					if ( ! $in_list ) {
						$in_list = true;
						$formatted_parts[] = '<ul>';
					}
					$current_list[] = '<li>' . esc_html( trim( $matches[1] ) ) . '</li>';
				} else {
					// Not a list item
					if ( $in_list && ! empty( $current_list ) ) {
						// Close the list
						$formatted_parts = array_merge( $formatted_parts, $current_list );
						$formatted_parts[] = '</ul>';
						$current_list = array();
						$in_list = false;
					}
					
					if ( $is_empty ) {
						// Empty line - close paragraph if open
						if ( $in_paragraph ) {
							$formatted_parts[] = '</p>';
							$in_paragraph = false;
						}
						// Add a <br> for spacing if not at the start/end and there's content before
						if ( ! empty( $formatted_parts ) && $index < count( $lines ) - 1 ) {
							$last_part = end( $formatted_parts );
							if ( ! preg_match( '/<\/p>|<\/ul>|<\/li>$/u', $last_part ) ) {
								$formatted_parts[] = '<br>';
							}
						}
					} else {
						// Non-empty line
						if ( ! $in_paragraph ) {
							$formatted_parts[] = '<p>';
							$in_paragraph = true;
						} else {
							// Add <br> before continuing in same paragraph (for line breaks)
							$formatted_parts[] = '<br>';
						}
						$formatted_parts[] = esc_html( $trimmed_line );
					}
				}
			}

			// Close any open list
			if ( $in_list && ! empty( $current_list ) ) {
				$formatted_parts = array_merge( $formatted_parts, $current_list );
				$formatted_parts[] = '</ul>';
			}

			// Close any open paragraph
			if ( $in_paragraph ) {
				$formatted_parts[] = '</p>';
			}

			$formatted = implode( '', $formatted_parts );
			
			// Clean up empty paragraphs and normalize <br> tags
			$formatted = preg_replace( '/<p>\s*<\/p>/u', '', $formatted );
			$formatted = preg_replace( '/<p>\s+/u', '<p>', $formatted );
			$formatted = preg_replace( '/\s+<\/p>/u', '</p>', $formatted );
			// Remove <br> tags that are immediately after opening tags or before closing tags
			$formatted = preg_replace( '/<(p|ul|li)>\s*<br\s*\/?>\s*/iu', '<$1>', $formatted );
			$formatted = preg_replace( '/\s*<br\s*\/?>\s*<\/(p|ul|li)>/iu', '</$1>', $formatted );
			// Normalize <br> tags
			$formatted = preg_replace( '/<br\s*\/?>/iu', '<br>', $formatted );

			return $formatted;
		}

		/**
		 * Render Trip infos to display it in frontend.
		 *
		 * @param int     $trip_id Trip id.
		 * @param boolean $display Deiplay or return the markup.
		 * @return void
		 */
		public static function render( $trip_id = 0, $display = true ) {
			if ( ! $trip_id ) {

				global $post;
				if ( ! $post ) {
					return;
				}
				$trip_id = $post->ID;

			}

			$faqs           = self::get( $trip_id ); // Saved In post meta.
			$section_titles = Trip::get_section_titles( $trip_id );
			$section_title  = $section_titles['faqs'] ?? __( 'FAQs', 'tripzzy' );
			ob_start();
			if ( is_array( $faqs ) && count( $faqs ) > 0 ) : ?>
				<div class="tripzzy-section"  id="tripzzy-faqs-section">
					<h3 class="tripzzy-section-title"><span><?php echo esc_html( $section_title ); ?></span> <a href="#" class="tripzzy-accordion-expand-close" data-expand="<?php esc_html_e( 'Expand all', 'tripzzy' ); ?>" data-close="<?php esc_html_e( 'Close all', 'tripzzy' ); ?>" ><?php esc_html_e( 'Expand all', 'tripzzy' ); ?></a></h3>
					<div class="tripzzy-section-inner tripzzy-faqs-wrapper"  id="tripzzy-faqs" >
						<ul class="tripzzy-accordion tripzzy-faqs" >
						<?php foreach ( $faqs as $faq ) : ?>
							<li>
								<span class="accordion-title faq-question"><?php echo esc_html( $faq['question'] ); ?></span>
								<div class="accordion-content faq-answer">
									<?php 
									$formatted_answer = self::format_faq_answer( $faq['answer'] );
									// Use wp_kses_post which allows all standard post HTML tags (strong, em, a, br, p, ul, li, etc.)
									echo wp_kses_post( do_shortcode( $formatted_answer ) ); 
									?>
								</div>
							</li>
						<?php endforeach; ?>
						</ul>
					</div>
				</div>
				<?php
			endif;

			$content = ob_get_contents();
			ob_end_clean();
			if ( ! $display ) {
				return $content;
			}
			echo wp_kses_post( $content );
		}
	}
}
