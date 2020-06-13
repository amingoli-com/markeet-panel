<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../tools/mail_handler.php"));

class ProductOrder extends REST{
	
	private $mysqli = NULL;
	private $db = NULL;
    private $product_order_detail = NULL;
    private $fcm = NULL;
    private $mail_handler = NULL;

	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
        $this->product_order_detail = new ProductOrderDetail($this->db);
        $this->fcm = new Fcm($this->db);
        $this->mail_handler = new MailHandler($this->db);
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT * FROM product_order po ORDER BY po.id DESC";
		$this->show_response($this->db->get_list($query));
	}

    public function findOne(){
        if($this->get_request_method() != "GET") $this->response('',406);
        if(!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $query="SELECT distinct * FROM product_order po WHERE po.id=$id";
        $this->show_response($this->db->get_one($query));
    }

    public function findOnePlain($id){
        $query="SELECT * FROM product_order po WHERE po.id=$id";
        return $this->db->get_one($query);
    }
	
	public function findAllByPage(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['limit']) || !isset($this->_request['page']))$this->responseInvalidParam();
		$limit = (int)$this->_request['limit'];
		$offset = ((int)$this->_request['page']) - 1;
		$q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
        if($q != ""){
            $query=	"SELECT DISTINCT * FROM product_order po "
                    ."WHERE buyer REGEXP '$q' OR code REGEXP '$q' OR address REGEXP '$q' OR email REGEXP '$q' OR phone REGEXP '$q' OR comment REGEXP '$q' OR shipping REGEXP '$q' "
                    ."ORDER BY po.id DESC LIMIT $limit OFFSET $offset";
        } else {
		    $query="SELECT DISTINCT * FROM product_order po ORDER BY po.id DESC LIMIT $limit OFFSET $offset";
        }
		$this->show_response($this->db->get_list($query));
	}
	
	public function allCount(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$query="SELECT COUNT(DISTINCT po.id) FROM product_order po";
		$this->show_response_plain($this->db->get_count($query));
	}

    public function insertOne(){
        if($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if(!isset($data)) $this->responseInvalidParam();
        $resp = $this->insertOnePlain($data);
        $this->show_response($resp);
    }

    public function insertOnePlain($data){
        $column_names = array('code', 'buyer', 'address', 'email', 'shipping', 'date_ship', 'phone', 'comment', 'status', 'total_fees', 'tax', 'serial', 'created_at', 'last_update');
        $table_name = 'product_order';
        $pk = 'id';
        $data['code'] = $this->getRandomCode();
        $resp = $this->db->post_one($data, $pk, $column_names, $table_name);
        return $resp;
    }

    public function updateOne(){
        if($this->get_request_method() != "POST") $this->response('',406);
        $data = json_decode(file_get_contents("php://input"),true);
        if(!isset($data['id'])) $this->responseInvalidParam();
        $id = (int)$data['id'];
        $column_names = array('buyer', 'address', 'email', 'shipping', 'date_ship', 'phone', 'comment', 'status', 'total_fees', 'tax', 'serial', 'created_at', 'last_update');
        $table_name = 'product_order';
        $pk = 'id';
        $this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
    }

    public function deleteOne(){
        if($this->get_request_method() != "GET") $this->response('',406);
        if(!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $table_name = 'product_order';
        $pk = 'id';
        $this->show_response($this->db->delete_one($id, $pk, $table_name));
    }

    public function deleteOnePlain($id){
        $table_name = 'product_order';
        $pk = 'id';
        return $this->db->delete_one($id, $pk, $table_name);
    }

    public function countByStatusPlain($status){
        $query = "SELECT COUNT(DISTINCT po.id) FROM product_order po WHERE po.status='$status' ";
        return $this->db->get_count($query);
    }

    public function processOrder(){
        if($this->get_request_method() != "POST") $this->response('',406);
        $data = json_decode(file_get_contents("php://input"),true);
        if(!isset($data['id']) || !isset($data['product_order']) || !isset($data['product_order_detail'])) {
            $this->responseInvalidParam();
        }
        $id             = (int)$data['id'];
        $order          = $data['product_order'];
        $order_detail   = $data['product_order_detail'];

        $resp_od = $this->product_order_detail->checkAvailableProductOrderDetail($order_detail);
        if($resp_od['status'] == 'success'){
            // process product stock
            foreach($resp_od['data'] as $od){
                $val = (int)$od['stock'] - (int)$od['amount'];
                $product_id = $od['product_id'];
                if($val > 0){
                    $query = "UPDATE product SET stock=$val WHERE id=$product_id";
                } else {
                    $query = "UPDATE product SET stock=$val, status='OUT OF STOCK' WHERE id=$product_id";
                }
                $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            }
            // update order status
            $new_status = 'PROCESSED';
            $order_id = $order['id'];
            $query_2 = "UPDATE product_order SET status='$new_status' WHERE id=$order_id";
            $this->mysqli->query($query_2) or die($this->mysqli->error.__LINE__);

            // send notification
            $order['status'] = $new_status;
            $this->sendNotifProductOrder($order);

            // send email
            $this->mail_handler->curlEmailOrderProcess($order_id);
        }
        $this->show_response($resp_od);
    }

    private function sendNotifProductOrder($order){
        if($order['serial'] != null){
            $regid = $this->fcm->findBySerial($order['serial']);
            $data = array(
                'title' => 'Order Status Changed',
                'content' => 'Your order ' . $order['code'] .' status has been change to ' . $order['status'],
                'type' => 'PROCESS_ORDER',
                'code' => $order['code'],
                'status' => $order['status']
            );
            $this->fcm->sendPushNotification($regid['regid'], $data);
        }
    }

    // function to generate unique id
    private function getRandomCode() {
        $size = 10; // must > 6
        $alpha_key = '';
        $alpha_key2 = '';
        $keys = range('A', 'Z');
        for ($i = 0; $i < 2; $i++) {
            $alpha_key .= $keys[array_rand($keys)];
            $alpha_key2 .= $keys[array_rand($keys)];
        }
        $length = $size - 5;
        $key = '';
        $keys = range(0, 9);
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        $final_key = $alpha_key . $key . $alpha_key2;

        // make sure code is unique in database
        $query = "SELECT COUNT(DISTINCT po.id) FROM product_order po WHERE po.code='$final_key' ";
        $num_rows = $this->db->get_count($query);

        if($num_rows > 0) {
            return $this->getRandomCode();
        } else {
            return $final_key;
        }
    }
}	
?>