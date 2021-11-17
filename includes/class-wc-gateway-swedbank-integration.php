<?php
/**
 *
 * @package  WC_Gateway_Swedbank_Integration
 * @author   Darius Augaitis
 */

    class WC_Gateway_Swedbank_Integration  {
        private $order;
        private $swMod;

        public function __construct($order, $swMod) {
            global $woocommerce;

            $this->order = $order;
            $this->swMod = $swMod;

        }


        public function getOrder()
        {
            return $this->order;
        }


        public function getSwMod()
        {
            return $this->swMod;
        }

        public function get_url($home_url){

                $orData = $this->order->get_data();
                return [$home_url.'?swedbank_mbbl_v2=redirectmbbl&order_id='.$orData['id'], $orData['id'], $orData['id']];
         }

    }
