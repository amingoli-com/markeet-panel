<?php

require_once(realpath(dirname(__FILE__) . "/../table/User.php"));
require_once(realpath(dirname(__FILE__) . "/mail.php"));


class MailHandler {

    private $db = NULL;
    private $user = NULL;

    public function __construct($db) {
        $this->db = $db;
    }

    // this function will be access after submit order finished
    public function curlEmailOrder($order_id) {
        $this->executeCurl('sendEmail?id='.$order_id.'&type=NEW_ORDER');
    }

    // this function will be access after order updated
    public function curlEmailOrderProcess($order_id) {
        $this->executeCurl('sendEmail?id='.$order_id.'&type=ORDER_PROCESS');
    }

    // this function will be access after order updated
    public function curlEmailOrderUpdate($order_id) {
        $this->executeCurl('sendEmail?id='.$order_id.'&type=ORDER_UPDATE');
    }

    private function executeCurl($path) {
        if (!function_exists('curl_init')) return;
        $this->user = new User($this->db->reConnect());
        $token = $this->user->findOneToken();
        $url_arr = explode("/", $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $url = "";
        for ($i = 0; $i < count($url_arr) - 1; $i++) {
            $url = $url . $url_arr[$i] . "/";
        }
        // Get cURL resource
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url . $path);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/1.0');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Token:' . $token));

        // Send the request & save response to $resp
        curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
    }
}

?>