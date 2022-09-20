<?php
namespace SidaPay\API\V1;

defined( 'ABSPATH' ) || exit;


class Routes {
    const BASE_URL = 'https://api.sidapay.com/api/v1';
    const AUTH = '/auth/';
    const CHECK_USERNAME = '/check_username/';
    const CHECK_TRANSACTION = '/check_transaction/';
    const SEND_OTP = '/send_otp/';
    const SUBMIT_TRANSACTION = '/submit_transaction/';
    const CONFIRM_TRANSACTION = '/confirm_transaction/';

	public static function BuildRoute( string $route, array $params = null )
	{

		$route = str_replace( array_keys( $params ), array_values( $params ), $route );

		return $route;
	}
}