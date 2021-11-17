<?php

/**
 *
 * @author   
 */
include 'logger.php';

class swedbank_v2_mbanklink {

    private $order;
    private $obSw;
    private $home_url;
    private $log;

    public function __construct($order, $obSw, $home_url) {
        $this->order = $order;
        $this->obSw = $obSw;
        $this->home_url = $home_url;
        $this->log = new \Swedbank_Client_Logger();
    }

    public function setupCon() {

        $orData = $this->order->get_data();
        require __DIR__ . '/mbbl/Protocol/Protocol.php';
        if(!empty($_COOKIE['setswpaytype'])){
            $bankTypeBic = explode('_', $_COOKIE['setswpaytype'])[3];
        } else {
            $bankTypeBic = '';
        }

        $bankTypeLng = explode('_', $orData['payment_method'])[3];

        $merchantReferenceId =  $orData['id'];
        $selectedType = null;
//-----------------
        $json = isset($this->obSw->settings['bank_list']) ? $this->obSw->settings['bank_list'] : '';

        try {
            $json = json_decode($json);
            foreach ($json as $list){
                if($list->bic === $bankTypeBic && $list->country === $bankTypeLng){
                    $selectedType = $list;
                }
            }

        } catch (Exception $ex){
            $this->obSw->settings['debuging'] === 'yes' ? $this->log(print_r($ex,true)) : null;
        }

        if(!isset($selectedType)){
            foreach ($json as $list){
                if($list->country === $bankTypeLng){
                    $selectedType = $list;
                    break;
                }
            }
        }

//----------------
        $protocol = new Protocol(
            trim($this->obSw->settings['seller_id_lt']), // seller ID (VK_SND_ID)
            $this->obSw->settings['privatekey_lt'], // private key
            '', // private key password, leave empty, if not neede
            $this->obSw->settings['publickey_lt'], // public key
            $this->home_url . '/index.php?swedbank_mbbl_v2=doneC&order_id=' . $merchantReferenceId . '&pmmm=' . $orData['payment_method']
        );

        require __DIR__ . '/mbbl/Banklink.php';

        $banklink = new Banklink($protocol, '', '', $selectedType->url);

        switch (strtolower(explode('_',get_locale())[1])) {
            case 'en':
                $lnv = 'ENG';
                break;
            case 'lt':
                $lnv = 'LIT';
                break;
            case 'ee':
                $lnv = 'EST';
                break;
            case 'et':
                $lnv = 'EST';
                break;
            case 'ru':
                $lnv = 'RUS';
                break;
            default:
                $lnv = 'ENG';
        }
        $ordM = 'Order Nr: ' . $merchantReferenceId;


        $request = $banklink->getPaymentRequest($merchantReferenceId, $orData['total'], $ordM, $lnv);

        $this->obSw->settings['debuging'] === 'yes' ? $this->log->logData(print_r($request->getRequestData(),true)) : null;

//echo $request->getRequestUrl();
        return '
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <script type="text/javascript">
        function closethisasap() {
            document.forms["redirectpost"].submit();
        }
    </script>
<body onload="closethisasap();">
<form method="POST" name="redirectpost" action="' . $request->getRequestUrl() . '">

    ' . $request->getRequestInputs() . '
    <input type="submit" style="display: none;" value="Pay" />
</form>
</body>
</html>';

    }


}
