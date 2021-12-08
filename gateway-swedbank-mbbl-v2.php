<?php
/**
 * Plugin Name: WooCommerce Swedbank MBBL gateway V2
 * Plugin URI: https://swedbank.lt/
 * Description: Swedbank gateway V2 for Bank Link and Bank Instance support for WooCommerce.
 * Author: Darius Augaitis
 * Author URI:
 * Version: 2.0.0
 * Text Domain: swedbank-plugin
 * Domain Path: /languages
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('init', 'do_output_bufferv2');
function do_output_bufferv2()
{
    ob_start();
}

add_action('wp_enqueue_scripts', 'callback_for_setting_up_scriptsv2');

function callback_for_setting_up_scriptsv2()
{

}

add_action('plugins_loaded', 'init_swedbank_v2_mbbl_gateway_class');

function add_swedbank_v2_mbbl_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_Swedbank_mbbl_v2';
    return $methods;
}


add_filter('woocommerce_payment_gateways', 'add_swedbank_v2_mbbl_gateway_class');

add_filter('woocommerce_available_payment_gateways', 'filter_gateways2', 1);

function filter_gateways2($gateways)
{
    global $woocommerce;

    // return $gateways
    if (isset($gateways['swedbank_mbbl_v2'])) {
        $lnv = '';
        switch (strtolower(explode('_',get_locale())[1])) {
            case 'en':
                $lnv = 'en';
                break;
            case 'lt':
                $lnv = 'lt';
                break;
            case 'ee':
                $lnv = 'et';
                break;
            case 'et':
                $lnv = 'et';
                break;
            case 'ru':
                $lnv = 'ru';
                break;
            default:
                $lnv = 'en';
        }

        $json = isset($gateways['swedbank_mbbl_v2']->settings['bank_list']) ? $gateways['swedbank_mbbl_v2']->settings['bank_list'] : '';
        $pList = [];
        try {

            $json = json_decode($json);
            if(!empty($json)){
                foreach ($json as $list){
                    if($gateways['swedbank_mbbl_v2']->settings[$list->bic."_".$list->country."_payment"] === 'yes'){

                        $pList[$list->country][$list->bic] = ['icon'=>$list->logo, 'title'=>$list->name->$lnv, 'id'=>'swedbank_mbbl_v2_'.$list->bic."_".$list->country];

                        //$gateways['swedbank_mbbl_v2_'.$list->bic."_".$list->country] = clone $gateways['swedbank_mbbl_v2'];
                        //$gateways['swedbank_mbbl_v2_'.$list->bic."_".$list->country]->icon = $list->logo;
                        //$gateways['swedbank_mbbl_v2_'.$list->bic."_".$list->country]->id = 'swedbank_mbbl_v2_'.$list->bic."_".$list->country;
                        //$gateways['swedbank_mbbl_v2_'.$list->bic."_".$list->country]->title = $list->name->$lnv;
                        //$gateways['swedbank_mbbl_v2_'.$list->bic."_".$list->country]->description = $list->longName->$lnv . ' <style> .payment_method_swedbank_mbbl_v2_'.$list->bic.'_'.$list->country.' img{height: 24px;}</style>';;//__('Swedbank bank link payment', 'woocommerce');

                    }
                }
            }

        } catch (Exception $ex){
            $gateways['swedbank_mbbl_v2']->settings['debuging'] === 'yes' ? $this->log(print_r($ex,true)) : null;
            //wc_add_notice(__('Something went wrong, please try agian later', 'woocommerce'), 'error');
        }

        if(!empty($pList)){
            foreach ($pList as $key => $value){
                $gateways['swedbank_mbbl_v2_'.$key] = clone $gateways['swedbank_mbbl_v2'];
                $gateways['swedbank_mbbl_v2_'.$key]->icon = false;
                $gateways['swedbank_mbbl_v2_'.$key]->id = 'swedbank_mbbl_v2_'.$key;
                $gateways['swedbank_mbbl_v2_'.$key]->title = 'Banklink '.(count($pList) > 1 ? '('.$key.')' : '');

                $i = 1;
                $desk = '<ul>';

                foreach ($value as $item){
                    $desk .= '<li style="display: block"><a id="'.$item['id'].'" style="border: 2px solid #fff; border-radius: 8px;padding: 8px; display: inline-block; cursor: pointer; margin-bottom: 8px;" onclick="const d = new Date();d.setTime(d.getTime() + (1*24*60*60*1000));let expires = \'expires=\'+ d.toUTCString();document.cookie = \'setswpaytype='.$item['id'].';\' + expires + \';path=/\'; var el = this.parentNode.parentNode.getElementsByTagName(\'a\'); Array.prototype.forEach.call(el, function(el){ el.classList.remove(\'SWborder\') }); this.classList.add(\'SWborder\')" ><img src="'.$item['icon'].'" alt="'.$item['title'].'" height="24px" style="height: 24px" > </a></li>';
                    $i++;
                }
                $desk .= '</ul>
<style> .SWborder{border-color: #eba134!important;}</style>

';

                $gateways['swedbank_mbbl_v2_'.$key]->description = $desk;

            }
        }

//echo '<pre>';
  //      print_r($pList);
    //    die;

        unset($gateways['swedbank_mbbl_v2']);
    }

    return $gateways;
}

function init_swedbank_v2_mbbl_gateway_class()
{


    class WC_Gateway_Swedbank_mbbl_v2 extends WC_Payment_Gateway
    {

        /** @var array Array of locales */
        public $locale;

        public function log($text) {
            $text = print_r($text, true);

            file_put_contents( __DIR__.'/../../uploads/wc-logs/Swedbak_MBBL_V2.log', date("Y-m-d H:i:s") . "\n-----\n$text\n\n", FILE_APPEND | LOCK_EX);
        }

        /**
         * Constructor for the gateway.
         */
        public function __construct()
        {
            $this->id = 'swedbank_mbbl_v2';
            $this->icon = apply_filters('woocommerce_bacs_icon', '');
            $this->has_fields = true;
            $this->method_title = __('Swedbank MBBL V2', 'woocommerce');
            $this->method_description = __('Allows payments by Bank Link and Multi Bank Link Instance transfer by Swedbank.', 'woocommerce');

            // Load the settings.
            $this->init_settings();
            $this->init_form_fields($this->settings);


            // Define user set variables
            $this->title = __('Swedbank MBBL v2', 'woocommerce');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');

            $sw_publick_certificate = '';

            if (empty($this->settings['publickey_lt'])) {
                $this->settings['publickey_lt'] = $sw_publick_certificate;
            }

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action('woocommerce_thankyou_swedbank_v2_mbbl', array($this, 'thankyou_page'));

            add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields()
        {

            $this->form_fields = array(


                'general_settings' => array(
                    'id' => 'general_settings',
                    'type' => 'general_settings',
                    'title' => __('General settings', 'woocommerce'),
                ),
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('To use this plugin you need to accept <a href="https://ib.swedbank.lt/static/pdf/business/cash/cashflow/cardservice/TC_for_the_use_of_Swedbank_Payment_Portal_WooCommerce_Module_v20170421.pdf" target="_blank">terms and conditions</a>', 'woocommerce'),
                    'default' => 'no'
                ),
                'debuging' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Debugging', 'woocommerce'),
                    'description' => __('Storing transaction XML to logs. This should be turned ON only if needed to track where operation fails, otherwise keep disabled. (wp-content/uploads/wc-logs/swedbankv3.log)', 'woocommerce'),
                    'default' => 'no'
                ),
                'order_status' => array(
                    'title' => __('Order status after payment', 'woocommerce'),
                    'type' => 'select',
                    'options' => wc_get_order_statuses(),
                ),
                'instructions' => array(
                    'title' => __('Other information', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Other information will be visible on the thank you page and emails.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                ),
                'seller_id_lt' => array(
                    'title' => __('Seller ID', 'woocommerce'),
                    'type' => 'text',
                    'description' => '',
                    'desc_tip' => false,
                    'required' => false
                ),
                'contract_country' => array(
                    'title' => __('Country', 'woocommerce'),
                    'type' => 'select',
                    'options' => ["LT"=>"LT", "LV"=>"LV", "EE" => "EE"],
                ),
                'privatekey_lt' => array(
                    'title' => __('Private key', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => '',
                    'desc_tip' => false,
                    'required' => false
                ),
                'mbbl_label_lt' => array(
                    'id' => 'mbbl_label',
                    'type' => 'mbbl_label',
                    'title' => __('Get/update supported bank list', 'woocommerce')
                ),
                'publickey_lt' => array(
                    'title' => __('Swedbank publick key', 'woocommerce'),
                    'type' => 'hidden',
                'description' => '',
                'desc_tip' => false,
                'required' => false
                ),

            );

            //echo '<pre>';
            //print_r($this->settings);

            if(isset($_GET['getbanklist']) && $_GET['getbanklist'] == 1){

                if(!empty($this->settings['seller_id_lt'])) {

                    $url = "https://banklink.swedbank.com/public/api/v1/agreements/" . $this->settings['contract_country'] . "/" . $this->settings['seller_id_lt'] . "/providers";

                    $json = file_get_contents($url);
                    $certificate = file_get_contents('https://banklink.swedbank.com/public/resources/bank-certificates/009');

                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Got list of banks: '.$json."\n",true)) : null;
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Got certificate: '.$certificate."\n",true)) : null;

                    $this->settings['bank_list'] = $json;
                    $this->settings['publickey_lt'] = $certificate;
                    //wc_add_notice(__('Important! Please click "Save changes" to save bank list!', 'woocommerce'), 'error');


                } else {
                    wc_add_notice(__('Please first enter Seller Id.', 'woocommerce'), 'error');
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Empty seller id.'."\n",true)) : null;
                }

               // $this->save_settings($this->settings);


            } else {
                $json = isset($this->settings['bank_list']) ? $this->settings['bank_list'] : '';
            }

            $this->form_fields["bank_list"] = array(
                'title' => __('Bank list', 'woocommerce'),
                'type' => 'hidden',
                'description' => '',
                'desc_tip' => false,
                'required' => false
            );

            try {

                $json = json_decode($json);
                if(!empty($json)) {
                    foreach ($json as $list) {
                        $this->form_fields[$list->bic . "_" . $list->country . "_payment"] = array(
                            'title' => __('Enable/Disable', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __($list->name->en. ' ('.$list->country.')', 'woocommerce'),
                            'description' => '',
                            'default' => 'no'
                        );
                        $this->settings[$list->bic . "_" . $list->country] = json_encode($list);
                        //var_dump($list->country);
                    }
                }
                //echo '<pre>';
                //  print_r($json);
            } catch (Exception $ex){
                $this->settings['debuging'] === 'yes' ? $this->log(print_r($ex,true)) : null;
                //wc_add_notice(__('Something went wrong, please try agian later', 'woocommerce'), 'error');
            }
           // echo '<pre>';
            //print_r($this->form_fields);
         //die;
        }

        /**
         * @param int $order_id
         */
        public function thankyou_page($order_id)
        {
            if ($this->instructions) {
                echo wpautop(wptexturize(wp_kses_post($this->instructions)));
            }
            $this->bank_details($order_id);
        }

        public function mbbl()
        {

            $home_url = home_url();
            include 'includes/mbanklink.php';
            $order = wc_get_order( $_GET['order_id']);
            $ob =  new swedbank_v2_mbanklink($order, $this, $home_url);
            echo $ob->setupCon();
            die;

        }

        /**
         * Add content to the WC emails.
         *
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {


        }

        /**
         * Get bank details and place into a list format.
         *
         * @param int $order_id
         */
        private function bank_details($order_id = '')
        {
                return;
        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            require_once "includes/class-wc-gateway-swedbank-integration.php";
            global $wpdb;
            //echo '<pre>';
            //print_r($_COOKIE['setswpaytype']);
//die('--------------------------');
            $order = wc_get_order($order_id);

            $ob = new WC_Gateway_Swedbank_Integration($order, $this);
            $home_url = home_url();

            $url = $ob->get_url($home_url);

            if (!$url) {
                return array(
                    'result' => 'failure',
                    'message' => "<ul class=\"woocommerce-error\">\n\t\t\t<li><strong>Error:<\/strong> payment method failed.<\/li>\n\t\t\t<\/ul>\n",
                    "refresh" => false,
                    "reload" => false
                );
            }  else {
                $wpdb->insert($wpdb->prefix . 'swedbank_orderlist', ['orderidcart' => $url[1], 'merchantreference' => $url[2]]);

                if(!is_array($url)){
                    return array(
                        'result' => 'success',
                        'redirect' => $url,
                    );
                }

                return array(
                    'result' => 'success',
                    'redirect' => $url[0],
                );
            }
        }

        public function doDoneC($n = false, $ob = null, $idn = null, $result = null)
        {

            $this->settings['debuging'] === 'yes' ? $this->log(print_r('Redirected from bank'."\n",true)) : null;
            require 'includes/mbbl/Protocol/Protocol.php';
            $this->settings['debuging'] === 'yes' ? $this->log(print_r('Included MBBL library'."\n",true)) : null;

           try {


                $protocol = new Protocol(
                    trim($this->settings['seller_id_lt']), // seller ID (VK_SND_ID)
                    trim($this->settings['privatekey_lt']), // private key
                    '', // private key password, leave empty, if not neede
                    $this->settings['publickey_lt'], // public key
                    '' // return url
                );
                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Created setting paramaters for protocol:'."\n",true)) : null;
                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Seller id: '.$this->settings['seller_id_lt']."\n",true)) : null;
                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Swedbank public key:'.$this->settings['publickey_lt']."\n",true)) : null;

                require 'includes/mbbl/Banklink.php';
                $banklink = new Banklink($protocol);
                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Created banklink object'."\n",true)) : null;

                $this->settings['debuging'] === 'yes' ? $this->log('POST: ' . print_r($_POST, true)) : null;
                $this->settings['debuging'] === 'yes' ? $this->log('GET: ' . print_r($_GET, true)) : null;

                $r = $banklink->handleResponse(empty($_POST) ? $_GET : $_POST);

                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Checking order status text. Current set to:'.$this->settings['order_status']."\n",true)) : null;
                if (!$this->settings['order_status'] || empty($this->settings['order_status'])) {
                    $this->settings['order_status'] = 'wc-completed';
                }
                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Order status was set to: '.$this->settings['order_status']."\n",true)) : null;

                $this->settings['debuging'] === 'yes' ? $this->log(print_r('Checking if order was successful'."\n",true)) : null;

                if ($r->wasSuccessful()) {
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Procesing order us success'."\n",true)) : null;
                    $id = $_GET['order_id'];
                    $pmmm = $_GET['pmmm'];
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Getting order by id: '.$id."\n",true)) : null;
                    $order = wc_get_order($id);
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Order: '.print_r($order, true)."\n",true)) : null;
                    $order_status_list = wc_get_order_statuses();
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Order status list: '.$order_status_list."\n",true)) : null;
                    $order->update_status($this->settings['order_status'], __($order_status_list[$this->settings['order_status']], 'woocommerce'));
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Updating Order status'."\n",true)) : null;
                    // Reduce stock levels
                    wc_reduce_stock_levels($id);
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Reduced stock level'."\n",true)) : null;

                    wp_redirect($order->get_checkout_order_received_url());
                } else {
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Processing order us failure'."\n",true)) : null;
                    $id = $_GET['order_id'];
                    $pmmm = $_GET['pmmm'];
                    $order = wc_get_order($id);
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Getting Order:'.print_r($order, true)."\n",true)) : null;
                    $order->update_status('failed', __('Failed', 'woocommerce'));
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Updated status to failure'."\n",true)) : null;
                    wc_add_notice(__('Payment error:', 'woocommerce') . ' please try later.', 'error');
                    $this->settings['debuging'] === 'yes' ? $this->log(print_r('Created notification message for failure'."\n",true)) : null;
                    wp_redirect($order->get_checkout_payment_url());
                }
            } catch (Exception $e){
                $this->settings['debuging'] === 'yes' ? $this->log(print_r($e,true)) : null;
            }

        }

        public function generate_general_settings_html($key, $value)
        {

            $field = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class' => 'button-secondary',
                'css' => '',
                'custom_attributes' => array(),
                'desc_tip' => false,
                'description' => '',
                'title' => '',
            );

            $value = wp_parse_args($value, $defaults);

            ob_start();
            ?>
            <tr style="border-bottom: 1px solid #000">
                <th scope="row" class="titledesc">
                    <h1>General settings</h1>
                </th>
                <td class="forminp">

                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function generate_spp_label_html($key, $value)
        {
            $field = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class' => 'button-secondary',
                'css' => '',
                'custom_attributes' => array(),
                'desc_tip' => false,
                'description' => '',
                'title' => '',
            );

            $value = wp_parse_args($value, $defaults);

            ob_start();
            ?>
            <tr style="border-bottom: 1px solid #000">
                <th scope="row" class="titledesc">
                    <h1>SPP</h1>
                </th>
                <td class="forminp">
                    If signed with Swedbank SPP contract
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function generate_mbbl_label_html($key, $value)
        {
            $field = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class' => 'button-secondary',
                'css' => '',
                'custom_attributes' => array(),
                'desc_tip' => false,
                'description' => '',
                'title' => '',
            );

            $value = wp_parse_args($value, $defaults);

            ob_start();
            ?>
            <t>
                <td class="forminp" colspan="2">
                    <div>
                        <a class="btn btn-default" href="?page=wc-settings&tab=checkout&section=swedbank_mbbl_v2&getbanklist=1">Get/update supported bank list</a>
                    </div>
                </td>
            </t>
            <?php
            return ob_get_clean();
        }

    }

}

