<?php
/**
 * Currencies.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Currencies' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Currencies {

		/**
		 * Currency option to use it in dropdown.
		 *
		 * @since 1.0.0
		 */
		public static function get_dropdown_options() {
			$lists = self::get_all();

			$options = array_map(
				function ( $value, $dropdown_list ) {
					return array(
						'label'  => html_entity_decode( sprintf( '%s (%s)', $dropdown_list['name'], $dropdown_list['symbol'] ) ),
						'value'  => $value,
						'symbol' => html_entity_decode( $dropdown_list['symbol'] ),
					);
				},
				array_keys( $lists ),
				array_values( $lists )
			);
			return $options;
		}

		/**
		 * Get Currency Symbol by code.
		 *
		 * @param string $code Currency Code.
		 * @since 1.0.0
		 * @since 1.1.4 Set default code value to 'empty' from 'USD'.
		 */
		public static function get_symbol( $code = '' ) {
			if ( ! $code ) {
				$code = self::get_code(); // if sent param as empty string.
			}
			$lists  = self::get_all();
			$symbol = isset( $lists[ $code ] ) && isset( $lists[ $code ]['symbol'] ) ? $lists[ $code ]['symbol'] : '$';

			return html_entity_decode( $symbol );
		}

		/**
		 * Get Currency Code.
		 *
		 * @return string
		 */
		public static function get_code() {
			$settings = Settings::get();
			return apply_filters( 'tripzzy_filter_currency_code', $settings['currency'] ?? 'USD' );
		}

		/**
		 * Get All Currency list.
		 *
		 * @since 1.0.0
		 */
		private static function get_all() {
			$lists = array(
				'AED' => array(
					'name'   => 'United Arab Emirates dirham',
					'symbol' => '&#x62f;.&#x625;',
				),
				'AFN' => array(
					'name'   => 'Afghan afghani',
					'symbol' => '&#x60b;',
				),
				'ALL' => array(
					'name'   => 'Albanian lek',
					'symbol' => 'L',
				),
				'AMD' => array(
					'name'   => 'Armenian dram',
					'symbol' => 'AMD',
				),
				'ANG' => array(
					'name'   => 'Netherlands Antillean guilder',
					'symbol' => '&fnof;',
				),
				'AOA' => array(
					'name'   => 'Angolan kwanza',
					'symbol' => 'Kz',
				),
				'ARS' => array(
					'name'   => 'Argentine peso',
					'symbol' => '&#36;',
				),
				'AUD' => array(
					'name'   => 'Australian dollar',
					'symbol' => '&#36;',
				),
				'AWG' => array(
					'name'   => 'Aruban florin',
					'symbol' => 'Afl.',
				),
				'AZN' => array(
					'name'   => 'Azerbaijani manat',
					'symbol' => 'AZN',
				),
				'BAM' => array(
					'name'   => 'Bosnia and Herzegovina convertible mark',
					'symbol' => 'KM',
				),
				'BBD' => array(
					'name'   => 'Barbadian dollar',
					'symbol' => '&#36;',
				),
				'BDT' => array(
					'name'   => 'Bangladeshi taka',
					'symbol' => '&#2547;&nbsp;',
				),
				'BGN' => array(
					'name'   => 'Bulgarian lev',
					'symbol' => '&#1083;&#1074;.',
				),
				'BHD' => array(
					'name'   => 'Bahraini dinar',
					'symbol' => '.&#x62f;.&#x628;',
				),
				'BIF' => array(
					'name'   => 'Burundian franc',
					'symbol' => 'Fr',
				),
				'BMD' => array(
					'name'   => 'Bermudian dollar',
					'symbol' => '&#36;',
				),
				'BND' => array(
					'name'   => 'Brunei dollar',
					'symbol' => '&#36;',
				),
				'BOB' => array(
					'name'   => 'Bolivian boliviano',
					'symbol' => 'Bs.',
				),
				'BRL' => array(
					'name'   => 'Brazilian real',
					'symbol' => '&#82;&#36;',
				),
				'BSD' => array(
					'name'   => 'Bahamian dollar',
					'symbol' => '&#36;',
				),
				'BTC' => array(
					'name'   => 'Bitcoin',
					'symbol' => '&#3647;',
				),
				'BTN' => array(
					'name'   => 'Bhutanese ngultrum',
					'symbol' => 'Nu.',
				),
				'BWP' => array(
					'name'   => 'Botswana pula',
					'symbol' => 'P',
				),
				'BYR' => array(
					'name'   => 'Belarusian ruble (old)',
					'symbol' => 'Br',
				),
				'BYN' => array(
					'name'   => 'Belarusian ruble',
					'symbol' => 'Br',
				),
				'BZD' => array(
					'name'   => 'Belize dollar',
					'symbol' => '&#36;',
				),
				'CAD' => array(
					'name'   => 'Canadian dollar',
					'symbol' => '&#36;',
				),
				'CDF' => array(
					'name'   => 'Congolese franc',
					'symbol' => 'Fr',
				),
				'CHF' => array(
					'name'   => 'Swiss franc',
					'symbol' => '&#67;&#72;&#70;',
				),
				'CLP' => array(
					'name'   => 'Chilean peso',
					'symbol' => '&#36;',
				),
				'CNY' => array(
					'name'   => 'Chinese yuan',
					'symbol' => '&yen;',
				),
				'COP' => array(
					'name'   => 'Colombian peso',
					'symbol' => '&#36;',
				),
				'CRC' => array(
					'name'   => 'Costa Rican col&oacute;n',
					'symbol' => '&#x20a1;',
				),
				'CUC' => array(
					'name'   => 'Cuban convertible peso',
					'symbol' => '&#36;',
				),
				'CUP' => array(
					'name'   => 'Cuban peso',
					'symbol' => '&#36;',
				),
				'CVE' => array(
					'name'   => 'Cape Verdean escudo',
					'symbol' => '&#36;',
				),
				'CZK' => array(
					'name'   => 'Czech koruna',
					'symbol' => '&#75;&#269;',
				),
				'DJF' => array(
					'name'   => 'Djiboutian franc',
					'symbol' => 'Fr',
				),
				'DKK' => array(
					'name'   => 'Danish krone',
					'symbol' => 'DKK',
				),
				'DOP' => array(
					'name'   => 'Dominican peso',
					'symbol' => 'RD&#36;',
				),
				'DZD' => array(
					'name'   => 'Algerian dinar',
					'symbol' => '&#x62f;.&#x62c;',
				),
				'EGP' => array(
					'name'   => 'Egyptian pound',
					'symbol' => 'EGP',
				),
				'ERN' => array(
					'name'   => 'Eritrean nakfa',
					'symbol' => 'Nfk',
				),
				'ETB' => array(
					'name'   => 'Ethiopian birr',
					'symbol' => 'Br',
				),
				'EUR' => array(
					'name'   => 'Euro',
					'symbol' => '&euro;',
				),
				'FJD' => array(
					'name'   => 'Fijian dollar',
					'symbol' => '&#36;',
				),
				'FKP' => array(
					'name'   => 'Falkland Islands pound',
					'symbol' => '&pound;',
				),
				'GBP' => array(
					'name'   => 'Pound sterling',
					'symbol' => '&pound;',
				),
				'GEL' => array(
					'name'   => 'Georgian lari',
					'symbol' => '&#x20be;',
				),
				'GGP' => array(
					'name'   => 'Guernsey pound',
					'symbol' => '&pound;',
				),
				'GHS' => array(
					'name'   => 'Ghana cedi',
					'symbol' => '&#x20b5;',
				),
				'GIP' => array(
					'name'   => 'Gibraltar pound',
					'symbol' => '&pound;',
				),
				'GMD' => array(
					'name'   => 'Gambian dalasi',
					'symbol' => 'D',
				),
				'GNF' => array(
					'name'   => 'Guinean franc',
					'symbol' => 'Fr',
				),
				'GTQ' => array(
					'name'   => 'Guatemalan quetzal',
					'symbol' => 'Q',
				),
				'GYD' => array(
					'name'   => 'Guyanese dollar',
					'symbol' => '&#36;',
				),
				'HKD' => array(
					'name'   => 'Hong Kong dollar',
					'symbol' => '&#36;',
				),
				'HNL' => array(
					'name'   => 'Honduran lempira',
					'symbol' => 'L',
				),
				'HRK' => array(
					'name'   => 'Croatian kuna',
					'symbol' => 'kn',
				),
				'HTG' => array(
					'name'   => 'Haitian gourde',
					'symbol' => 'G',
				),
				'HUF' => array(
					'name'   => 'Hungarian forint',
					'symbol' => '&#70;&#116;',
				),
				'IDR' => array(
					'name'   => 'Indonesian rupiah',
					'symbol' => 'Rp',
				),
				'ILS' => array(
					'name'   => 'Israeli new shekel',
					'symbol' => '&#8362;',
				),
				'IMP' => array(
					'name'   => 'Manx pound',
					'symbol' => '&pound;',
				),
				'INR' => array(
					'name'   => 'Indian rupee',
					'symbol' => '&#8377;',
				),
				'IQD' => array(
					'name'   => 'Iraqi dinar',
					'symbol' => '&#x639;.&#x62f;',
				),
				'IRR' => array(
					'name'   => 'Iranian rial',
					'symbol' => '&#xfdfc;',
				),
				'IRT' => array(
					'name'   => 'Iranian toman',
					'symbol' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
				),
				'ISK' => array(
					'name'   => 'Icelandic kr&oacute;na',
					'symbol' => 'kr.',
				),
				'JEP' => array(
					'name'   => 'Jersey pound',
					'symbol' => '&pound;',
				),
				'JMD' => array(
					'name'   => 'Jamaican dollar',
					'symbol' => '&#36;',
				),
				'JOD' => array(
					'name'   => 'Jordanian dinar',
					'symbol' => '&#x62f;.&#x627;',
				),
				'JPY' => array(
					'name'   => 'Japanese yen',
					'symbol' => '&yen;',
				),
				'KES' => array(
					'name'   => 'Kenyan shilling',
					'symbol' => 'KSh',
				),
				'KGS' => array(
					'name'   => 'Kyrgyzstani som',
					'symbol' => '&#x441;&#x43e;&#x43c;',
				),
				'KHR' => array(
					'name'   => 'Cambodian riel',
					'symbol' => '&#x17db;',
				),
				'KMF' => array(
					'name'   => 'Comorian franc',
					'symbol' => 'Fr',
				),
				'KPW' => array(
					'name'   => 'North Korean won',
					'symbol' => '&#x20a9;',
				),
				'KRW' => array(
					'name'   => 'South Korean won',
					'symbol' => '&#8361;',
				),
				'KWD' => array(
					'name'   => 'Kuwaiti dinar',
					'symbol' => '&#x62f;.&#x643;',
				),
				'KYD' => array(
					'name'   => 'Cayman Islands dollar',
					'symbol' => '&#36;',
				),
				'KZT' => array(
					'name'   => 'Kazakhstani tenge',
					'symbol' => 'KZT',
				),
				'LAK' => array(
					'name'   => 'Lao kip',
					'symbol' => '&#8365;',
				),
				'LBP' => array(
					'name'   => 'Lebanese pound',
					'symbol' => '&#x644;.&#x644;',
				),
				'LKR' => array(
					'name'   => 'Sri Lankan rupee',
					'symbol' => '&#xdbb;&#xdd4;',
				),
				'LRD' => array(
					'name'   => 'Liberian dollar',
					'symbol' => '&#36;',
				),
				'LSL' => array(
					'name'   => 'Lesotho loti',
					'symbol' => 'L',
				),
				'LYD' => array(
					'name'   => 'Libyan dinar',
					'symbol' => '&#x644;.&#x62f;',
				),
				'MAD' => array(
					'name'   => 'Moroccan dirham',
					'symbol' => '&#x62f;.&#x645;.',
				),
				'MDL' => array(
					'name'   => 'Moldovan leu',
					'symbol' => 'MDL',
				),
				'MGA' => array(
					'name'   => 'Malagasy ariary',
					'symbol' => 'Ar',
				),
				'MKD' => array(
					'name'   => 'Macedonian denar',
					'symbol' => '&#x434;&#x435;&#x43d;',
				),
				'MMK' => array(
					'name'   => 'Burmese kyat',
					'symbol' => 'Ks',
				),
				'MNT' => array(
					'name'   => 'Mongolian t&ouml;gr&ouml;g',
					'symbol' => '&#x20ae;',
				),
				'MOP' => array(
					'name'   => 'Macanese pataca',
					'symbol' => 'P',
				),
				'MRU' => array(
					'name'   => 'Mauritanian ouguiya',
					'symbol' => 'UM',
				),
				'MUR' => array(
					'name'   => 'Mauritian rupee',
					'symbol' => '&#x20a8;',
				),
				'MVR' => array(
					'name'   => 'Maldivian rufiyaa',
					'symbol' => '.&#x783;',
				),
				'MWK' => array(
					'name'   => 'Malawian kwacha',
					'symbol' => 'MK',
				),
				'MXN' => array(
					'name'   => 'Mexican peso',
					'symbol' => '&#36;',
				),
				'MYR' => array(
					'name'   => 'Malaysian ringgit',
					'symbol' => '&#82;&#77;',
				),
				'MZN' => array(
					'name'   => 'Mozambican metical',
					'symbol' => 'MT',
				),
				'NAD' => array(
					'name'   => 'Namibian dollar',
					'symbol' => 'N&#36;',
				),
				'NGN' => array(
					'name'   => 'Nigerian naira',
					'symbol' => '&#8358;',
				),
				'NIO' => array(
					'name'   => 'Nicaraguan c&oacute;rdoba',
					'symbol' => 'C&#36;',
				),
				'NOK' => array(
					'name'   => 'Norwegian krone',
					'symbol' => '&#107;&#114;',
				),
				'NPR' => array(
					'name'   => 'Nepalese rupee',
					'symbol' => '&#8360;',
				),
				'NZD' => array(
					'name'   => 'New Zealand dollar',
					'symbol' => '&#36;',
				),
				'OMR' => array(
					'name'   => 'Omani rial',
					'symbol' => '&#x631;.&#x639;.',
				),
				'PAB' => array(
					'name'   => 'Panamanian balboa',
					'symbol' => 'B/.',
				),
				'PEN' => array(
					'name'   => 'Sol',
					'symbol' => 'S/',
				),
				'PGK' => array(
					'name'   => 'Papua New Guinean kina',
					'symbol' => 'K',
				),
				'PHP' => array(
					'name'   => 'Philippine peso',
					'symbol' => '&#8369;',
				),
				'PKR' => array(
					'name'   => 'Pakistani rupee',
					'symbol' => '&#8360;',
				),
				'PLN' => array(
					'name'   => 'Polish z&#x142;oty',
					'symbol' => '&#122;&#322;',
				),
				'PRB' => array(
					'name'   => 'Transnistrian ruble',
					'symbol' => '&#x440;.',
				),
				'PYG' => array(
					'name'   => 'Paraguayan guaran&iacute;',
					'symbol' => '&#8370;',
				),
				'QAR' => array(
					'name'   => 'Qatari riyal',
					'symbol' => '&#x631;.&#x642;',
				),
				'RON' => array(
					'name'   => 'Romanian leu',
					'symbol' => 'lei',
				),
				'RSD' => array(
					'name'   => 'Serbian dinar',
					'symbol' => '&#x434;&#x438;&#x43d;.',
				),
				'RUB' => array(
					'name'   => 'Russian ruble',
					'symbol' => '&#8381;',
				),
				'RWF' => array(
					'name'   => 'Rwandan franc',
					'symbol' => 'Fr',
				),
				'SAR' => array(
					'name'   => 'Saudi riyal',
					'symbol' => '&#x631;.&#x633;',
				),
				'SBD' => array(
					'name'   => 'Solomon Islands dollar',
					'symbol' => '&#36;',
				),
				'SCR' => array(
					'name'   => 'Seychellois rupee',
					'symbol' => '&#x20a8;',
				),
				'SDG' => array(
					'name'   => 'Sudanese pound',
					'symbol' => '&#x62c;.&#x633;.',
				),
				'SEK' => array(
					'name'   => 'Swedish krona',
					'symbol' => '&#107;&#114;',
				),
				'SGD' => array(
					'name'   => 'Singapore dollar',
					'symbol' => '&#36;',
				),
				'SHP' => array(
					'name'   => 'Saint Helena pound',
					'symbol' => '&pound;',
				),
				'SLL' => array(
					'name'   => 'Sierra Leonean leone',
					'symbol' => 'Le',
				),
				'SOS' => array(
					'name'   => 'Somali shilling',
					'symbol' => 'Sh',
				),
				'SRD' => array(
					'name'   => 'Surinamese dollar',
					'symbol' => '&#36;',
				),
				'SSP' => array(
					'name'   => 'South Sudanese pound',
					'symbol' => '&pound;',
				),
				'STN' => array(
					'name'   => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
					'symbol' => 'Db',
				),
				'SYP' => array(
					'name'   => 'Syrian pound',
					'symbol' => '&#x644;.&#x633;',
				),
				'SZL' => array(
					'name'   => 'Swazi lilangeni',
					'symbol' => 'L',
				),
				'THB' => array(
					'name'   => 'Thai baht',
					'symbol' => '&#3647;',
				),
				'TJS' => array(
					'name'   => 'Tajikistani somoni',
					'symbol' => '&#x405;&#x41c;',
				),
				'TMT' => array(
					'name'   => 'Turkmenistan manat',
					'symbol' => 'm',
				),
				'TND' => array(
					'name'   => 'Tunisian dinar',
					'symbol' => '&#x62f;.&#x62a;',
				),
				'TOP' => array(
					'name'   => 'Tongan pa&#x2bb;anga',
					'symbol' => 'T&#36;',
				),
				'TRY' => array(
					'name'   => 'Turkish lira',
					'symbol' => '&#8378;',
				),
				'TTD' => array(
					'name'   => 'Trinidad and Tobago dollar',
					'symbol' => '&#36;',
				),
				'TWD' => array(
					'name'   => 'New Taiwan dollar',
					'symbol' => '&#78;&#84;&#36;',
				),
				'TZS' => array(
					'name'   => 'Tanzanian shilling',
					'symbol' => 'Sh',
				),
				'UAH' => array(
					'name'   => 'Ukrainian hryvnia',
					'symbol' => '&#8372;',
				),
				'UGX' => array(
					'name'   => 'Ugandan shilling',
					'symbol' => 'UGX',
				),
				'USD' => array(
					'name'   => 'United States (US) dollar',
					'symbol' => '&#36;',
				),
				'UYU' => array(
					'name'   => 'Uruguayan peso',
					'symbol' => '&#36;',
				),
				'UZS' => array(
					'name'   => 'Uzbekistani som',
					'symbol' => 'UZS',
				),
				'VEF' => array(
					'name'   => 'Venezuelan bol&iacute;var',
					'symbol' => 'Bs F',
				),
				'VES' => array(
					'name'   => 'Bol&iacute;var soberano',
					'symbol' => 'Bs.S',
				),
				'VND' => array(
					'name'   => 'Vietnamese &#x111;&#x1ed3;ng',
					'symbol' => '&#8363;',
				),
				'VUV' => array(
					'name'   => 'Vanuatu vatu',
					'symbol' => 'Vt',
				),
				'WST' => array(
					'name'   => 'Samoan t&#x101;l&#x101;',
					'symbol' => 'T',
				),
				'XAF' => array(
					'name'   => 'Central African CFA franc',
					'symbol' => 'CFA',
				),
				'XCD' => array(
					'name'   => 'East Caribbean dollar',
					'symbol' => '&#36;',
				),
				'XOF' => array(
					'name'   => 'West African CFA franc',
					'symbol' => 'CFA',
				),
				'XPF' => array(
					'name'   => 'CFP franc',
					'symbol' => 'Fr',
				),
				'YER' => array(
					'name'   => 'Yemeni rial',
					'symbol' => '&#xfdfc;',
				),
				'ZAR' => array(
					'name'   => 'South African rand',
					'symbol' => '&#82;',
				),
				'ZMW' => array(
					'name'   => 'Zambian kwacha',
					'symbol' => 'ZK',
				),
			);
			return apply_filters( 'tripzzy_filter_currencies', $lists );
		}
	}
}
