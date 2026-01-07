<?php
/**
 * Countries.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Countries' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Countries {

		/**
		 * Country option to use it in dropdown.
		 *
		 * @since 1.0.0
		 */
		public static function get_dropdown_options() {
			$lists = self::get_all();

			$options = array_map(
				function ( $value, $name ) {
					return array(
						'label' => $name,
						'value' => $value,
					);
				},
				array_keys( $lists ),
				array_values( $lists )
			);
			return $options;
		}

		/**
		 * Get Country Symbol by code.
		 *
		 * @param string $code Country Code.
		 * @since 1.0.0
		 */
		public static function get_symbol( $code = 'USD' ) {
			if ( ! $code ) {
				$code = 'USD'; // if sent param as empty string.
			}
			$lists  = self::get_all();
			$symbol = isset( $lists[ $code ] ) && isset( $lists[ $code ]['symbol'] ) ? $lists[ $code ]['symbol'] : '$';

			return html_entity_decode( $symbol );
		}

		/**
		 * Get All Country list.
		 *
		 * @since 1.0.0
		 */
		private static function get_all() {
			$lists = array(
				'AF' => __( 'Afghanistan', 'tripzzy' ),
				'AX' => __( 'Åland Islands', 'tripzzy' ),
				'AL' => __( 'Albania', 'tripzzy' ),
				'DZ' => __( 'Algeria', 'tripzzy' ),
				'AS' => __( 'American Samoa', 'tripzzy' ),
				'AD' => __( 'Andorra', 'tripzzy' ),
				'AO' => __( 'Angola', 'tripzzy' ),
				'AI' => __( 'Anguilla', 'tripzzy' ),
				'AQ' => __( 'Antarctica', 'tripzzy' ),
				'AG' => __( 'Antigua and Barbuda', 'tripzzy' ),
				'AR' => __( 'Argentina', 'tripzzy' ),
				'AM' => __( 'Armenia', 'tripzzy' ),
				'AW' => __( 'Aruba', 'tripzzy' ),
				'AU' => __( 'Australia', 'tripzzy' ),
				'AT' => __( 'Austria', 'tripzzy' ),
				'AZ' => __( 'Azerbaijan', 'tripzzy' ),
				'BS' => __( 'Bahamas', 'tripzzy' ),
				'BH' => __( 'Bahrain', 'tripzzy' ),
				'BD' => __( 'Bangladesh', 'tripzzy' ),
				'BB' => __( 'Barbados', 'tripzzy' ),
				'BY' => __( 'Belarus', 'tripzzy' ),
				'BE' => __( 'Belgium', 'tripzzy' ),
				'PW' => __( 'Belau', 'tripzzy' ),
				'BZ' => __( 'Belize', 'tripzzy' ),
				'BJ' => __( 'Benin', 'tripzzy' ),
				'BM' => __( 'Bermuda', 'tripzzy' ),
				'BT' => __( 'Bhutan', 'tripzzy' ),
				'BO' => __( 'Bolivia', 'tripzzy' ),
				'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'tripzzy' ),
				'BA' => __( 'Bosnia and Herzegovina', 'tripzzy' ),
				'BW' => __( 'Botswana', 'tripzzy' ),
				'BV' => __( 'Bouvet Island', 'tripzzy' ),
				'BR' => __( 'Brazil', 'tripzzy' ),
				'IO' => __( 'British Indian Ocean Territory', 'tripzzy' ),
				'BN' => __( 'Brunei', 'tripzzy' ),
				'BG' => __( 'Bulgaria', 'tripzzy' ),
				'BF' => __( 'Burkina Faso', 'tripzzy' ),
				'BI' => __( 'Burundi', 'tripzzy' ),
				'KH' => __( 'Cambodia', 'tripzzy' ),
				'CM' => __( 'Cameroon', 'tripzzy' ),
				'CA' => __( 'Canada', 'tripzzy' ),
				'CV' => __( 'Cape Verde', 'tripzzy' ),
				'KY' => __( 'Cayman Islands', 'tripzzy' ),
				'CF' => __( 'Central African Republic', 'tripzzy' ),
				'TD' => __( 'Chad', 'tripzzy' ),
				'CL' => __( 'Chile', 'tripzzy' ),
				'CN' => __( 'China', 'tripzzy' ),
				'CX' => __( 'Christmas Island', 'tripzzy' ),
				'CC' => __( 'Cocos (Keeling) Islands', 'tripzzy' ),
				'CO' => __( 'Colombia', 'tripzzy' ),
				'KM' => __( 'Comoros', 'tripzzy' ),
				'CG' => __( 'Congo (Brazzaville)', 'tripzzy' ),
				'CD' => __( 'Congo (Kinshasa)', 'tripzzy' ),
				'CK' => __( 'Cook Islands', 'tripzzy' ),
				'CR' => __( 'Costa Rica', 'tripzzy' ),
				'HR' => __( 'Croatia', 'tripzzy' ),
				'CU' => __( 'Cuba', 'tripzzy' ),
				'CW' => __( 'Cura&ccedil;ao', 'tripzzy' ),
				'CY' => __( 'Cyprus', 'tripzzy' ),
				'CZ' => __( 'Czech Republic', 'tripzzy' ),
				'DK' => __( 'Denmark', 'tripzzy' ),
				'DJ' => __( 'Djibouti', 'tripzzy' ),
				'DM' => __( 'Dominica', 'tripzzy' ),
				'DO' => __( 'Dominican Republic', 'tripzzy' ),
				'EC' => __( 'Ecuador', 'tripzzy' ),
				'EG' => __( 'Egypt', 'tripzzy' ),
				'SV' => __( 'El Salvador', 'tripzzy' ),
				'GQ' => __( 'Equatorial Guinea', 'tripzzy' ),
				'ER' => __( 'Eritrea', 'tripzzy' ),
				'EE' => __( 'Estonia', 'tripzzy' ),
				'ET' => __( 'Ethiopia', 'tripzzy' ),
				'FK' => __( 'Falkland Islands', 'tripzzy' ),
				'FO' => __( 'Faroe Islands', 'tripzzy' ),
				'FJ' => __( 'Fiji', 'tripzzy' ),
				'FI' => __( 'Finland', 'tripzzy' ),
				'FR' => __( 'France', 'tripzzy' ),
				'GF' => __( 'French Guiana', 'tripzzy' ),
				'PF' => __( 'French Polynesia', 'tripzzy' ),
				'TF' => __( 'French Southern Territories', 'tripzzy' ),
				'GA' => __( 'Gabon', 'tripzzy' ),
				'GM' => __( 'Gambia', 'tripzzy' ),
				'GE' => __( 'Georgia', 'tripzzy' ),
				'DE' => __( 'Germany', 'tripzzy' ),
				'GH' => __( 'Ghana', 'tripzzy' ),
				'GI' => __( 'Gibraltar', 'tripzzy' ),
				'GR' => __( 'Greece', 'tripzzy' ),
				'GL' => __( 'Greenland', 'tripzzy' ),
				'GD' => __( 'Grenada', 'tripzzy' ),
				'GP' => __( 'Guadeloupe', 'tripzzy' ),
				'GU' => __( 'Guam', 'tripzzy' ),
				'GT' => __( 'Guatemala', 'tripzzy' ),
				'GG' => __( 'Guernsey', 'tripzzy' ),
				'GN' => __( 'Guinea', 'tripzzy' ),
				'GW' => __( 'Guinea-Bissau', 'tripzzy' ),
				'GY' => __( 'Guyana', 'tripzzy' ),
				'HT' => __( 'Haiti', 'tripzzy' ),
				'HM' => __( 'Heard Island and McDonald Islands', 'tripzzy' ),
				'HN' => __( 'Honduras', 'tripzzy' ),
				'HK' => __( 'Hong Kong', 'tripzzy' ),
				'HU' => __( 'Hungary', 'tripzzy' ),
				'IS' => __( 'Iceland', 'tripzzy' ),
				'IN' => __( 'India', 'tripzzy' ),
				'ID' => __( 'Indonesia', 'tripzzy' ),
				'IR' => __( 'Iran', 'tripzzy' ),
				'IQ' => __( 'Iraq', 'tripzzy' ),
				'IE' => __( 'Ireland', 'tripzzy' ),
				'IM' => __( 'Isle of Man', 'tripzzy' ),
				'IL' => __( 'Israel', 'tripzzy' ),
				'IT' => __( 'Italy', 'tripzzy' ),
				'CI' => __( 'Ivory Coast', 'tripzzy' ),
				'JM' => __( 'Jamaica', 'tripzzy' ),
				'JP' => __( 'Japan', 'tripzzy' ),
				'JE' => __( 'Jersey', 'tripzzy' ),
				'JO' => __( 'Jordan', 'tripzzy' ),
				'KZ' => __( 'Kazakhstan', 'tripzzy' ),
				'KE' => __( 'Kenya', 'tripzzy' ),
				'KI' => __( 'Kiribati', 'tripzzy' ),
				'KW' => __( 'Kuwait', 'tripzzy' ),
				'KG' => __( 'Kyrgyzstan', 'tripzzy' ),
				'LA' => __( 'Laos', 'tripzzy' ),
				'LV' => __( 'Latvia', 'tripzzy' ),
				'LB' => __( 'Lebanon', 'tripzzy' ),
				'LS' => __( 'Lesotho', 'tripzzy' ),
				'LR' => __( 'Liberia', 'tripzzy' ),
				'LY' => __( 'Libya', 'tripzzy' ),
				'LI' => __( 'Liechtenstein', 'tripzzy' ),
				'LT' => __( 'Lithuania', 'tripzzy' ),
				'LU' => __( 'Luxembourg', 'tripzzy' ),
				'MO' => __( 'Macao', 'tripzzy' ),
				'MK' => __( 'North Macedonia', 'tripzzy' ),
				'MG' => __( 'Madagascar', 'tripzzy' ),
				'MW' => __( 'Malawi', 'tripzzy' ),
				'MY' => __( 'Malaysia', 'tripzzy' ),
				'MV' => __( 'Maldives', 'tripzzy' ),
				'ML' => __( 'Mali', 'tripzzy' ),
				'MT' => __( 'Malta', 'tripzzy' ),
				'MH' => __( 'Marshall Islands', 'tripzzy' ),
				'MQ' => __( 'Martinique', 'tripzzy' ),
				'MR' => __( 'Mauritania', 'tripzzy' ),
				'MU' => __( 'Mauritius', 'tripzzy' ),
				'YT' => __( 'Mayotte', 'tripzzy' ),
				'MX' => __( 'Mexico', 'tripzzy' ),
				'FM' => __( 'Micronesia', 'tripzzy' ),
				'MD' => __( 'Moldova', 'tripzzy' ),
				'MC' => __( 'Monaco', 'tripzzy' ),
				'MN' => __( 'Mongolia', 'tripzzy' ),
				'ME' => __( 'Montenegro', 'tripzzy' ),
				'MS' => __( 'Montserrat', 'tripzzy' ),
				'MA' => __( 'Morocco', 'tripzzy' ),
				'MZ' => __( 'Mozambique', 'tripzzy' ),
				'MM' => __( 'Myanmar', 'tripzzy' ),
				'NA' => __( 'Namibia', 'tripzzy' ),
				'NR' => __( 'Nauru', 'tripzzy' ),
				'NP' => __( 'Nepal', 'tripzzy' ),
				'NL' => __( 'Netherlands', 'tripzzy' ),
				'NC' => __( 'New Caledonia', 'tripzzy' ),
				'NZ' => __( 'New Zealand', 'tripzzy' ),
				'NI' => __( 'Nicaragua', 'tripzzy' ),
				'NE' => __( 'Niger', 'tripzzy' ),
				'NG' => __( 'Nigeria', 'tripzzy' ),
				'NU' => __( 'Niue', 'tripzzy' ),
				'NF' => __( 'Norfolk Island', 'tripzzy' ),
				'MP' => __( 'Northern Mariana Islands', 'tripzzy' ),
				'KP' => __( 'North Korea', 'tripzzy' ),
				'NO' => __( 'Norway', 'tripzzy' ),
				'OM' => __( 'Oman', 'tripzzy' ),
				'PK' => __( 'Pakistan', 'tripzzy' ),
				'PS' => __( 'Palestinian Territory', 'tripzzy' ),
				'PA' => __( 'Panama', 'tripzzy' ),
				'PG' => __( 'Papua New Guinea', 'tripzzy' ),
				'PY' => __( 'Paraguay', 'tripzzy' ),
				'PE' => __( 'Peru', 'tripzzy' ),
				'PH' => __( 'Philippines', 'tripzzy' ),
				'PN' => __( 'Pitcairn', 'tripzzy' ),
				'PL' => __( 'Poland', 'tripzzy' ),
				'PT' => __( 'Portugal', 'tripzzy' ),
				'PR' => __( 'Puerto Rico', 'tripzzy' ),
				'QA' => __( 'Qatar', 'tripzzy' ),
				'RE' => __( 'Reunion', 'tripzzy' ),
				'RO' => __( 'Romania', 'tripzzy' ),
				'RU' => __( 'Russia', 'tripzzy' ),
				'RW' => __( 'Rwanda', 'tripzzy' ),
				'BL' => __( 'Saint Barth&eacute;lemy', 'tripzzy' ),
				'SH' => __( 'Saint Helena', 'tripzzy' ),
				'KN' => __( 'Saint Kitts and Nevis', 'tripzzy' ),
				'LC' => __( 'Saint Lucia', 'tripzzy' ),
				'MF' => __( 'Saint Martin (French part)', 'tripzzy' ),
				'SX' => __( 'Saint Martin (Dutch part)', 'tripzzy' ),
				'PM' => __( 'Saint Pierre and Miquelon', 'tripzzy' ),
				'VC' => __( 'Saint Vincent and the Grenadines', 'tripzzy' ),
				'SM' => __( 'San Marino', 'tripzzy' ),
				'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'tripzzy' ),
				'SA' => __( 'Saudi Arabia', 'tripzzy' ),
				'SN' => __( 'Senegal', 'tripzzy' ),
				'RS' => __( 'Serbia', 'tripzzy' ),
				'SC' => __( 'Seychelles', 'tripzzy' ),
				'SL' => __( 'Sierra Leone', 'tripzzy' ),
				'SG' => __( 'Singapore', 'tripzzy' ),
				'SK' => __( 'Slovakia', 'tripzzy' ),
				'SI' => __( 'Slovenia', 'tripzzy' ),
				'SB' => __( 'Solomon Islands', 'tripzzy' ),
				'SO' => __( 'Somalia', 'tripzzy' ),
				'ZA' => __( 'South Africa', 'tripzzy' ),
				'GS' => __( 'South Georgia/Sandwich Islands', 'tripzzy' ),
				'KR' => __( 'South Korea', 'tripzzy' ),
				'SS' => __( 'South Sudan', 'tripzzy' ),
				'ES' => __( 'Spain', 'tripzzy' ),
				'LK' => __( 'Sri Lanka', 'tripzzy' ),
				'SD' => __( 'Sudan', 'tripzzy' ),
				'SR' => __( 'Suriname', 'tripzzy' ),
				'SJ' => __( 'Svalbard and Jan Mayen', 'tripzzy' ),
				'SZ' => __( 'Eswatini', 'tripzzy' ),
				'SE' => __( 'Sweden', 'tripzzy' ),
				'CH' => __( 'Switzerland', 'tripzzy' ),
				'SY' => __( 'Syria', 'tripzzy' ),
				'TW' => __( 'Taiwan', 'tripzzy' ),
				'TJ' => __( 'Tajikistan', 'tripzzy' ),
				'TZ' => __( 'Tanzania', 'tripzzy' ),
				'TH' => __( 'Thailand', 'tripzzy' ),
				'TL' => __( 'Timor-Leste', 'tripzzy' ),
				'TG' => __( 'Togo', 'tripzzy' ),
				'TK' => __( 'Tokelau', 'tripzzy' ),
				'TO' => __( 'Tonga', 'tripzzy' ),
				'TT' => __( 'Trinidad and Tobago', 'tripzzy' ),
				'TN' => __( 'Tunisia', 'tripzzy' ),
				'TR' => __( 'Turkey', 'tripzzy' ),
				'TM' => __( 'Turkmenistan', 'tripzzy' ),
				'TC' => __( 'Turks and Caicos Islands', 'tripzzy' ),
				'TV' => __( 'Tuvalu', 'tripzzy' ),
				'UG' => __( 'Uganda', 'tripzzy' ),
				'UA' => __( 'Ukraine', 'tripzzy' ),
				'AE' => __( 'United Arab Emirates', 'tripzzy' ),
				'GB' => __( 'United Kingdom (UK)', 'tripzzy' ),
				'US' => __( 'United States (US)', 'tripzzy' ),
				'UM' => __( 'United States (US) Minor Outlying Islands', 'tripzzy' ),
				'UY' => __( 'Uruguay', 'tripzzy' ),
				'UZ' => __( 'Uzbekistan', 'tripzzy' ),
				'VU' => __( 'Vanuatu', 'tripzzy' ),
				'VA' => __( 'Vatican', 'tripzzy' ),
				'VE' => __( 'Venezuela', 'tripzzy' ),
				'VN' => __( 'Vietnam', 'tripzzy' ),
				'VG' => __( 'Virgin Islands (British)', 'tripzzy' ),
				'VI' => __( 'Virgin Islands (US)', 'tripzzy' ),
				'WF' => __( 'Wallis and Futuna', 'tripzzy' ),
				'EH' => __( 'Western Sahara', 'tripzzy' ),
				'WS' => __( 'Samoa', 'tripzzy' ),
				'YE' => __( 'Yemen', 'tripzzy' ),
				'ZM' => __( 'Zambia', 'tripzzy' ),
				'ZW' => __( 'Zimbabwe', 'tripzzy' ),
			);
			return apply_filters( 'tripzzy_filter_countries', $lists );
		}
	}
}
