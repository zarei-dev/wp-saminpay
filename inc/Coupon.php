<?php

namespace SaminPay;

defined( 'ABSPATH' ) || exit;

class Coupon {

    private $amount;
    private $discount_type;
    private $coupon_code = null;


	/**
	 *
	 * Create WooCommerce Coupon
	 *
	 * @param int $amount
	 * @param string $discount_type
	 * @param string|null $coupon_code
	 *
	 * @return false|int
	 */
    public function __construct( int $amount, string $discount_type = 'fixed_cart', string $coupon_code = null ) {
        $this->amount = $amount;
        $this->discount_type = $discount_type;
        $this->coupon_code = empty( $this->coupon_code ) ? $this->generate_coupon_code() : $coupon_code;

        return $this->create_coupon();
    }


	/**
	 * Generate Coupon started with Samin- and 6 random characters
	 * @return string
	 */
    public function generate_coupon_code(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen( $characters );
        $randomString = '';
        for ( $i = 0; $i < 6; $i++ ) {
            $randomString .= $characters[rand( 0, $charactersLength - 1 )];
        }
        return 'Samin-' . $randomString;
    }


	/**
	 * return Coupon id
	 * @return false|int
	 */
    public function create_coupon() {
        $coupon = array(
            'post_title' => $this->coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );

        $new_coupon_id = wp_insert_post( $coupon );

		if ( $new_coupon_id instanceof \WP_Error) {
			return false;
		}
	    $new_coupon_id = (int)$new_coupon_id;

        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $this->discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $this->amount );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', '1' );
        update_post_meta( $new_coupon_id, 'expiry_date', '' );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

        return $new_coupon_id;
    }

}
