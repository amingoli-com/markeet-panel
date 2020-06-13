<?php
require_once(realpath(dirname(__FILE__) . "/../table/Config.php"));
require_once(realpath(dirname(__FILE__) . "/../table/ProductOrder.php"));
require_once(realpath(dirname(__FILE__) . "/../table/ProductOrderDetail.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));
require_once(realpath(dirname(__FILE__) . "/rest.php"));

require_once("mailer/PHPMailer.php");
require_once("mailer/Exception.php");
require_once("mailer/SMTP.php");
use PHPMailer\PHPMailer\PHPMailer;

class Mail extends REST {

    private $db = NULL;
    private $config = NULL;
    private $config_arr = NULL;
    private $product_order = NULL;
    private $product_order_detail = NULL;
    private $conf = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->config = new Config($this->db);
        $this->product_order = new ProductOrder($this->db);
        $this->product_order_detail = new ProductOrderDetail($this->db);
        $this->conf = new CONF(); // Create config class
    }

    public function restEmail() {
        if ($this->get_request_method() != "GET") $this->response('', 406);
        if (!isset($this->_request['id'])) $this->responseInvalidParam();
        if (!isset($this->_request['type'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $type = $this->_request['type'];
        $debug = false;
        if (isset($this->_request['debug'])) {
            $d = (int)$this->_request['debug'];
            $debug = ($d == 1);
        }

        // filter with config
        $this->config_arr = $this->config->findAllArr();
        if ($type == "NEW_ORDER") {
            $notif_on_order = $this->getValue($this->config_arr, 'EMAIL_NOTIF_ON_ORDER');
            if ($notif_on_order == null or $notif_on_order == "FALSE") return;

        } else if ($type == "ORDER_PROCESS") {
            $notif_on_order_process = $this->getValue($this->config_arr, 'EMAIL_NOTIF_ON_ORDER_PROCESS');
            if ($notif_on_order_process == null or $notif_on_order_process == "FALSE") return;
        } else if (!$type == "ORDER_UPDATE") {
            return;
        }

        $this->sendOrderEmail($id, $this->config_arr, $type, $debug);
    }

    private function sendOrderEmail($order_id, $config_arr, $type, $debug) {
        // find object order order_details
        $order = $this->product_order->findOnePlain($order_id);
        $order_details = $this->product_order_detail->findAllByOrderIdPlain($order_id);

        $email_reply_to = $this->getValue($this->config_arr, 'EMAIL_REPLY_TO');
        $email_receiver_arr = json_decode($this->getValue($this->config_arr, 'EMAIL_BCC_RECEIVER'), true);
        //if (sizeof($email_receiver_arr) == 0) return;

        try {
            $mailer = new PHPMailer();
            $mailer->IsSMTP();
            $mailer->SMTPAuth = true;
            // SMTP connection will not close after each email sent, reduces SMTP overhead
            $mailer->SMTPKeepAlive = true;

            $mailer->Host = $this->conf->SMTP_HOST;
            $mailer->Port = $this->conf->SMTP_PORT;
            $mailer->Username = $this->conf->SMTP_EMAIL;
            $mailer->Password = $this->conf->SMTP_PASSWORD;

            $subject_title = "";
            if ($type == "NEW_ORDER") {
                $subject_title = $this->conf->SUBJECT_EMAIL_NEW_ORDER;
                try {
                    foreach ($email_receiver_arr as $row) {
                        $mailer->addBCC($row, $row);
                    }
                } catch (Exception $e) {
                }
            } else if ($type == "ORDER_PROCESS") {
                $subject_title = $this->conf->SUBJECT_EMAIL_ORDER_PROCESSED;
            } else if ($type == "ORDER_UPDATE") {
                $subject_title = $this->conf->SUBJECT_EMAIL_ORDER_UPDATED;
            }

            $subject = '[' . $order['code'] . '] ' . $subject_title;
            $mailer->addCustomHeader('X-Entity-Ref-ID', $order['code']);
            $mailer->Subject = $subject;

            $mailer->SetFrom($this->conf->SMTP_EMAIL);
            $mailer->addReplyTo($email_reply_to);
            $mailer->addAddress($order['email'], $order['email']);
            $template = $this->getEmailOrderTemplate($order, $order_details, $config_arr, $type);
            $mailer->msgHTML($template);

            $error = 'Message sent!';
            if (!$mailer->Send()) {
                $error = 'Mail error: ' . $mailer->ErrorInfo;
            }
            if ($debug) echo $error;

        } catch (Exception $e) {
        }
    }

    private function getEmailOrderTemplate($order, $order_details, $config_arr, $type) {
        $currency = $this->getValue($config_arr, 'CURRENCY');
        $order_item_row = "";

        // calculate total
        $price_total = 0;
        $amount_total = 0;
        $index = 1;

        foreach ($order_details as $od) {
            $price_total = 0;
            $item_row = file_get_contents(realpath(dirname(__FILE__) . "/template/order_item_row.html"));
            $price_total += $od['price_item'] * $od['amount'];
            $amount_total += $price_total;
            $od['index'] = $index;
            $od['price_total'] = number_format($price_total, 2, '.', '');
            foreach ($od as $key => $value) {
                $tagToReplace = "[@$key]";
                $item_row = str_replace($tagToReplace, $value, $item_row);
            }
            $order_item_row = $order_item_row . $item_row;
            $index++;
        }

        $price_tax = ($order['tax'] / 100) * $amount_total;
        $price_tax_formatted = number_format($price_tax, 2, '.', '');
        $price_total_formatted = number_format($amount_total, 2, '.', '');
        $price_after_tax = number_format(($amount_total + $price_tax), 2, '.', '');

        // binding data
        $order_template = file_get_contents(realpath(dirname(__FILE__) . "/template/order_template.html"));
        $order['date_ship'] = date("d M y", floatval($order['date_ship']) / 1000);
        $order['created_at'] = date("d M y", floatval($order['created_at']) / 1000);
        $order['last_update'] = date("d M y", floatval($order['last_update']) / 1000);
        foreach ($order as $key => $value) {
            $tagToReplace = "[@$key]";
            $order_template = str_replace($tagToReplace, $value, $order_template);
        }

        // put row view into $order_template
        $title = "";
        if ($type == "NEW_ORDER") {
            $title = $this->conf->TITLE_REPORT_NEW_ORDER;
        } else if ($type == "ORDER_PROCESS") {
            $title = $this->conf->TITLE_REPORT_ORDER_PROCESSED;
        } else if ($type == "ORDER_UPDATE") {
            $title = $this->conf->TITLE_REPORT_ORDER_UPDATED;
        }
        $order_template = str_replace('[@report_title]', $title, $order_template);
        $order_template = str_replace('[@order_item_row]', $order_item_row, $order_template);

        $order_template = str_replace('[@conf_currency]', $currency, $order_template);
        $order_template = str_replace('[@price_tax_formatted]', $price_tax_formatted, $order_template);
        $order_template = str_replace('[@price_total_formatted]', $price_total_formatted, $order_template);
        $order_template = str_replace('[@price_after_tax]', $price_after_tax, $order_template);

        return $order_template;
    }

    private function getOrderProcessedTemplate($order, $config_arr, $type) {
        $order_template = "";
        return $order_template;
    }

    private function getValue($data, $code) {
        foreach ($data as $d) {
            if ($d['code'] == $code) {
                return $d['value'];
            }
        }
    }

}

?>