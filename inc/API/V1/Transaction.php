<?php
namespace SidaPay\API\V1;

use SidaPay\API\Sida_IPG\Routes as Sida_IPG_Routes;

defined( 'ABSPATH' ) || exit;

class Transaction {

    private $_TOKEN;

    public function __construct( $token ) {
        $this->_TOKEN = $token;
    }

    public function create( $order_id, $amount ) {

        if ( empty( $this->_TOKEN ) ) {
            return false;
        }


        $url = Sida_IPG_Routes::BASE_URL . Sida_IPG_Routes::BuildRoute( Sida_IPG_Routes::CREATE_TRANSACTION, array(
            'national_id' => '09227329806',
            'total_price_amount' => $amount,
            'ipg-token' => $this->_TOKEN,
            'order_id' => $order_id,
        ));

        return $url;

        


    }
}