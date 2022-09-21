<?php
namespace SaminPay\API\SAMIN_IPG;

defined( 'ABSPATH' ) || exit;


class Routes {
    const BASE_URL = 'https://api.SaminPay.com/SAMIN_ipg';
    const CREATE_TRANSACTION = '/?client=national_id&total_price=total_price_amount&ipg_token=ipg-token&invoice=order_id';

	public static function BuildRoute( string $route, array $params = null )
	{

		$route = str_replace( array_keys( $params ), array_values( $params ), $route );

		return $route;
	}
}