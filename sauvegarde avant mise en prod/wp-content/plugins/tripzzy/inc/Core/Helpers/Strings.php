<?php
/**
 * Strings.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Strings' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Strings {
		/**
		 * Get All Strings Value.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Added hook.
		 */
		public static function get() {

			$strings = array(
				'labels'       => self::labels(),
				'descriptions' => self::descriptions(),
				'messages'     => self::messages(),
				'queries'      => self::queries(),
				'tooltips'     => self::tooltips(),
			);
			return apply_filters( 'tripzzy_filter_strings', $strings, Page::is_admin_pages() );
		}

		/**
		 * Label strings.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Added more strings.
		 */
		public static function labels() {
			$strings = array(
				'address_2'          => __( 'Address 2', 'tripzzy' ),
				'all'                => __( 'All', 'tripzzy' ),
				'billing_details'    => __( 'Billing Details', 'tripzzy' ),
				'book_now'           => __( 'Book Now', 'tripzzy' ),
				'booked_on'          => __( 'Booked on', 'tripzzy' ),
				'booking'            => __( 'Booking', 'tripzzy' ),
				/* translators: %d: Booking ID */
				'booking_details'    => __( 'Booking #%d Details', 'tripzzy' ),
				'booking_date'       => __( 'Booking Date', 'tripzzy' ),
				'bookings'           => __( 'Bookings', 'tripzzy' ),
				'change_password'    => __( 'Change Password', 'tripzzy' ),
				'check_availability' => __( 'Check availability', 'tripzzy' ),
				'checkout'           => __( 'Checkout', 'tripzzy' ),
				'country_region'     => __( 'Country/Region', 'tripzzy' ),
				'clear'              => __( 'Clear', 'tripzzy' ),
				'confirm_password'   => __( 'Confirm Password', 'tripzzy' ),
				'current_password'   => __( 'Current Password', 'tripzzy' ),
				'dep'                => __( 'Dep', 'tripzzy' ),
				'due'                => __( 'Due', 'tripzzy' ),
				'duration'           => __( 'Duration', 'tripzzy' ),
				'edit_profile'       => __( 'Edit Profile', 'tripzzy' ),
				'email_address'      => __( 'Email Address', 'tripzzy' ),
				'enquiry'            => __( 'Enquiry', 'tripzzy' ),
				'featured'           => __( 'Featured', 'tripzzy' ),
				'filter_by'          => __( 'Filter by', 'tripzzy' ),
				'first_name'         => __( 'First Name', 'tripzzy' ),
				'from'               => __( 'From', 'tripzzy' ),
				'general'            => __( 'General', 'tripzzy' ),
				'last_name'          => __( 'Last Name', 'tripzzy' ),
				'load_more'          => __( 'Load more', 'tripzzy' ),
				'loading'            => __( 'Loading...', 'tripzzy' ),
				'logout'             => __( 'Log out', 'tripzzy' ),
				'make_enquiry'       => __( 'Make an Enquiry', 'tripzzy' ),
				'more_photos'        => __( 'More Photos', 'tripzzy' ),
				'more_videos'        => __( 'More Videos', 'tripzzy' ),
				'na'                 => __( 'N/A', 'tripzzy' ),
				'new_password'       => __( 'New Password', 'tripzzy' ),
				'no'                 => __( 'No', 'tripzzy' ),
				'packages'           => __( 'Packages', 'tripzzy' ),
				'paid'               => __( 'Paid', 'tripzzy' ),
				'personal_details'   => __( 'Personal Details', 'tripzzy' ),
				'phone_number'       => __( 'Phone Number', 'tripzzy' ),
				'profile'            => __( 'Profile', 'tripzzy' ),
				'qty'                => __( 'Qty', 'tripzzy' ),
				'ratings'            => __( 'Ratings', 'tripzzy' ),
				'replies'            => __( 'Replies', 'tripzzy' ),
				'reviews'            => __( 'Reviews', 'tripzzy' ),
				'save_profile'       => __( 'Save profile', 'tripzzy' ),
				'select'             => __( 'Select', 'tripzzy' ),
				'send_notification'  => __( 'Send Notification', 'tripzzy' ),
				'state_zone'         => __( 'State/Zone', 'tripzzy' ),
				'street_address'     => __( 'Street Address', 'tripzzy' ),
				'submit_enquiry'     => __( 'Submit Enquiry', 'tripzzy' ),
				'submitted_on'       => __( 'Submitted On', 'tripzzy' ),
				'sold_out'           => __( 'Sold out', 'tripzzy' ),
				'to'                 => __( 'To', 'tripzzy' ),
				'total'              => __( 'Total', 'tripzzy' ),
				'town_city'          => __( 'Town/City', 'tripzzy' ),
				'trip'               => __( 'Trip', 'tripzzy' ),
				'trip_code'          => __( 'Trip Code', 'tripzzy' ),
				'trip_types'         => __( 'Trip Types', 'tripzzy' ),
				'view'               => __( 'View', 'tripzzy' ),
				'view_details'       => __( 'View Details', 'tripzzy' ),
				'view_itinerary'     => __( 'View Itineraries', 'tripzzy' ),
				'view_more_dep'      => __( 'View more departure', 'tripzzy' ),
				'wishlist'           => __( 'Wishlist', 'tripzzy' ),
				'wishlists'          => __( 'Wishlists', 'tripzzy' ),
				'yes'                => __( 'Yes', 'tripzzy' ),
				'zip_postal_code'    => __( 'Zip/Postal Code', 'tripzzy' ),

			);
			if ( Page::is_admin_pages() ) {
				$strings['add_category']           = __( 'Add Category', 'tripzzy' );
				$strings['add_date']               = __( 'Add Date', 'tripzzy' );
				$strings['add_excludes']           = __( 'Add Excludes', 'tripzzy' );
				$strings['add_faq']                = __( 'Add FAQ', 'tripzzy' );
				$strings['add_highlight']          = __( 'Add Highlight', 'tripzzy' );
				$strings['add_images']             = __( 'Add Images In Gallery', 'tripzzy' );
				$strings['add_includes']           = __( 'Add Includes', 'tripzzy' );
				$strings['add_info']               = __( 'Add Info', 'tripzzy' );
				$strings['add_itinerary']          = __( 'Add Itinerary', 'tripzzy' );
				$strings['add_new_filter']         = __( 'Add new filter', 'tripzzy' );
				$strings['add_package']            = __( 'Add Package', 'tripzzy' );
				$strings['add_schedule']           = __( 'Add Schedule', 'tripzzy' );
				$strings['admin_templates']        = __( 'Admin Templates', 'tripzzy' );
				$strings['advanced']               = __( 'Advanced', 'tripzzy' );
				$strings['amount_display_format']  = __( 'Amount Display Format', 'tripzzy' );
				$strings['answer']                 = __( 'Answer', 'tripzzy' );
				$strings['available_variables']    = __( 'Available Variables', 'tripzzy' );
				$strings['category']               = __( 'Category', 'tripzzy' );
				$strings['check_to_reset']         = __( 'Check to reset.', 'tripzzy' );
				$strings['checkout']               = __( 'Checkout', 'tripzzy' );
				$strings['config']                 = __( 'Config', 'tripzzy' );
				$strings['coupon']                 = __( 'Coupon', 'tripzzy' );
				$strings['currency']               = __( 'Currency', 'tripzzy' );
				$strings['currency_settings']      = __( 'Currency Settings', 'tripzzy' );
				$strings['custom_filters']         = __( 'Custom Filters', 'tripzzy' );
				$strings['customer_templates']     = __( 'Customer Templates', 'tripzzy' );
				$strings['cut_off_time']           = __( 'Cut-off Time', 'tripzzy' );
				$strings['dates']                  = __( 'Dates', 'tripzzy' );
				$strings['day']                    = __( 'Day', 'tripzzy' );
				$strings['days']                   = __( 'Days', 'tripzzy' );
				$strings['decimal_separator']      = __( 'Decimal Separator', 'tripzzy' );
				$strings['default_filters']        = __( 'Default Filters', 'tripzzy' );
				$strings['difficulties']           = __( 'Difficulties', 'tripzzy' );
				$strings['disabled']               = __( 'Disabled', 'tripzzy' );
				$strings['documentation']          = __( 'Documentation', 'tripzzy' );
				$strings['duration_filter_config'] = __( 'Duration Filter Config', 'tripzzy' );
				$strings['email']                  = __( 'Email', 'tripzzy' );
				$strings['email_config']           = __( 'Email Config', 'tripzzy' );
				$strings['enable']                 = __( 'Enable', 'tripzzy' );
				$strings['enable_trip_slider']     = __( 'Enable Trip Slider', 'tripzzy' );
				$strings['enable_cut_off_time']    = __( 'Enable Cut-off Time', 'tripzzy' );
				$strings['export_tripzzy']         = __( 'Export Tripzzy', 'tripzzy' );
				$strings['faqs']                   = __( 'Faqs', 'tripzzy' );
				$strings['features']               = __( 'Features', 'tripzzy' );
				$strings['field_label']            = __( 'Field Label', 'tripzzy' );
				$strings['field_name']             = __( 'Field Name', 'tripzzy' );
				$strings['field_type']             = __( 'Field Type', 'tripzzy' );
				$strings['fields']                 = __( 'Fields', 'tripzzy' );
				$strings['filter_config']          = __( 'Filter Config', 'tripzzy' );
				$strings['filter_duration_in']     = __( 'Filter Duration In', 'tripzzy' );
				$strings['filter_label']           = __( 'Filter/Category Label', 'tripzzy' );
				$strings['filter_slug']            = __( 'Filter/Category Slug', 'tripzzy' );
				$strings['fixed_dates']            = __( 'Fixed Dates', 'tripzzy' );
				$strings['form_fields']            = __( 'Form Fields', 'tripzzy' );
				$strings['gallery']                = __( 'Gallery', 'tripzzy' );
				$strings['group']                  = __( 'Group', 'tripzzy' );
				$strings['group_price']            = __( 'Group Price', 'tripzzy' );
				$strings['group_sale_price']       = __( 'Group Sale Price', 'tripzzy' );
				$strings['hidden']                 = __( 'Hidden', 'tripzzy' );
				$strings['hierarchical']           = __( 'Hierarchical', 'tripzzy' );
				$strings['highlights']             = __( 'Highlights', 'tripzzy' );
				$strings['hours']                  = __( 'Hours', 'tripzzy' );
				$strings['import_export']          = __( 'Import/Export', 'tripzzy' );
				$strings['import_tripzzy']         = __( 'Import Tripzzy', 'tripzzy' );
				$strings['includes_excludes']      = __( 'Includes/Excludes', 'tripzzy' );
				$strings['infos']                  = __( 'Infos', 'tripzzy' );
				$strings['is_sticky']              = __( 'Is Sticky', 'tripzzy' );
				$strings['is_sticky_label']        = __( 'Stick to the top of the trips.', 'tripzzy' );
				$strings['itineraries']            = __( 'Itineraries', 'tripzzy' );
				$strings['itinerary_date']         = __( 'Itinerary Date', 'tripzzy' );
				$strings['itinerary_description']  = __( 'Itinerary Description', 'tripzzy' );
				$strings['itinerary_schedules']    = __( 'Itinerary Schedules', 'tripzzy' );
				$strings['itinerary_title']        = __( 'Itinerary Title', 'tripzzy' );
				$strings['label']                  = __( 'Label', 'tripzzy' );
				$strings['map']                    = __( 'Map', 'tripzzy' );
				$strings['maps']                   = __( 'Maps', 'tripzzy' );
				$strings['map_latitude']           = __( 'Map Latitude', 'tripzzy' );
				$strings['map_location']           = __( 'Map Location', 'tripzzy' );
				$strings['map_longitude']          = __( 'Map Longitude', 'tripzzy' );
				$strings['map_type']               = __( 'Map Type', 'tripzzy' );
				$strings['map_preview']            = __( 'Map Preview', 'tripzzy' );
				$strings['map_zoom']               = __( 'Map Zoom', 'tripzzy' );
				$strings['marker_description']     = __( 'Marker Description', 'tripzzy' );
				$strings['marker_latitude']        = __( 'Marker Latitude', 'tripzzy' );
				$strings['marker_location']        = __( 'Marker Location', 'tripzzy' );
				$strings['marker_longitude']       = __( 'Marker Longitude', 'tripzzy' );
				$strings['markers']                = __( 'Markers', 'tripzzy' );
				$strings['max']                    = __( 'Max', 'tripzzy' );
				$strings['max_people']             = __( 'Max People', 'tripzzy' );
				$strings['min']                    = __( 'Min', 'tripzzy' );
				$strings['minutes']                = __( 'Minutes', 'tripzzy' );
				$strings['misc']                   = __( 'Misc', 'tripzzy' );
				$strings['modules']                = __( 'Modules', 'tripzzy' );
				$strings['month']                  = __( 'Month', 'tripzzy' );
				$strings['month_day']              = __( 'Month Day', 'tripzzy' );
				$strings['min_people']             = __( 'Min People', 'tripzzy' );
				$strings['nights']                 = __( 'Nights', 'tripzzy' );
				$strings['number_of_decimals']     = __( 'Number of Decimals', 'tripzzy' );
				$strings['offset']                 = __( 'Offset', 'tripzzy' );
				$strings['options']                = __( 'Options', 'tripzzy' );
				$strings['or']                     = __( 'or', 'tripzzy' );
				$strings['package_and_date']       = __( 'Package & Date', 'tripzzy' );
				$strings['package_categories']     = __( 'Package Categories', 'tripzzy' );
				$strings['package_name']           = __( 'Package Name', 'tripzzy' );
				$strings['packages']               = __( 'Packages', 'tripzzy' );
				$strings['page_settings']          = __( 'Page Settings', 'tripzzy' );
				$strings['pages']                  = __( 'Pages', 'tripzzy' );
				$strings['payment']                = __( 'Payment', 'tripzzy' );
				$strings['person']                 = __( 'Person', 'tripzzy' );
				$strings['placeholder']            = __( 'Placeholder', 'tripzzy' );
				$strings['price']                  = __( 'Price', 'tripzzy' );
				$strings['price_per']              = __( 'Price Per', 'tripzzy' );
				$strings['question']               = __( 'Question', 'tripzzy' );
				$strings['recurring_dates']        = __( 'Recurring Dates', 'tripzzy' );
				$strings['recurring_dates_config'] = __( 'Recurring Dates Config', 'tripzzy' );
				$strings['recurring_until']        = __( 'Recurring Until', 'tripzzy' );
				$strings['required']               = __( 'Required', 'tripzzy' );
				$strings['regular_price']          = __( 'Regular Price', 'tripzzy' );
				$strings['reset_fields']           = __( 'Reset Fields', 'tripzzy' );
				$strings['reset_settings']         = __( 'Reset Settings', 'tripzzy' );
				$strings['review_settings']        = __( 'Review Settings', 'tripzzy' );
				$strings['sale_price']             = __( 'Sale Price', 'tripzzy' );
				$strings['schedule_placeholder']   = __( 'Your Schedule', 'tripzzy' );
				$strings['schedules']              = __( 'Schedules', 'tripzzy' );
				$strings['schema']                 = __( 'Schema', 'tripzzy' );
				$strings['search']                 = __( 'Search', 'tripzzy' );
				$strings['search_settings']        = __( 'Search Settings', 'tripzzy' );
				$strings['section_title']          = __( 'Section Title', 'tripzzy' );
				$strings['select_currency']        = __( 'Select Currency', 'tripzzy' );
				$strings['select_excludes']        = __( 'Select Excludes', 'tripzzy' );
				$strings['select_global_faqs']     = __( 'Select Gloabl FAQs', 'tripzzy' );
				$strings['select_includes']        = __( 'Select Includes', 'tripzzy' );
				$strings['select_info']            = __( 'Select Info', 'tripzzy' );
				$strings['select_date']            = __( 'Select Date', 'tripzzy' );
				$strings['settings']               = __( 'Settings', 'tripzzy' );
				$strings['show_filter_button']     = __( 'Show Filter Button', 'tripzzy' );
				$strings['show_in_filters']        = __( 'Show in Filters', 'tripzzy' );
				$strings['slider_image_size']      = __( 'Slider image size', 'tripzzy' );
				$strings['slider_settings']        = __( 'Slider Settings', 'tripzzy' );
				$strings['slug']                   = __( 'Slug', 'tripzzy' );
				$strings['smooth_scroll']          = __( 'Smooth Scroll', 'tripzzy' );
				$strings['start_date']             = __( 'Start Date', 'tripzzy' );
				$strings['status']                 = __( 'Status', 'tripzzy' );
				$strings['sub_fields']             = __( 'Sub Fields', 'tripzzy' );
				$strings['support']                = __( 'Support', 'tripzzy' );
				$strings['system_info']            = __( 'System Info', 'tripzzy' );
				$strings['system_status']          = __( 'System Status', 'tripzzy' );
				$strings['tabs']                   = __( 'Tabs', 'tripzzy' );
				$strings['themes']                 = __( 'Themes', 'tripzzy' );
				$strings['thousand_separator']     = __( 'Thousand Separator', 'tripzzy' );

				$strings['trip_date_type']         = __( 'Trip Date Type', 'tripzzy' );
				$strings['trip_difficulties']      = __( 'Trip Difficulties', 'tripzzy' );
				$strings['trip_duration']          = __( 'Trip Duration', 'tripzzy' );
				$strings['trip_excludes']          = __( 'Trip Excludes', 'tripzzy' );
				$strings['trip_faqs']              = __( 'Trip FAQs', 'tripzzy' );
				$strings['trip_filters']           = __( 'Trip Filters', 'tripzzy' );
				$strings['trip_gallery']           = __( 'Trip Gallery', 'tripzzy' );
				$strings['trip_highlights']        = __( 'Trip Highlights', 'tripzzy' );
				$strings['trip_includes']          = __( 'Trip Includes', 'tripzzy' );
				$strings['trip_infos']             = __( 'Trip Infos', 'tripzzy' );
				$strings['trip_itineraries']       = __( 'Trip Itineraries', 'tripzzy' );
				$strings['trip_packages']          = __( 'Trip Packages', 'tripzzy' );
				$strings['trip_price']             = __( 'Trip Price', 'tripzzy' );
				$strings['trip_saved']             = __( 'Trip Saved', 'tripzzy' );
				$strings['trip_settings']          = __( 'Trip Settings', 'tripzzy' );
				$strings['trip_slider']            = __( 'Trip Slider', 'tripzzy' );
				$strings['ui']                     = __( 'UI', 'tripzzy' );
				$strings['use_as_default_package'] = __( 'Use as Default Package', 'tripzzy' );
				$strings['use_as_default_price']   = __( 'Use as Default Price', 'tripzzy' );
				$strings['user']                   = __( 'User', 'tripzzy' );
				$strings['update_filter']          = __( 'Update Filter', 'tripzzy' );
			}
			return apply_filters( 'tripzzy_filter_string_labels', $strings, Page::is_admin_pages() );
		}

		/**
		 * List of descriptions.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Added more strings.
		 */
		public static function descriptions() {
			$strings = array();
			if ( Page::is_admin_pages() ) {
				$strings['add_global_faqs_trip']      = __( 'Please add Faqs in the settings.', 'tripzzy' );
				$strings['add_images']                = __( 'Drop Images here to upload in gallery.', 'tripzzy' );
				$strings['add_images2']               = __( 'Please add gallery images.', 'tripzzy' );
				$strings['add_marker']                = __( 'Please right click on the map to add new markers.', 'tripzzy' );
				$strings['cut_off_time']              = __( 'If your trip requires 1 day for preparation, please add 1 day to the cut-off time.', 'tripzzy' );
				$strings['cut_off_time2']             = __( 'If your trip requires 3 hours for preparation, please add 3 hours to the cut-off time.', 'tripzzy' );
				$strings['cut_off_time3']             = __( "The trip has only a date without time, so, let's assume a trip starts at 10:00 AM and is calculated as: ((24 hr - trip start time) + cut-off time). For example, if your trip begins at 10:00 AM and the cut-off time is 3 hours, you should set the cut-off time to 17 i.e. ((24-10)+3). Note: This calculation is only for the trips that have cut-off time in hours.", 'tripzzy' );
				$strings['decimal_separator']         = __( 'Symbol to use for decimal separator in displayed Price.', 'tripzzy' );
				$strings['enable_trip_slider']        = __( 'This will enable Trip Slider in Trip detail page.', 'tripzzy' );
				$strings['field_label']               = __( 'This is the name which will appear on the form.', 'tripzzy' );
				$strings['field_name']                = __( 'Single word, no spaces. Underscores and dashes allowed.', 'tripzzy' );
				$strings['field_type']                = __( 'Note: You can not modify the type for default field.', 'tripzzy' );
				$strings['filter_duration_in']        = __( "Display duration units like '2 Days' or '2 Hours' in the search or filter.", 'tripzzy' );
				$strings['group_price']               = __( 'Use this price for trip when price per is set as group.', 'tripzzy' );
				$strings['itinerary_date']            = __( "Date is only recommended for the fixed dated trips. You can disable it by going to `Settings > Trip Settings > Trip Itineraries`, if you don't need it.", 'tripzzy' );
				$strings['itinerary_schedules']       = __( 'You can add more schedule by clicking Add Schedule button.', 'tripzzy' );
				$strings['map_location']              = __( 'Please click to search location for the map.', 'tripzzy' );
				$strings['max_people']                = __( 'Maximum no of recommended people for the trip.', 'tripzzy' );
				$strings['min_people']                = __( 'No. of minimum required people for the trip.', 'tripzzy' );
				$strings['no_added_package_category'] = __( 'You have not added the Package category yet. Please add a category now.', 'tripzzy' );
				$strings['no_added_packages']         = __( "You don't have Packages yet. Click Add package button to add new package.", 'tripzzy' );
				$strings['no_faqs']                   = __( "You don't have FAQs yet. Please add some FAQs now.", 'tripzzy' );
				$strings['no_highlights']             = __( "You don't have trip highlights yet. Please add some highlights now.", 'tripzzy' );
				$strings['no_trip_dates']             = __( "You don't have Trip date yet. Please add trip date now.", 'tripzzy' );
				$strings['number_of_decimals']        = __( 'This sets the number of decimal points shown in displayed prices.', 'tripzzy' );
				$strings['reset_fields']              = __( 'This option will reset your form. This action can not be undone.', 'tripzzy' );
				$strings['reset_settings']            = __( 'This option will reset your entire settings. This action can not be undone.', 'tripzzy' );
				$strings['save_trip']                 = __( '* Please click publish/save button to save.', 'tripzzy' );
				$strings['section_title']             = __( 'Display as section title in frontend trip detail page.', 'tripzzy' );
				$strings['select_currency']           = __( 'Choose your accepted payment currency.', 'tripzzy' );
				$strings['select_global_faqs']        = __( 'Selected FAQs will be displayed in current trips', 'tripzzy' );
				$strings['show_filter_button']        = __( 'If this set as false, filter item as per input selected.', 'tripzzy' );
				$strings['slider_image_size']         = __( 'Slider image size to display slider in trip detail page.', 'tripzzy' );
				$strings['thousand_separator']        = __( 'Symbol to use for thousands separator in displayed Price.', 'tripzzy' );
				$strings['trip_date_type_fixed']      = __( 'Please add your fixed dates for the tirp.', 'tripzzy' );
				$strings['trip_date_type_recurring']  = __( 'Please select recurring date options.', 'tripzzy' );
				$strings['trip_difficulties']         = __( 'Click on the point to change difficulty level. You can change the difficulty level like Easy, Medium from the settings.', 'tripzzy' );
				$strings['use_as_default_package']    = __( 'Default Packge to display Price. Only one package can select as default. If selected, Price from this packge is displayed as default price.', 'tripzzy' );
				$strings['use_as_default_price']      = __( 'Display this price on the front end as default. Only one category can select as from price.', 'tripzzy' );
			}
			return apply_filters( 'tripzzy_filter_string_descriptions', $strings, Page::is_admin_pages() );
		}

		/**
		 * Message strings. can directly used in ajax response etc.
		 *
		 * @since 1.0.0
		 */
		public static function messages() {
			$strings = array(
				'coupon_required'           => __( 'Please add coupon code!', 'tripzzy' ),
				'error'                     => __( 'An Error has occur!!', 'tripzzy' ), // default.
				'invalid_cart_request'      => __( 'Please select atleast one category!!', 'tripzzy' ),
				'nonce_verification_failed' => __( 'Nonce verification failed!!', 'tripzzy' ),
				'page_expired'              => __( 'This link has been expired.', 'tripzzy' ),
				/* translators: %s Field Name */
				'required_field'            => __( '%s is required field!', 'tripzzy' ),
				'unable_to_add_cart_item'   => __( 'Unable to add trip in the cart!!', 'tripzzy' ),
				'wishlist_empty'            => __( 'Your wishlist is empty!', 'tripzzy' ),
			);
			return apply_filters( 'tripzzy_filter_string_messages', $strings, Page::is_admin_pages() );
		}

		/**
		 * Query strings.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Added delete string and hook.
		 */
		public static function queries() {
			$strings = array(
				'have_coupon_code' => __( 'Have a Coupon code?', 'tripzzy' ),
				/* translators: %s Placeholder */
				'delete'           => __( 'Are you sure to delete %s?', 'tripzzy' ),
				/* translators: %s Placeholder */
				'do_you_want_to'   => __( 'Do you really want to ', 'tripzzy' ),
			);

			return apply_filters( 'tripzzy_filter_string_queries', $strings, Page::is_admin_pages() );
		}

		/**
		 * Tooltip infos.
		 *
		 * @since 1.2.8
		 */
		public static function tooltips() {
			$strings = array(
				'wishlist' => __( 'Remove from the wishlist.', 'tripzzy' ),
			);
			if ( Page::is_admin_pages() ) {
				$strings['amount_display_format'] = __( 'This will display the price and currency as per the added variables.', 'tripzzy' );
				$strings['cut_off_time']          = __( 'Cut-off time determines the latest booking time before a trip starts.', 'tripzzy' );
				$strings['decimal_separator']     = __( 'This sets the decimal seperator of displayed prices.', 'tripzzy' );
				$strings['enable_trip_slider']    = __( 'Either show Trip slider or Featured image at the top of section.', 'tripzzy' );
				$strings['number_of_decimals']    = __( 'This sets the number of decimal points shown in displayed prices.', 'tripzzy' );
				$strings['select_currency']       = __( 'This controls what currency prices are listed in the displayed prices.', 'tripzzy' );
				$strings['slider_image_size']     = __( 'This will change the image size of the slider/featured image of the trip detail page.', 'tripzzy' );
				$strings['thousand_separator']    = __( 'This sets the thousands seperator of the displayed prices.', 'tripzzy' );

			}
			return apply_filters( 'tripzzy_filter_string_tooltips', $strings, Page::is_admin_pages() );
		}

		/**
		 * Checks to see whether or not a string starts with another.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $starts_with The string we're looking for at the start of $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 *
		 * @return bool True if the $string_value starts with $starts_with, false otherwise.
		 */
		public static function starts_with( $string_value, $starts_with, $case_sensitive = true ) {
			$len = strlen( $starts_with );
			if ( $len > strlen( $string_value ) ) {
				return false;
			}

			$string_value = substr( $string_value, 0, $len );

			if ( $case_sensitive ) {
				return strcmp( $string_value, $starts_with ) === 0;
			}

			return strcasecmp( $string_value, $starts_with ) === 0;
		}

		/**
		 * Checks to see whether or not a string ends with another.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $ends_with The string we're looking for at the end of $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 *
		 * @return bool True if the $string_value ends with $ends_with, false otherwise.
		 */
		public static function ends_with( $string_value, $ends_with, $case_sensitive = true ) {
			$len = strlen( $ends_with );
			if ( $len > strlen( $string_value ) ) {
				return false;
			}

			$string_value = substr( $string_value, -$len );

			if ( $case_sensitive ) {
				return strcmp( $string_value, $ends_with ) === 0;
			}

			return strcasecmp( $string_value, $ends_with ) === 0;
		}

		/**
		 * Checks if one string is contained into another at any position.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $contained The string we're looking for inside $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 * @return bool True if $contained is contained inside $string_value, false otherwise.
		 */
		public static function contains( $string_value, $contained, $case_sensitive = true ) {
			if ( $case_sensitive ) {
				return false !== strpos( $string_value, $contained );
			} else {
				return false !== stripos( $string_value, $contained );
			}
		}

		/**
		 * Get the name of a plugin in the form 'directory/file.php', as in the keys of the array returned by 'get_plugins'.
		 *
		 * @param string $plugin_file_path The path of the main plugin file (can be passed as __FILE__ from the plugin itself).
		 * @return string The name of the plugin in the form 'directory/file.php'.
		 */
		public static function plugin_name_from_plugin_file( $plugin_file_path ) {
			return basename( dirname( $plugin_file_path ) ) . DIRECTORY_SEPARATOR . basename( $plugin_file_path );
		}

		/**
		 * Remove Line Break, HTML Comments, and tab (\t), enter characters (\n) from the string.
		 *
		 * @since 1.0.6
		 *
		 * @param string $content String to trim.
		 * @return string
		 */
		public static function trim_nl( $content ) {
			// Remove HTML new line and tab chars.
			$content = str_replace( array( "\r", "\n", "\t" ), '', $content );
			// Remove HTML comments.
			$content = preg_replace( '/<!--.*?-->/', '', $content );
			return $content;
		}
	}
}
