<?php
/**
 * Add/Update media image.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;

if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Image' ) ) {
	/**
	 * Image Input.
	 *
	 * @since 1.0.0
	 */
	class Image {
		/**
		 * Field array.
		 *
		 * @var $field
		 * @since 1.0.0
		 */
		protected $field;
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.0.0
		 */
		protected static $field_type = 'image';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.0.0
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( __CLASS__, 'register_attribute' ) );

			// Enqueue Media.
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_media' ) );
			// Upload custom script.
			add_action( 'admin_footer', array( __CLASS__, 'upload_media' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.0.0
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Image', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Image',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Load media script.
		 *
		 * @return void
		 */
		public static function load_media() {
			wp_enqueue_media();
		}

		/**
		 * Tripzzy upload media custom script.
		 *
		 * @return void
		 */
		public static function upload_media() {
			?>
			<script>
				jQuery(document).ready( function($) {
					// Open Media popup and select media.
					$('body').on('click', 'input.tripzzy-image-upload-button', function(e) {
						e.preventDefault();
						let th = $(this);
						let valueField     = th.siblings( '.tripzzy-image-upload-input' );
						let previewField   = th.siblings( '.tripzzy-image-thumbnail-preview' );
						let wrapperElement = th.closest( '.tripzzy-image-upload-wrapper' );
						let imgSrcInput    = th.siblings( '.tripzzy-image-thumbnail-url' );
						wp.media.editor.open(th);
						wp.media.editor.send.attachment = function(props, attachment){
							let imgSrc = attachment.sizes.tripzzy_thumbnail.url
							previewField.html(`<img class="tripzzy-image-upload-preview-image" src="${imgSrc}" />`);
							valueField.val(attachment.id);
							imgSrcInput.val(imgSrc);
							wrapperElement.addClass( 'has-image' );
						}
						return;
					});
					// Remove Media.
					$('body').on('click','.tripzzy-image-remove-button',function(e){
						e.preventDefault();

						let confirmRemove = confirm( 'Are you about to remove this image?' );

						if (confirmRemove) {
							let valueField   = $(this).siblings( '.tripzzy-image-upload-input' );
							let previewField = $(this).siblings( '.tripzzy-image-thumbnail-preview' );
							let buttonField  = $(this).siblings( '.tripzzy-image-upload-button' );
							let ButtonText   = buttonField.data( 'textAddImage' );
							let wrapperElement = $(this).closest( '.tripzzy-image-upload-wrapper' );
							let imgSrcInput    = $(this).siblings( '.tripzzy-image-thumbnail-url' );
							previewField.html('');
							valueField.val('');
							imgSrcInput.val('');
							buttonField.html( ButtonText );
							wrapperElement.removeClass( 'has-image' );
						}
					});
					// Clear image field on add new tag
					$(document).ajaxComplete(function(event, xhr, settings) {
						var queryStringArr = settings.data.split('&');
						if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
							var xml = xhr.responseXML;
							$response = $(xml).find('term_id').text();
							if($response!=""){
								// Clear the thumb image
								$('.tripzzy-image-thumbnail-preview').html('');
								$('.tripzzy-image-upload-input').val('');
								$('.tripzzy-image-upload-wrapper').removeClass('has-image');
							}
						}
					});
				});
			</script>
			<?php
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @param bool  $display Display field flag. whether return or display.
		 */
		public static function render( $field = array(), $display = true ) {
			$enabled       = isset( $field['enabled'] ) ? $field['enabled'] : true; // by default ebabled.
			$force_enabled = isset( $field['force_enabled'] ) ? $field['force_enabled'] : false; // by default disabled.
			if ( $enabled || $force_enabled ) {
				$button_label = __( 'Add Image', 'tripzzy' );
				$value        = isset( $field['value'] ) ? $field['value'] : '';

				$before_field = '';
				if ( isset( $field['before_field'] ) ) {
					$before_field_class = isset( $field['before_field_class'] ) ? $field['before_field_class'] : '';
					$before_field       = sprintf( '<span class="tripzzy-before-field%s">%s</span>', esc_attr( $before_field_class ), wp_kses_post( $field['before_field'] ) );
				}
				$wrapper_class = $value ? 'has-image' : '';

				$image_src = '';
				if ( $value ) {
					$image_data = wp_get_attachment_image_src( $value, 'tripzzy_thumbnail' );
					if ( $image_data ) {
						$image_src = $image_data[0];
					}
				}

				// Add Image button markup.
				$img_markup = '';
				if ( $image_src ) {
					$img_markup = sprintf( '<img src="%1$s" />', esc_url( $image_src ) );
				}

				$output = sprintf(
					'%1$s
					<span class="tripzzy-image-upload-wrapper %2$s">
						<span class="tripzzy-image-thumbnail-preview">%3$s</span>
						<input type="hidden" name="%4$s" class="tripzzy-image-upload-input" value="%5$s" />
						<input type="button" class="button button-secondary tripzzy-image-upload-button" data-text-add-image="%6$s" value="%7$s" />
						<input type="hidden" class="tripzzy-image-thumbnail-url" name="%8$s_url" value="%9$s" />
						<input type="button" class="button button-secondary tripzzy-image-remove-button" value="x" />
					</span>
				',
					$before_field,
					esc_attr( $wrapper_class ),
					$img_markup,
					esc_attr( $field['name'] ),
					esc_attr( $value ),
					$button_label,
					$button_label,
					esc_attr( $field['name'] ),
					esc_url( $image_src )
				);
				if ( ! $display ) {
					return $output;
				}

				$allowed_html = EscapeHelper::get_allowed_html();
				echo wp_kses( $output, $allowed_html );
			}
		}
	}
}
