<?php

/**
 * @package   SidaPay Gateway for woocommerce
 * @author    Mohammad Zarei <mohammad.zarei1380@gmail.com>
 * @license   GPL-3.0+
 * @link      https://zareidev.ir
 *
 * Plugin Name:     SidaPay Gateway for woocommerce
 * Plugin URI:      https://zareidev.ir
 * Description:     SidaPay secure payment gateway plugin for WooCommerce
 * Version:         0.0.1
 * Author:          Mohammad Zarei
 * Author URI:      https://zareidev.ir
 * Text Domain:     wp-sidapay
 * License:         GPL-3.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:     /languages
 * Requires PHP:    7.0
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'SIDA_VERSION', '0.0.2' );
define( 'SIDA_TEXTDOMAIN', 'wp-sidapay' );
define( 'SIDA_NAME', 'SidaPay Gateway for woocommerce' );
define( 'SIDA_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SIDA_PLUGIN_ROOT_URL', plugin_dir_url( __FILE__ ) );
define( 'SIDA_PLUGIN_ABSOLUTE', __FILE__ );
define( 'SIDA_MIN_PHP_VERSION', '7.0' );
define( 'SIDA_WP_VERSION', '5.3' );


add_action(
	'init',
	static function () {
		load_plugin_textdomain( SIDA_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

if ( version_compare( PHP_VERSION, SIDA_MIN_PHP_VERSION, '<=' ) ) {
	add_action(
		'admin_init',
		static function() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	add_action(
		'admin_notices',
		static function() {
			echo wp_kses_post(
				sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					__( '"Plugin Name" requires PHP 5.6 or newer.', SIDA_TEXTDOMAIN )
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}
require_once ( SIDA_PLUGIN_ROOT . 'inc/Setup.php');
(new SidaPay\Setup())->run();