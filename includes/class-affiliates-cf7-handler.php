<?php
/**
 * affiliates-cf7-handler.php
 * 
 * Copyright (c) 2013 "kento" Karim Rahimpur www.itthinx.com
 * 
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 * 
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * This header and all notices must be kept intact.
 * 
 * @author Karim Rahimpur
 * @package affiliates-contact-form-7
 * @since affiliates-contact-form-7 3.0.0
 */

/**
 * Plugin main handler class.
 */
class Affiliates_CF7_Handler {

	/**
	 * Adds the proper initialization action on the wp_init hook.
	 */
	public static function init() {
		add_action( 'init', array(__CLASS__, 'wp_init' ) );
	}

	/**
	 * Adds actions and filters.
	 */
	public static function wp_init() {

		Affiliates_CF7::$supported_currencies = apply_filters( 'affiliates_cf7_currencies', AFFILIATES_CF7::$supported_currencies );
		sort( Affiliates_CF7::$supported_currencies );

		// hook into after form submission, before the email is sent
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'wpcf7_before_send_mail' ) );

		if ( class_exists( 'Affiliates_Attributes' ) ) {
			$options = get_option( Affiliates_CF7::PLUGIN_OPTIONS , array() );
			$rate_adjusted = isset( $options[Affiliates_CF7::RATE_ADJUSTED] );
			if ( !$rate_adjusted ) {
				$referral_rate = isset( $options[Affiliates_CF7::REFERRAL_RATE] ) ? $options[Affiliates_CF7::REFERRAL_RATE] : Affiliates_CF7::REFERRAL_RATE_DEFAULT;
				if ( $referral_rate ) {
					$key   = get_option( 'aff_def_ref_calc_key', null );
					$value = get_option( 'aff_def_ref_calc_value', null );
					if ( empty( $key ) ) {
						if ( $key = Affiliates_Attributes::validate_key( 'referral.rate' ) ) {
							update_option( 'aff_def_ref_calc_key', $key );
						}
						if ( $referral_rate = Affiliates_Attributes::validate_value( $key, $referral_rate ) ) {
							update_option( 'aff_def_ref_calc_value', $referral_rate );
						}
					}
				}
				$options[Affiliates_CF7::RATE_ADJUSTED] = 'yes';
				update_option( Affiliates_CF7::PLUGIN_OPTIONS, $options );
			}
		} else {
			// Reset the rate flag so it gets set when switching plugins
			// back and forth.
			$options = get_option( Affiliates_CF7::PLUGIN_OPTIONS , array() );
			unset( $options[Affiliates_CF7::RATE_ADJUSTED] );
			update_option( Affiliates_CF7::PLUGIN_OPTIONS, $options );
		}

	}

	/**
	 * This hook is called from WPCF7_ContactForm::mail(...)
	 *
	 * @param WPCF7_ContactForm &$form
	 */
	public static function wpcf7_before_send_mail( WPCF7_ContactForm &$form ) {

		global $wpdb, $affiliates_db;

		$options = get_option( Affiliates_CF7::PLUGIN_OPTIONS , array() );

		$included_form_ids = isset( $options[Affiliates_CF7::INCLUDED_FORMS] ) ? $options[Affiliates_CF7::INCLUDED_FORMS] : array();
		$excluded_form_ids = isset( $options[Affiliates_CF7::EXCLUDED_FORMS] ) ? $options[Affiliates_CF7::EXCLUDED_FORMS] : array();

		$valid_form = true;
		if ( count( $included_form_ids ) > 0 ) {
			if ( !in_array( $form->id, $included_form_ids ) ) {
				$valid_form = false;
			}
		}
		if ( count( $excluded_form_ids ) > 0 ) {
			if ( in_array( $form->id, $excluded_form_ids ) ) {
				$valid_form = false;
			}
		}
		if ( !$valid_form ) {
			return;
		}

		// only record actual form fields of interest
		$scanned_fields = $form->scanned_form_tags;

		$fields = array();
		foreach ( $scanned_fields as $field ) {
			$fields[$field['name']] = $field;
		}

		$data = array();
		$posted_data = $form->posted_data;
		foreach( $posted_data as $key => $value ) {
			if ( key_exists( $key, $fields ) ) {
				$v = '';
				switch( $fields[$key]['type'] ) {
					case 'acceptance' :
						break;

					case 'captchac' :
					case 'captchar' :
						break;

					case 'checkbox' :
					case 'checkbox*' :
					case 'radio' :
					case 'select' :
					case 'select*' :
						if ( is_array( $value ) ) {
							$v = implode( ", ", $value );
						} else {
							$v = $value;
						}
						break;

					// Files are handled below
					//case 'file' :
					//case 'file*' :
					//	break;

					case 'quiz' :
						break;

					case 'response' :
						break;

					case 'submit' :
						break;

					case 'text' :
					case 'text*' :
					case 'email' :
					case 'email*' :
					case 'textarea' :
					case 'textarea*' :
					case 'number' :
					case 'number*' :
					case 'range' :
					case 'range*' :
						$v = $value;
						break;

					default :
						$v = $value;
				}

				// IMPORTANT
				// Applying htmlentities() to $v or stripping tags is VERY
				// important. Think e.g. <script> when displaying referral data
				// in the admin areas would be disastrous.
				$data[$key] = array(
					'title'  => $key,
					'domain' => AFF_CF7_PLUGIN_DOMAIN,
					'value'  => wp_strip_all_tags( $v ),
				);
			}
		}

		$uploaded_files = $form->uploaded_files;
		foreach ( $uploaded_files as $key => $value ) {
			if ( key_exists( $key, $fields ) ) {
				$data[$key] = array(
					'title'  => $key,
					'domain' => AFF_CF7_PLUGIN_DOMAIN,
					'value'  => wp_strip_all_tags( basename( $value ) ), // better paranoia than disaster
				);
			}
		}

		// can't get_the_ID() here
		$post_id = isset( $_GET['page_id'] ) ? intval( $_GET['page_id'] ) : 0;

		$description = !empty( $form->title ) ? $form->title : 'Contact Form 7';
		$base_amount = null;
		$amount = null;
		$currency = isset( $options[Affiliates_CF7::CURRENCY] ) ? $options[Affiliates_CF7::CURRENCY] : Affiliates_CF7::DEFAULT_CURRENCY;

		$use_form_amount      = isset( $options[Affiliates_CF7::USE_FORM_AMOUNT] ) ? $options[Affiliates_CF7::USE_FORM_AMOUNT] : Affiliates_CF7::DEFAULT_USE_FORM_AMOUNT;
		$use_form_base_amount = isset( $options[Affiliates_CF7::USE_FORM_BASE_AMOUNT] ) ? $options[Affiliates_CF7::USE_FORM_BASE_AMOUNT] : Affiliates_CF7::DEFAULT_USE_FORM_BASE_AMOUNT;
		$use_form_currency    = isset( $options[Affiliates_CF7::USE_FORM_CURRENCY] ) ? $options[Affiliates_CF7::USE_FORM_CURRENCY] : Affiliates_CF7::DEFAULT_USE_FORM_CURRENCY;

		// check form for value/currency?
		if ( $use_form_base_amount ) {
			if ( isset( $data['base-amount']['value'] ) && is_numeric( $data['base-amount']['value'] ) ) {
				$base_amount = bcadd( "0", $data['base-amount']['value'] );
			}
		}
		if ( $use_form_amount ) {
			if ( isset( $data['amount']['value'] ) && is_numeric( $data['amount']['value'] ) ) {
				$amount = bcadd( "0", $data['amount']['value'] );
			}
		}
		if ( $use_form_currency ) {
			if ( isset( $data['currency']['value'] ) ) {
				if ( in_array( $data['currency']['value'], Affiliates_CF7::$supported_currencies ) ) {
					$currency = $data['currency']['value'];
				}
			}
		}

		if ( class_exists( 'Affiliates_Referral_WordPress' ) ) {
			$r = new Affiliates_Referral_WordPress();
			$affiliate_id = $r->evaluate( $post_id, $description, $data, $base_amount, $amount, $currency, null, Affiliates_CF7::REFERRAL_TYPE );
		} else {
			$options = get_option( Affiliates_CF7::PLUGIN_OPTIONS , array() );
			$referral_rate  = isset( $options[Affiliates_CF7::REFERRAL_RATE] ) ? $options[Affiliates_CF7::REFERRAL_RATE] : Affiliates_CF7::REFERRAL_RATE_DEFAULT;
			if ( $base_amount !== null ) {
				$amount = round( floatval( $referral_rate ) * floatval( $base_amount ), AFFILIATES_REFERRAL_AMOUNT_DECIMALS );
			}
			$affiliate_id = affiliates_suggest_referral( $post_id, $description, $data, $amount, $currency, null, Affiliates_CF7::REFERRAL_TYPE );
		}

	}
}
Affiliates_CF7_Handler::init();
