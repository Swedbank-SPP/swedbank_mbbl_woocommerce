<?php

class Swedbank_Client_Logger {

    public function __construct() {
        
    }

    public function logData($text) {
        $text = print_r($text, true);

        file_put_contents( __DIR__.'/../../../uploads/wc-logs/Swedbak_MBBL_V2.log', date("Y-m-d H:i:s") . "\n-----\n$text\n\n", FILE_APPEND | LOCK_EX);
    }

}
