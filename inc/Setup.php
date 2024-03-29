<?php

namespace SaminPay;

defined( 'ABSPATH' ) || exit;

class Setup {

    private $loader;
    public static $IS_WC_ACTIVE = false;

    public function __construct() {
        if ( class_exists('WC_Payment_Gateway') ) {
            self::$IS_WC_ACTIVE = true;
        }


        $this->load_dependencies();
        $this->loader = new Loader();

        $this->register_admin_hooks();

        if (self::$IS_WC_ACTIVE) {
            $this->loader->add_action( 'plugins_loaded', $this, 'register_gateway_method', 0);
        } else {
            // Show notice if WooCommerce is not installed
            $this->loader->add_action( 'admin_notices', $this, 'admin_notice__need_woocomerce');
        }

        require_once( SAMIN_PLUGIN_ROOT . 'inc/API/SAMIN_IPG/Routes.php');
        require_once( SAMIN_PLUGIN_ROOT . 'inc/API/SAMIN_IPG/Confirm.php');
        require_once( SAMIN_PLUGIN_ROOT . 'inc/API/SAMIN_IPG/Transaction.php');


    }

    public function admin_notice__need_woocomerce() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'You need to install WooCommerce to using SaminPay Gatewayes. If you do not need, please disable the SaminPay plugin.', SAMIN_TEXTDOMAIN ); ?></p>
        </div>
        <?php
    }

    public function register_admin_hooks() {

        $Helpers = new Helpers();

        if ( self::$IS_WC_ACTIVE ) {
            $this->loader->add_filter( 'woocommerce_currencies', $Helpers, 'add_IR_currency' );
            $this->loader->add_filter( 'woocommerce_currency_symbol', $Helpers, 'add_IR_currency_symbol', 10, 2 );
            $this->loader->add_filter( 'woocommerce_payment_gateways', $this, 'add_gateway_method' );
        }
    }

    private function load_dependencies() {
        require_once ( SAMIN_PLUGIN_ROOT . 'inc/Loader.php');
        require_once ( SAMIN_PLUGIN_ROOT . 'inc/Helpers.php');
    }

    public function add_gateway_method( $methods ) {
        $methods[] = 'WC_SaminPay';
        return $methods;
    }

    public function register_gateway_method() {
        require_once ( SAMIN_PLUGIN_ROOT . 'inc/WC_SaminPay.php');
    }


    public function run() {
		$this->loader->run();
	}

}