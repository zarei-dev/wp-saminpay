<?php

use SaminPay\API\V1\Transaction;

defined( 'ABSPATH' ) || exit;

class WC_SaminPay extends \WC_Payment_Gateway {

    private $failedMassage;
    private $successMassage;
	private $Saminuser;
	private $Saminpass;
    private $Samintoken;

	public function __construct() {

        $this->id = 'WC_SaminPay';
        $this->method_title = __('درگاه پرداخت اعتباری صیدا', 'woocommerce');
        $this->method_description = __('تنظیمات درگاه پرداخت اعتباری صیدا برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
        $this->icon = apply_filters('WC_SaminPay_logo', SAMIN_PLUGIN_ROOT_URL . '/assets/images/logo.png');
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];

        $this->Saminuser = $this->settings['Saminuser'];
        $this->Saminpass = $this->settings['Saminpass'];
        $this->Samintoken = $this->settings['Samintoken'];
        
        $this->successMassage = $this->settings['success_massage'];
        $this->failedMassage = $this->settings['failed_massage'];

        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
        }

        add_action( 'woocommerce_receipt_' . $this->id, array($this, 'Send_to_SAMIN_Gateway'));
        add_action( 'woocommerce_api_' . strtolower(get_class($this)), array($this, 'Return_from_SAMIN_Gateway'));


    }

	/**
	 * Initialise settings form fields.
	 *
	 * Add an array of fields to be displayed on the gateway's settings screen.
	 *
	 *  fields can override with WC_SaminPay_Config filter
	 * @return void
	 */
    public function init_form_fields() {
        $this->form_fields = apply_filters('WC_SaminPay_Config', array(
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
                'Samintoken' => array(
                    'title' => __('توکن درگاه', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('توکن درگاه', 'woocommerce'),
                    'description' => __('این توکن پس از ثبت callback url در پنل صیدا قابل دریافت است.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'Saminuser' => array(
                    'title' => __('نام کاربری درگاه', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('نام کاربری درگاه', 'woocommerce'),
                    'description' => __('نام کاربری را اینجا وارد کنید', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'Saminpass' => array(
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

    public function SendConfirmToSamin($login,$params) {

    }

    public function Send_to_SAMIN_Gateway($order_id) {
        global $woocommerce;
        $woocommerce->session->order_id_SaminPay = $order_id;
        $order = \wc_get_order( $order_id );
        $currency = $order->get_currency();
        $currency = apply_filters('WC_SaminPay_Currency', $currency, $order_id);


        $form = '';
        $form = apply_filters('WC_SaminPay_Form', $form, $order_id, $woocommerce);

        do_action('WC_SaminPay_Gateway_Before_Form', $order_id, $woocommerce);
        echo $form;
        do_action('WC_SaminPay_Gateway_After_Form', $order_id, $woocommerce);


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
        $Amount = apply_filters('woocommerce_order_amount_total_SAMIN_gateway', $Amount, $currency);

        $Mobile = get_post_meta($order_id, '_billing_phone', true) ?: '-';
        $ResNumber = (int)$order->get_order_number();
        
        //Hooks for iranian developer
        $Mobile = apply_filters('WC_SaminPay_Mobile', $Mobile, $order_id);
        $ResNumber = apply_filters('WC_SaminPay_ResNumber', $ResNumber, $order_id);
        $Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';

        // Redirect to Samin
        $url = (new Transaction( $this->Samintoken ))->create( $order_id, $Amount, $Mobile );
        if ( $url ) {
            // Add note to order
            $order->add_order_note(
                sprintf(
                    __('کاربر به درگاه پرداخت منتقل شد.', 'woocommerce')
                )
            );
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
            do_action('WC_SaminPay_Send_to_Gateway_Failed', $order_id);
            exit;
        }


    }


	/**
	 * Payment callback : https://example.com/wc-api/wc_SaminPay/
	 * @return void
	 * @throws WC_Data_Exception
	 */
    public function Return_from_SAMIN_Gateway() {
        global $woocommerce;

        // early return if not Samin gateway
        if ( !(isset($_GET['status']) && ( isset($_GET['invoice'] ) || isset($_GET['invoice_number'] ) ) ) ) {
            return;
        }

        $invoice = isset($_GET['invoice']) ? sanitize_text_field( $_GET['invoice'] ) : sanitize_text_field( $_GET['invoice_number'] );
        $status = sanitize_text_field( $_GET['status'] );
        

        // if status != 2 the payment is not successful
        if ( $status != 2 ) {
            $order = new \WC_Order($invoice);
            $order->update_status('failed', __('پرداخت ناموفق', 'woocommerce'));
            $order->add_order_note(
                sprintf(
                    __('پرداخت ناموفق بود. کد خطا : %s', 'woocommerce'),
                    $status
                )
            );
            wp_redirect( $woocommerce->cart->get_checkout_url() );
            exit;
        }

        // payment is successful
        $username = sanitize_text_field( $_GET['username'] );
        $invoice = sanitize_text_field( $_GET['invoice'] );
        $tracking_number = sanitize_text_field( $_GET['tracking_number'] );
        $Transaction_ID = sanitize_text_field( $_GET['tracking_number'] );


        // Confirm the Payment
        $token = (new SaminPay\API\V1\Auth($this->Saminuser, $this->Saminpass))->get_token();

        $confirm = (new Transaction($token))->confirm( $tracking_number );
        if ( !$confirm || !isset($confirm->transaction_status) || $confirm->status != 200 ) {
            $order = new \WC_Order($invoice);
            $order->update_status('failed', __('پرداخت ناموفق', 'woocommerce'));
            $order->add_order_note(
                sprintf(
                    __('پرداخت ناموفق بود. کد خطا : %s', 'woocommerce'),
                    $status
                )
            );
            wp_redirect( $woocommerce->cart->get_checkout_url() );
            exit;
        }

        // payment confirmed
        $order_id = sanitize_text_field( $confirm->invoice );

        if ( !isset($order_id) ) {
            $order = new \WC_Order($invoice);
            $Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
            $Notice = wpautop(wptexturize($this->failedMassage));
            $Notice = str_replace('{fault}', $Fault, $Notice);
            $Notice = apply_filters('WC_SaminPay_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
            if ($Notice) {
                \wc_add_notice($Notice, 'error');
            }

            do_action('WC_SaminPay_Return_from_Gateway_No_Order_ID', $order_id, '0', $Fault);

            wp_redirect( $woocommerce->cart->get_checkout_url() );
            exit();
        }

        $order = new \WC_Order($order_id);
        
        $SAMIN_amount = (int)$confirm->final_amount;
        $Amount = (int)$order->get_total();
        
        if ( $SAMIN_amount != $Amount) {
            $Note = 'پرداخت سفارش موفقیت آمیز بود اما بنظر می‌رسد این سفارش ویرایش شده و با مبلغ دیگری پرداخت شده است. لطفا بررسی شود.';

            $Note = apply_filters('WC_SaminPay_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
            if ($Note) {
                $order->add_order_note($Note, 1);
            }

            $Notice = wpautop(wptexturize($this->failedMassage));
            
            do_action('WC_SaminPay_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

            wp_redirect( $woocommerce->cart->get_checkout_url() );
            exit;
        }
        

        if ( $confirm->transaction_status != 1 ) {
            $Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $Message, $tr_id);

            $Note = apply_filters('WC_SaminPay_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
            if ($Note) {
                $order->add_order_note($Note, 1);
            }

            $Notice = wpautop(wptexturize($this->failedMassage));

            $Notice = str_replace(array('{transaction_id}', '{fault}'), array($Transaction_ID, $Message), $Notice);
            $Notice = apply_filters('WC_SaminPay_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $Transaction_ID, $Fault);
            if ($Notice) {
                wc_add_notice($Notice, 'error');
            }

            do_action('WC_SaminPay_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

            wp_redirect(  $this->get_return_url($order) );
            exit;
        }


        \update_post_meta($order_id, '_transaction_id', $tracking_number);
        $order->payment_complete($tracking_number);
        $woocommerce->cart->empty_cart();
        
        $Note = sprintf(__('پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s', 'woocommerce'), $tracking_number);
        $Note = apply_filters('WC_SaminPay_Return_from_Gateway_Success_Note', $Note, $order_id, $tracking_number);
        if ($Note)
            $order->add_order_note($Note, 1);

        $Notice = wpautop(wptexturize($this->successMassage));

        $Notice = str_replace('{transaction_id}', $Transaction_ID, $Notice);

        $Notice = apply_filters('WC_SaminPay_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
        if ($Notice)
            wc_add_notice($Notice, 'success');

        do_action('WC_SaminPay_Return_from_Gateway_Success', $order_id, $Transaction_ID);

        wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
        exit;

    }

}