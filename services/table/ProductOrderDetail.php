<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../tools/mail_handler.php"));

class ProductOrderDetail extends REST{
	
	private $mysqli = NULL;
	private $db = NULL;
    private $mail_handler = NULL;
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
        $this->mail_handler = new MailHandler($this->db);
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT * FROM product_order_detail pod";
		$this->show_response($this->db->get_list($query));
	}

    public function insertAllPlain($order_id, $data){
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['order_id'] = $order_id;
        }
        $column_names = array('order_id', 'product_id', 'product_name', 'amount', 'price_item', 'created_at', 'last_update');
        $table_name = 'product_order_detail';
        return $this->db->post_array($data, $column_names, $table_name);
    }

    public function deleteInsertAll(){
        if($this->get_request_method() != "POST") $this->response('',406);
        $data = json_decode(file_get_contents("php://input"),true);
        if(!isset($data))$this->responseInvalidParam();
        $is_new = 1;
        if (isset($this->_request['is_new'])) {
            $is_new = (int)$this->_request['is_new'];
        }

        $column_names = array('order_id', 'product_id', 'product_name', 'amount', 'price_item', 'created_at', 'last_update');
        $table_name = 'product_order_detail';
        try {
            if(sizeof($data)>0){
                $query="DELETE FROM ".$table_name." WHERE order_id = ".$data[0]['order_id'];
                $this->mysqli->query($query);
            }
        } catch(Exception $e) {}

        // insert all data
        $resp = $this->db->post_array($data, $column_names, $table_name);

        // send email
        if($resp['status'] = 'success' && $is_new == 1){
            $this->mail_handler->curlEmailOrder($data[0]['order_id']);
        }
        $this->show_response($resp);
    }

	public function findAllByOrderIdPlain($order_id){
		$query = "SELECT DISTINCT * FROM product_order_detail pod WHERE pod.order_id=$order_id;";
		return $this->db->get_list($query);
	}

	public function findAllByOrderId(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['order_id'])) $this->responseInvalidParam();
		$order_id = (int)$this->_request['order_id'];
		$this->show_response($this->findAllByOrderIdPlain($order_id));
	}

    public function checkAvailableProductOrderDetail($order_detail){
        $resp = array('status' => 'success', 'data' => null);
        $status = array();

        // find and check available each product
        foreach($order_detail as $od){
            $status_item = array('product_id' => $od['product_id'], 'stock' => 0, 'amount' => 0, 'product_name' => $od['product_name'], 'msg' => 'تعداد در انبار داریم');
            $product_id = $od['product_id'];
            $query = "SELECT * FROM product p WHERE p.id=$product_id LIMIT 1";
            $result = array();
            $r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
            if($r->num_rows > 0) {
                $result = $r->fetch_assoc();
                $status_item['stock'] = $result['stock'];
                $status_item['amount'] = $od['amount'];
                if($result['stock'] < $od['amount']){
                    $status_item['msg'] = 'موجودی تمام شده';
                    $resp['status'] = 'failed';
                }
            } else {
                $status_item['msg'] = 'چنین محصولی پاک شده یا موجود نیست';
                $resp['status'] = 'failed';
            }
            array_push($status, $status_item);
        }
        $resp['data'] = $status;
        return $resp;
    }

}	
?>