<?php

namespace SidaPay;

defined( 'ABSPATH' ) || exit;


class Helpers {
    function add_IR_currency($currencies) {
        $currencies['IRR'] = __('ریال', 'woocommerce');
        $currencies['IRT'] = __('تومان', 'woocommerce');
        $currencies['IRHR'] = __('هزار ریال', 'woocommerce');
        $currencies['IRHT'] = __('هزار تومان', 'woocommerce');

        return $currencies;
    }

    function add_IR_currency_symbol($currency_symbol, $currency)
    {
        switch ($currency) {
            case 'IRR':
                $currency_symbol = 'ریال';
                break;
            case 'IRT':
                $currency_symbol = 'تومان';
                break;
            case 'IRHR':
                $currency_symbol = 'هزار ریال';
                break;
            case 'IRHT':
                $currency_symbol = 'هزار تومان';
                break;
        }
        return $currency_symbol;
    }
}