<?php

$publicKey = file_get_contents('https://banklink.swedbank.com/public/resources/bank-certificates/009');

echo '<pre>';
//try {
    $ob = openssl_get_publickey($publicKey);
//} catch (Exception $ex){
 //   print_r(
   //     $ex
   // );
//s}
$r = openssl_pkey_get_details($ob);

print_r($r);

