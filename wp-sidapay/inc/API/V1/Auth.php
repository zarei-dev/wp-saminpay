<?php
namespace SidaPay\API\V1;

defined( 'ABSPATH' ) || exit;

class Auth {

    private $_TOKEN;

    public function __construct( $username, $password ) {
        $this->login( $username, $password );
    }

    public function login( $username, $password ) {
        $url = Routes::BASE_URL . Routes::AUTH;

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => json_encode( array(
                'username' => $username,
                'password' => $password,
            ) ),
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response = json_decode( $response['body'] );
            
            if ( !empty( $response->token ) ) {
                $this->_TOKEN = $response->token;
            }

        }

    }

	/**
	 * Get Sidapay Token
	 * @return mixed
	 */
    public function get_token() {
        return $this->_TOKEN;
    }
}