add_filter('query_vars', 'swedbank_v2_return');

/**
 *   Add the 'swedbank_mbbl_v2' query variable so WordPress
 *   won't remove it.
 */
function swedbank_v2_return($vars)
{
    $vars[] = "swedbank";
    return $vars;
}

/**
 *   check for  'swedbank_mbbl_v2' query variable and do what you want if its there
 */
add_action('template_redirect', 'swedbank_v2_done');

function swedbank_v2_done($template)
{

    global $wp_query;

    if (!isset($_GET['swedbank_mbbl_v2'])) {
        return $template;
    }


    if ($_GET['swedbank_mbbl_v2'] == 'doneC') {
        $WC_Gateway_Swedbank_mbbl_v2 = new WC_Gateway_Swedbank_mbbl_v2();
        //echo '<pre>';
        $WC_Gateway_Swedbank_mbbl_v2->doDoneC();

        //echo "</pre>";
        exit;
    } else if ($_GET['swedbank_mbbl_v2'] == 'redirectmbbl') {
        $WC_Gateway_Swedbank_mbbl_v2 = new WC_Gateway_Swedbank_mbbl_v2();
        //echo '<pre>';
        $WC_Gateway_Swedbank_mbbl_v2->mbbl();

        //echo "</pre>";
        exit;
    }


    return $template;
}

register_activation_hook(__FILE__, 'on_activatev2');

function on_activatev2()
{
    global $wpdb;
    $create_table_query = "
            CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}swedbank_orderlist` (
              `orderidcart` VARCHAR(25),
              `merchantreference` VARCHAR(25) 
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
    ";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($create_table_query);
}
