<?php
namespace SaminPay\API\V1;

defined( 'ABSPATH' ) || exit;

class Confirm {

    private $_TOKEN;

    public function __construct( $token ) {
        $this->_TOKEN = $token;
    }
    
    public function confirm_order( $tracking_number ) {

        $url = Routes::BASE_URL . Routes::CONFIRM_TRANSACTION;


        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_TOKEN,
            ),
            'body' => json_encode( array(
                'tracking_number' => $tracking_number,
            ) ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {

            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";

        } else {

            $response = json_decode( $response['body'] );
            
            // if transaction_status is 1, then the transaction is successful
            if ( !empty( $response->transaction_status ) ) {
                return $response->transaction_status;
            }

        }

    }
}