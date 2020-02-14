<?php
/**
 * Plugin Name: Pay via ProxyAPI
 * Plugin URI: http://woocommerce.com/products/pay-via-proxyapi/
 * Description: Your extension's description text.
 * Version: 1.0.0
 * Author: maxp555
 * Author URI: https://proxyapi.co.ke/
 * Text Domain: woocommerce-extension
 *
 * WC requires at least: 3.9.2
 * WC tested up to: 3.9.2
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) or die( 'Not allowed' );

require "proxyapi-pvp-activation.php";
require "proxyapi-pvp-deactivation.php";
require "proxyapi-pvp-uninstall.php";
require "proxyapi-pvp-settings.php";

function init_Proxy_API_PVP()
{
    class Proxy_API_PVP extends WC_Payment_Gateway
    {
        private static $instance = false;
        public function __construct()
        {
            $this->id = 'proxyapi_pvp_settings'; // payment gateway plugin ID
            $this->icon = __DIR__.'/assets/images/icon-small.png'; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->max_amount = 70000;
            $this->has_fields = false; // in case you need a custom credit card form
            $this->method_title = 'Pay via Proxy';
            $this->method_description = "Accept Safaricom Lipa na M-Pesa payments via Proxy API using PVP's Smart Payment Button.";

            $this->init_form_fields();
            $this->init_settings();
            $this->api_key = $this->get_option('api_key');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id,
                array(
                    $this,
                    'process_admin_options'
                )
            );
            add_action('wp_enqueue_scripts',
                array(
                    $this,
                    'payment_scripts'
                )
            );
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                "api_key" => array(
                    'title'       => 'API Key',
                    'type'        => 'text',
                    'description' => 'API Key assigned from Proxy API',
                    'default'     => ''
                ),
                //This controls the title which the user sees during checkout.
                'title' => array(
                    'title'       => 'Title',
                    'type'        => 'text',
                    'default'     => 'Lipa na M-Pesa'
                ),
                //This controls the description which the user sees during checkout.
                'description' => array(
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'default'     => "Check out via Safaricom's Lipa na M-Pesa.",
                )
            );
        }

//        public function payment_fields()
//        {
//            $formHtml = '
//<fieldset id="wc-' . esc_attr( $this->id ) . '-lnm-form" class="wc-payment-form" style="background:transparent;">
//    <div class="form-row form-row-wide"><label>MPhone Number<span class="required">*</span></label>
//        <input id="pvp_sender_msisdn" type="text" autocomplete="off">
//    </div>
//    <div class="clear"></div>
//</fieldset>';
//            echo $formHtml;
//        }

//        public function payment_scripts()
//        {
//            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order']))
//            {
//                return;
//            }
//
//            if('no' === $this->enabled)
//            {
//                return;
//            }
//
//            if(empty( $this->api_key))
//            {
//                return;
//            }
//
//            if (!is_ssl())
//            {
//                return;
//            }
//
//            wp_enqueue_script('pvp_js', 'https://pvp.proxyapi.co.ke/dist/pvp.min.js');
//            wp_register_script('pvp_woocommerce_js', plugins_url('assets/js/pvp.js', __FILE__), array('jquery', 'pvp_js'));
//            wp_enqueue_script('pvp_woocommerce_js');
//        }

        public function validate_fields()
        {
            if( empty( $_POST['billing']["billing_phone"]) )
            {
                wc_add_notice( 'Phone Number is required!', 'error');
                return false;
            }

            if( preg_match('/^(\+?254|0)(7|1)([\d]{8})$/', $_POST['billing']["billing_phone"]) !== 1)
            {
                wc_add_notice( 'Please enter a valid Phone Number.', 'error');
                return false;
            }
            return true;
        }

        public function process_payment($order_id)
        {
            global $woocommerce;

            // we need it to get any order details
            $order = new WC_Order( $order_id );

            $order->update_status('on-hold', 'Order sent. Awaiting Lipa na M-Pesa Confirmation');
            $woocommerce->cart->empty_cart();
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        public function webhook()
        {

        }

        private function __formatMsisdn($msisdn)
        {
            $msisdn = preg_replace("/^(\+?2547|07)/", "2547", $msisdn);
            $msisdn = preg_replace("/^(\+?2541|01)/", "2541", $msisdn);
            return $msisdn;
        }
    }
    $PVP = Proxy_API_PVP::getInstance();
}

function add_Proxy_API_PVP( $methods )
{
    $methods[] = 'Proxy_API_PVP';
    return $methods;
}

add_action('plugins_loaded', 'init_Proxy_API_PVP');
add_filter( 'woocommerce_payment_gateways', 'add_Proxy_API_PVP' );
