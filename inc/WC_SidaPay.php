<?php

use SidaPay\API\V1\Transaction;

defined( 'ABSPATH' ) || exit;

class WC_SidaPay extends \WC_Payment_Gateway {

    private $failedMassage;
    private $successMassage;
	private $sidauser;
	private $sidapass;
    private $sidatoken;

	public function __construct() {

        $this->id = 'WC_sidapay';
        $this->method_title = __('درگاه پرداخت اعتباری صیدا', 'woocommerce');
        $this->method_description = __('تنظیمات درگاه پرداخت اعتباری صیدا برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
        $this->icon = apply_filters('WC_sidapay_logo', WP_PLUGIN_URL . '/' . plugin_basename(__DIR__) . '/assets/images/logo.png');
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];

        $this->sidauser = $this->settings['sidauser'];
        $this->sidapass = $this->settings['sidapass'];
        $this->sidatoken = $this->settings['sidatoken'];
        
        $this->successMassage = $this->settings['success_massage'];
        $this->failedMassage = $this->settings['failed_massage'];

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }

        add_action( 'woocommerce_receipt_' . $this->id, array($this, 'Send_to_Sida_Gateway'));
        add_action( 'woocommerce_api_' . strtolower(get_class($this)), array($this, 'Return_from_Sida_Gateway'));


    }

	/**
	 * Initialise settings form fields.
	 *
	 * Add an array of fields to be displayed on the gateway's settings screen.
	 *
	 *  fields can override with WC_sidapay_Config filter
	 * @return void
	 */
    public function init_form_fields() {
        $this->form_fields = apply_filters('WC_sidapay_Config', array(
                'base_config' => array(
                    'title' => __('تنظیمات درگاه', 'woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'title' => array(
                    'title' => __('عنوان درگاه', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce'),
                    'default' => __('درگاه پرداخت اعتباری صیدا', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('توضیحات درگاه', 'woocommerce'),
                    'type' => 'text',
                    'desc_tip' => true,
                    'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
                    'default' => __('پرداخت با حساب کاربری صیدا', 'woocommerce')
                ),
                'account_config' => array(
                    'title' => __('تنظیمات حساب صیدا', 'woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'sidatoken' => array(
                    'title' => __('توکن درگاه', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('توکن درگاه', 'woocommerce'),
                    'description' => __('این توکن پس از ثبت callback url در پنل صیدا قابل دریافت است.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'sidauser' => array(
                    'title' => __('نام کاربری درگاه', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('نام کاربری درگاه', 'woocommerce'),
                    'description' => __('نام کاربری را اینجا وارد کنید', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'sidapass' => array(
                    'title' => __('رمز عبور درگاه', 'woocommerce'),
                    'type' => 'password',
                    'label' => __('رمز عبور درگاه', 'woocommerce'),
                    'description' => __('رمز عبور را اینجا وارد کنید', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'enabled' => array(
                    'title' => __('فعالسازی/غیرفعالسازی', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('فعالسازی درگاه صیدا', 'woocommerce'),
                    'description' => __('برای فعالسازی درگاه پرداخت صیدا باید چک باکس را تیک بزنید', 'woocommerce'),
                    'default' => 'yes',
                    'desc_tip' => true,
                ),
                'payment_config' => array(
                    'title' => __('تنظیمات عملیات پرداخت', 'woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'success_massage' => array(
                    'title' => __('پیام پرداخت موفق', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) صیدا استفاده نمایید .', 'woocommerce'),
                    'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce'),
                ),
                'failed_massage' => array(
                    'title' => __('پیام پرداخت ناموفق', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت صیدا ارسال میگردد .', 'woocommerce'),
                    'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce'),
                ),
            )
        );
    }

    public function process_payment($order_id) {
        $order = new \WC_Order($order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function SendRequestToSida( string $username, string $password,$params) {
        $token = (new \SidaPay\API\V1\Auth( $username, $password))->get_token();


    }

    public function SendConfirmToSida($login,$params) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://pay.cpg.ir/api/v1/payment/acknowledge");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($login[0] . ":" .$login[1])
                ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);


            $response = curl_exec($ch);
            $server_info = curl_getinfo($ch);
            $error    = curl_errno($ch);

            curl_close($ch);

            $output = $error ? false : json_decode($response);
            return $output;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function Send_to_Sida_Gateway($order_id) {
        global $woocommerce;
        $woocommerce->session->order_id_sidapay = $order_id;
        $order = \wc_get_order( $order_id );
        $currency = $order->get_currency();
        $currency = apply_filters('WC_sidapay_Currency', $currency, $order_id);


        $form = '';
        $form = apply_filters('WC_sidapay_Form', $form, $order_id, $woocommerce);

        do_action('WC_sidapay_Gateway_Before_Form', $order_id, $woocommerce);
        echo $form;
        do_action('WC_sidapay_Gateway_After_Form', $order_id, $woocommerce);


        $Amount = (int)$order->get_total();
        $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
        $strToLowerCurrency = strtolower($currency);
        if (
            ($strToLowerCurrency === strtolower('IRT')) ||
            ($strToLowerCurrency === strtolower('TOMAN')) ||
            $strToLowerCurrency === strtolower('Iran TOMAN') ||
            $strToLowerCurrency === strtolower('Iranian TOMAN') ||
            $strToLowerCurrency === strtolower('Iran-TOMAN') ||
            $strToLowerCurrency === strtolower('Iranian-TOMAN') ||
            $strToLowerCurrency === strtolower('Iran_TOMAN') ||
            $strToLowerCurrency === strtolower('Iranian_TOMAN') ||
            $strToLowerCurrency === strtolower('تومان') ||
            $strToLowerCurrency === strtolower('تومان ایران'
            )
        ) {
            $Amount *= 10;
        } else if (strtolower($currency) === strtolower('IRHT')) {
            $Amount *= 1000;
        } else if (strtolower($currency) === strtolower('IRHR')) {
            $Amount *= 100;
        } else if (strtolower($currency) === strtolower('IRR')) {
            $Amount *= 1;
        }


        $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
        $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
        $Amount = apply_filters('woocommerce_order_amount_total_Sida_gateway', $Amount, $currency);

        $Mobile = get_post_meta($order_id, '_billing_phone', true) ?: '-';
        $ResNumber = (int)$order->get_order_number();
        
        //Hooks for iranian developer
        $Mobile = apply_filters('WC_sidapay_Mobile', $Mobile, $order_id);
        $ResNumber = apply_filters('WC_sidapay_ResNumber', $ResNumber, $order_id);
        $Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';

        $payment_uid = rand(1000,9999999999);
        update_post_meta( $order_id, 'sidapay_uid', $payment_uid );



        // Redirect to Sida
        $url = (new Transaction( $this->sidatoken ))->create( $order_id, $Amount);

        if ( $url ) {
            wp_redirect( $url );
            exit;
        } else {
            $order->add_order_note(
                sprintf(
                    __('خطا در اتصال به درگاه پرداخت صیدا . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce')
                )
            );
            $order->update_status('failed');
            wp_redirect( wc_get_checkout_url() );
            do_action('WC_sidapay_Send_to_Gateway_Failed', $order_id);
            exit;
        }


    }


	/**
	 * Payment callback : https://example.com/wc-api/wc_sidapay/
	 * @return void
	 * @throws WC_Data_Exception
	 */
    public function Return_from_Sida_Gateway() {

        // early return if not Sida gateway
        if ( !(isset($_GET['status']) && isset($_GET['invoice'] ) ) ) {
            return;
        }

        $order_id = sanitize_text_field( $_GET['invoice'] );
        $status = sanitize_text_field( $_GET['status'] );

        // if status != 2 the payment is not successful
        if ( $status != 2 ) {
            $order = new WC_Order($order_id);
            $order->update_status('failed', __('پرداخت ناموفق', 'woocommerce'));
            $order->add_order_note(
                sprintf(
                    __('پرداخت ناموفق بود. کد خطا : %s', 'woocommerce'),
                    $status
                )
            );
            wp_redirect($this->get_return_url($order));
            exit;
        }

        // payment is successful
        $username = sanitize_text_field( $_GET['username'] );
        $tracking_number = sanitize_text_field( $_GET['tracking_number'] );


        // Confirm the Payment
        $token = (new Auth($this->sidauser, $this->sidapass))->getToken();
        $confirm = (new Confirm($token))->confirm_order($tracking_number);

        if ( $confirm ) {
            // payment is successfully confirmed

            // create coupon with amount of sida payment
            // where is the amount of payment?
            $amount = 1000;

            $coupon_id = new Coupon( $amount );

            // add coupon to order
            $order = new WC_Order($order_id);
            $order->add_coupon( $coupon_id, $amount );


        }


        // if (isset($_POST['status']) && isset($_POST['grantId']) && isset($_POST['uniqueIdentifier'])) {
        //     $statusPayment        = sanitize_text_field($_POST['status']);
        //     $grantId      = sanitize_text_field($_POST['grantId']);
        //     $InvoiceNumber      = sanitize_text_field($_POST['uniqueIdentifier']);
        //     global $woocommerce;
        //     global $wpdb;
        //     $iuid = intval($InvoiceNumber);
        //     $results = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_value = '$iuid' AND meta_key = 'sidapay_uid'", ARRAY_A );
        //     if (count($results) > 0) {
        //         $InvoiceNumber = $results[0]['post_id'];
        //         $order_id = $InvoiceNumber;
        //     }
            
        //     if ($order_id) {

        //         $order = new \WC_Order($order_id);
        //         $currency = $order->get_currency();
        //         $currency = apply_filters('WC_sidapay_Currency', $currency, $order_id);

        //         if ($order->status !== 'completed') {

        //             if ($statusPayment == 'Success') {

        //                 $MerchantID = $this->merchantCode;
        //                 $Amount = (int)$order->order_total;
        //                 $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
        //                 $strToLowerCurrency = strtolower($currency);
        //                 if (
        //                     ($strToLowerCurrency === strtolower('IRT')) ||
        //                     ($strToLowerCurrency === strtolower('TOMAN')) ||
        //                     $strToLowerCurrency === strtolower('Iran TOMAN') ||
        //                     $strToLowerCurrency === strtolower('Iranian TOMAN') ||
        //                     $strToLowerCurrency === strtolower('Iran-TOMAN') ||
        //                     $strToLowerCurrency === strtolower('Iranian-TOMAN') ||
        //                     $strToLowerCurrency === strtolower('Iran_TOMAN') ||
        //                     $strToLowerCurrency === strtolower('Iranian_TOMAN') ||
        //                     $strToLowerCurrency === strtolower('تومان') ||
        //                     $strToLowerCurrency === strtolower('تومان ایران'
        //                     )
        //                 ) {
        //                     $Amount *= 10;
        //                 } else if (strtolower($currency) === strtolower('IRHT')) {
        //                     $Amount *= 1000;
        //                 } else if (strtolower($currency) === strtolower('IRHR')) {
        //                     $Amount *= 100;
        //                 } else if (strtolower($currency) === strtolower('IRR')) {
        //                     $Amount *= 1;
        //                 }
        //                 $login=array($this->sidauser , $this->sidapass);
        //                 $data = array('uniqueIdentifier' => "$iuid");
        //                 $result = $this->getStatusPayment($login,$data);
                        
                        
                        
        //                 if ($result->result->status == "1") {
                            
        //                 $login=array($this->sidauser , $this->sidapass);    
        //                 $dataConfirm = array('token' => get_post_meta($order_id,'token_sidapay',true));
        //                 $resultConfirm = $this->SendConfirmToSida($login,$dataConfirm);    
                            
        //                     $Status = 'completed';
        //                     $Transaction_ID = $result->result->referenceNumber;
        //                     $Fault = '';
        //                     $Message = '';  
                            
                            
        //                 } else {
        //                     $order = wc_get_order($order_id);
        //                 $order->set_status('cancelled');
        //                 $order->save();
        //                 unset($woocommerce->session->order_id_sidapay);
        //                     $Status = 'failed';
        //                     $Fault = $result->code;
        //                     $Message = 'تراکنش ناموفق بود - '.$result->description;
        //                 }
        //             } else {
        //                 // $order = wc_get_order($order_id);
        //                 // $order->set_status('cancelled');
        //                 // $order->save();
        //                 unset($woocommerce->session->order_id_sidapay);
        //                 $Status = 'failed';
        //                 $Fault = '';
        //                 $Message = 'تراکنش انجام نشد .';
        //             }

        //             if ($Status === 'completed' && isset($Transaction_ID) && $Transaction_ID !== 0) {
        //                 update_post_meta($order_id, '_transaction_id', $Transaction_ID);


        //                 $order->payment_complete($Transaction_ID);
        //                 $woocommerce->cart->empty_cart();

        //                 $Note = sprintf(__('پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s', 'woocommerce'), $Transaction_ID);
        //                 $Note = apply_filters('WC_sidapay_Return_from_Gateway_Success_Note', $Note, $order_id, $Transaction_ID);
        //                 if ($Note)
        //                     $order->add_order_note($Note, 1);


        //                 $Notice = wpautop(wptexturize($this->successMassage));

        //                 $Notice = str_replace('{transaction_id}', $Transaction_ID, $Notice);

        //                 $Notice = apply_filters('WC_sidapay_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
        //                 if ($Notice)
        //                     wc_add_notice($Notice, 'success');

        //                 do_action('WC_sidapay_Return_from_Gateway_Success', $order_id, $Transaction_ID);

        //                 wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
        //                 exit;
        //             }

        //             if (($Transaction_ID && ($Transaction_ID != 0))) {
        //                 $tr_id = ('<br/>توکن : ' . $Transaction_ID);
        //             } else {
        //                 $tr_id = '';
        //             }

        //             $Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $Message, $tr_id);

        //             $Note = apply_filters('WC_sidapay_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
        //             if ($Note) {
        //                 $order->add_order_note($Note, 1);
        //             }

        //             $Notice = wpautop(wptexturize($this->failedMassage));

        //             $Notice = str_replace(array('{transaction_id}', '{fault}'), array($Transaction_ID, $Message), $Notice);
        //             $Notice = apply_filters('WC_sidapay_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $Transaction_ID, $Fault);
        //             if ($Notice) {
        //                 wc_add_notice($Notice, 'error');
        //             }

        //             do_action('WC_sidapay_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

        //             wp_redirect($woocommerce->cart->get_checkout_url());
        //             exit;
        //         }

        //         $Transaction_ID = get_post_meta($order_id, '_transaction_id', true);

        //         $Notice = wpautop(wptexturize($this->successMassage));

        //         $Notice = str_replace('{transaction_id}', $Transaction_ID, $Notice);

        //         $Notice = apply_filters('WC_sidapay_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $Transaction_ID);
        //         if ($Notice) {
        //             wc_add_notice($Notice, 'success');
        //         }

        //         do_action('WC_sidapay_Return_from_Gateway_ReSuccess', $order_id, $Transaction_ID);

        //         wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
        //         exit;
        //     }

        //     $Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
        //     $Notice = wpautop(wptexturize($this->failedMassage));
        //     $Notice = str_replace('{fault}', $Fault, $Notice);
        //     $Notice = apply_filters('WC_sidapay_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
        //     if ($Notice) {
        //         wc_add_notice($Notice, 'error');
        //     }

        //     do_action('WC_sidapay_Return_from_Gateway_No_Order_ID', $order_id, '0', $Fault);

        //     wp_redirect($woocommerce->cart->get_checkout_url());
        //     exit;
        // }
    }


	/**
	 * @param $login
	 * @param $params
	 *
	 * @return bool
	 */
    public function getStatusPayment($login,$params) {
		return true;
    }



}