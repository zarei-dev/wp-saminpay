<?php
namespace SaminPay\API\V1;

use SaminPay\API\SAMIN_IPG\Routes as SAMIN_IPG_Routes;

defined( 'ABSPATH' ) || exit;

class Transaction {

    private $_TOKEN;

    public function __construct( $token ) {
        $this->_TOKEN = $token;
    }

    public function create( $order_id, $amount, $client ) {

        if ( empty( $this->_TOKEN ) ) {
            return false;
        }


        $url = SAMIN_IPG_Routes::BASE_URL . SAMIN_IPG_Routes::BuildRoute( SAMIN_IPG_Routes::CREATE_TRANSACTION, array(
            'national_id' => $client,
            'total_price_amount' => $amount,
            'ipg-token' => $this->_TOKEN,
            'order_id' => $order_id,
        ));

        return $url;

    }

    public function confirm( $transaction_id ) {

        if ( empty( $this->_TOKEN ) ) {
            return false;
        }

        $url = Routes::BASE_URL . Routes::CONFIRM_TRANSACTION;

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_TOKEN,
            ),
            'body' => json_encode( array(
                'tracking_number' => $transaction_id,
            ) ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $err = "Something went wrong: $error_message";
            error_log( $err );

            return false;
        } else {
            $response = json_decode( $response['body'] );

            // if ( !empty( $response->transaction_id ) ) {
            //     return $response->transaction_id;
            // }

        }

        return $response;

    }
}