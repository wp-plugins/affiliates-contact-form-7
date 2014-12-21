<?php
/**
 * affiliates-cf7.php
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
 * Plugin main class.
 */
class Affiliates_CF7 {

	const PLUGIN_OPTIONS = 'affiliates_cf7';

	const NONCE = 'aff_cf7_admin_nonce';
	const SET_ADMIN_OPTIONS = 'set_admin_options';

	const REFERRAL_TYPE = 'contact';

	// currency
	const DEFAULT_CURRENCY = 'USD';
	const CURRENCY = 'currency';

	public static $supported_currencies = array(
	// Australian Dollar
	'AUD',
	// Brazilian Real
	'BRL',
	// Canadian Dollar
	'CAD',
	// Czech Koruna
	'CZK',
	// Danish Krone
	'DKK',
	// Euro
	'EUR',
	// Hong Kong Dollar
	'HKD',
	// Hungarian Forint
	'HUF',
	// Israeli New Sheqel
	'ILS',
	// Japanese Yen
	'JPY',
	// Malaysian Ringgit
	'MYR',
	// Mexican Peso
	'MXN',
	// Norwegian Krone
	'NOK',
	// New Zealand Dollar
	'NZD',
	// Philippine Peso
	'PHP',
	// Polish Zloty
	'PLN',
	// Pound Sterling
	'GBP',
	// Singapore Dollar
	'SGD',
	// Swedish Krona
	'SEK',
	// Swiss Franc
	'CHF',
	// Taiwan New Dollar
	'TWD',
	// Thai Baht
	'THB',
	// Turkish Lira
	'TRY',
	// U.S. Dollar
	'USD'
	);

	// forms
	const INCLUDED_FORMS = 'included_forms';
	const EXCLUDED_FORMS = 'excluded_forms';
	const PETITION_FORMS = 'petition_forms';

	const USE_FORM_AMOUNT = 'use_form_amount';
	const DEFAULT_USE_FORM_AMOUNT = false;
	const USE_FORM_BASE_AMOUNT = 'use_form_base_amount';
	const DEFAULT_USE_FORM_BASE_AMOUNT = false;
	const USE_FORM_CURRENCY = 'use_form_currency';
	const DEFAULT_USE_FORM_CURRENCY = false;

	// email
	const SUBJECT = 'subject';
	const DEFAULT_SUBJECT = "Form submission referral";
	const MESSAGE = 'message';
	const DEFAULT_MESSAGE =
"A referral has been created for a form submission on <a href='[site_url]'>[site_title]</a>.<br/>
<br/>
Greetings,<br/>
[site_title]<br/>
[site_url]
";

	const NOTIFY_ADMIN             = 'notify_admin';
	const NOTIFY_AFFILIATE         = 'notify_affiliate';
	const NOTIFY_ADMIN_DEFAULT     = true;
	const NOTIFY_AFFILIATE_DEFAULT = true;

	const REFERRAL_RATE         = "referral-rate";
	const REFERRAL_RATE_DEFAULT = "0";
	const USAGE_STATS           = 'usage_stats';
	const USAGE_STATS_DEFAULT   = true;

	const AUTO_ADJUST_DEFAULT = true;

	const RATE_ADJUSTED = 'rate-adjusted';

	private static $admin_messages = array();

	/**
	 * Activation handler.
	 */
	public static function activate() {
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( $options === null ) {
			$options = array();
			// add the options and there's no need to autoload these
			add_option( self::PLUGIN_OPTIONS, $options, null, 'no' );
		}
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo $msg;
			}
		}
	}

	/**
	 * Initializes the integration if dependencies are verified.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		if ( self::check_dependencies() ) {
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
			include_once 'class-affiliates-cf7-handler.php';
			if ( is_admin() ) {
				include_once 'class-affiliates-cf7-admin.php';
			}
		}
	}

	/**
	 * Check dependencies and print notices if they are not met.
	 * @return true if ok, false if plugins are missing
	 */
	public static function check_dependencies() {

		$result = true;

		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}

		// required plugins
		$affiliates_is_active =
			in_array( 'affiliates/affiliates.php', $active_plugins ) ||
			in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) ||
			in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		if ( !$affiliates_is_active ) {
			self::$admin_messages[] =
				"<div class='error'>" .
				__( 'The <strong>Affiliates Contact Form 7 Integration</strong> plugin requires an appropriate Affiliates plugin: <a href="http://www.itthinx.com/plugins/affiliates" target="_blank">Affiliates</a>, <a href="http://www.itthinx.com/plugins/affiliates-pro" target="_blank">Affiliates Pro</a> or <a href="http://www.itthinx.com/plugins/affiliates-enterprise" target="_blank">Affiliates Enterprise</a>.', AFF_CF7_PLUGIN_DOMAIN ) .
				"</div>";
		}
// 		$cf7_is_active = in_array( 'contact-form-7/wp-contact-form-7.php', $active_plugins );
// 		if ( !$cf7_is_active ) {
// 			self::$admin_messages[] =
// 				"<div class='error'>" .
// 				__( 'The <strong>Affiliates Contact Form 7 Integration</strong> plugin requires <a href="http://wordpress.org/extend/plugins/contact-form-7" target="_blank">Contact Form 7</a>.', AFF_CF7_PLUGIN_DOMAIN ) .
// 				"</div>";
// 		}
// 		if ( !$affiliates_is_active || !$cf7_is_active ) {
// 			$result = false;
// 		}
		if ( !$affiliates_is_active ) {
			$result = false;
		}

		// deactivate the old plugin
		$affiliates_cf7_is_active = in_array( 'affiliates-cf7/affiliates-cf7.php', $active_plugins );
		if ( $affiliates_cf7_is_active ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( function_exists('deactivate_plugins' ) ) {
				deactivate_plugins( 'affiliates-cf7/affiliates-cf7.php' );
				self::$admin_messages[] =
					"<div class='error'>" .
					__( 'The <strong>Affiliates Contact Form 7 Integration</strong> plugin version 3 and above replaces the former integration plugin (version number below 3.x).<br/>The former plugin has been deactivated and can now be deleted.', AFF_CF7_PLUGIN_DOMAIN ) .
					"</div>";
			} else {
				self::$admin_messages[] =
				"<div class='error'>" .
				__( 'The <strong>Affiliates Contact Form 7 Integration</strong> plugin version 3 and above replaces the former integration plugin with an inferior version number.<br/><strong>Please deactivate and delete the former integration plugin with version number below 3.x.</strong>', AFF_CF7_PLUGIN_DOMAIN ) .
				"</div>";
			}
		}

		return $result;
	}

}
Affiliates_CF7::init();
