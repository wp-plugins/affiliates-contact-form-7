<?php
/**
 * affiliates-contact-form-7.php
 *
 * Copyright (c) 2013-2015 "kento" Karim Rahimpur www.itthinx.com
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
 *
 * Plugin Name: Affiliates Contact Form 7 Integration
 * Plugin URI: http://www.itthinx.com/plugins/affiliates-contact-form-7/
 * Description: Integrates Affiliates, Affiliates Pro and Affiliates Enterprise with Contact Form 7
 * Author: itthinx
 * Author URI: http://www.itthinx.com/
 * Version: 3.3.0
 * License: GPLv3
 */
if ( !defined( 'AFF_CF7_PLUGIN_DOMAIN' ) ) {
	define( 'AFF_CF7_PLUGIN_DOMAIN', 'affiliates-contact-form-7' );
}

define( 'AFF_CF7_FILE', __FILE__ );

include_once 'includes/class-affiliates-cf7.php';
