<?php
require_once(realpath(dirname(__FILE__) . "/tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/tools/mail_handler.php"));

/*
 * This class handle all communication with Android Client
 */
class CLIENT extends REST{

    private $mysqli = NULL;
    private $db = NULL;
    private $product 				= NULL;
    private $product_category		= NULL;
    private $product_order			= NULL;
    private $product_order_detail	= NULL;
    private $product_image 			= NULL;
    private $category 				= NULL;
    private $user 					= NULL;
    private $fcm 					= NULL;
    private $news_info 				= NULL;
    private $currency 				= NULL;
    private $config 				= NULL;
    private $mail_handler           = NULL;
	public $conf                    = NULL;

    public function __construct($db) {
        parent::__construct();
        $this->db = $db;
        $this->mysqli = $db->mysqli;
        $this->user = new User($this->db);
        $this->product = new Product($this->db);
        $this->product_category = new ProductCategory($this->db);
        $this->product_order = new ProductOrder($this->db);
        $this->product_order_detail = new ProductOrderDetail($this->db);
        $this->product_image = new ProductImage($this->db);
        $this->category = new Category($this->db);
        $this->fcm = new Fcm($this->db);
        $this->news_info = new NewsInfo($this->db);
        $this->currency = new Currency($this->db);
        $this->config = new Config($this->db);
        $this->app_version = new AppVersion($this->db);
        $this->mail_handler = new MailHandler($this->db);
		$this->conf = new CONF();
    }

    /* Cek status version and get some config data */
    public function info(){
        if($this->get_request_method() != "GET") $this->response('',406);
        if(!isset($this->_request['version'])) $this->responseInvalidParam();
        $version = (int)$this->_request['version'];
        $query = "SELECT COUNT(DISTINCT a.id) FROM app_version a WHERE version_code = $version AND active = 1";
        $resp_ver = $this->db->get_count($query);
        $config_arr = $this->config->findAllArr();
        $info = array(
            "active" => ($resp_ver > 0),
            "tax" => $this->getValue($config_arr, 'TAX'),
            "currency" => $this->getValue($config_arr, 'CURRENCY'),
            "shipping" => json_decode($this->getValue($config_arr, 'SHIPPING'), true)
        );
        $response = array( "status" => "success", "info" => $info );
        $this->show_response($response);
    }

    /* Response featured News Info */
    public function findAllFeaturedNewsInfo(){
        if($this->get_request_method() != "GET") $this->response('',406);
        $featured_news = $this->news_info->findAllFeatured();
        $object_res = array();
        foreach ($featured_news as $r){
            unset($r['full_content']);
            array_push($object_res, $r);
        }
		$response = array(
            'status' => 'success', 'news_infos' => $object_res
        );
        $this->show_response($response);
    }

    /* Response All News Info */
    public function findAllNewsInfo(){
        if($this->get_request_method() != "GET") $this->response('',406);
        $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
        $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
        $q = isset($this->_request['q']) && $this->_request['q'] != null ? ($this->_request['q']) : "";

        $offset = ($page * $limit) - $limit;
        $count_total = $this->news_info->allCountPlain($q, 1);
        $news_infos = $this->news_info->findAllByPagePlain($limit, $offset, $q, 1);

        $object_res = array();
        foreach ($news_infos as $r){
            unset($r['full_content']);
            array_push($object_res, $r);
        }
        $count = count($news_infos);
        $response = array(
            'status' => 'success', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'news_infos' => $object_res
        );
        $this->show_response($response);
    }

    /* Response All Product */
    public function findAllProduct(){
        if($this->get_request_method() != "GET") $this->response('',406);
        $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
        $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;
        $q = isset($this->_request['q']) && $this->_request['q'] != null ? ($this->_request['q']) : "";
        $category_id = isset($this->_request['category_id']) && $this->_request['category_id'] != null ? ((int)$this->_request['category_id']) : -1;
        $column = isset($this->_request['col']) ? $this->_request['col'] : 'id';
        $order = isset($this->_request['ord']) ? $this->_request['ord'] : 'DESC';

        $offset = ($page * $limit) - $limit;
        $count_total = $this->product->allCountPlainForClient($q, $category_id);
        $products = $this->product->findAllByPagePlainForClient($limit, $offset, $q, $category_id, $column, $order);

        $object_res = array();
        foreach ($products as $r){
            unset($r['description']);
            array_push($object_res, $r);
        }
        $count = count($products);
        $response = array(
            'status' => 'success', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'products' => $object_res
        );
        $this->show_response($response);
    }

    /* Response Details Product */
    public function findProductDetails(){
        if($this->get_request_method() != "GET") $this->response('',406);
        if(!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $product = $this->product->findOnePlain($id);
		if(count($product) > 0){
			$categories = $this->category->getAllByProductIdPlain($id);
			$product_images = $this->product_image->findAllByProductIdPlain($id);
			$product['categories'] = $categories;
			$product['product_images'] = $product_images;	
			$response = array( 'status' => 'success', 'product' => $product );
		} else {
			$response = array( 'status' => 'failed', 'product' => null );
		}
        $this->show_response($response);
    }
	
    /* Response Details News Info */
    public function findNewsDetails(){
        if($this->get_request_method() != "GET") $this->response('',406);
        if(!isset($this->_request['id'])) $this->responseInvalidParam();
        $id = (int)$this->_request['id'];
        $news_info = $this->news_info->findOnePlain($id);
		$response['status'] = 'success';
		$response['news_info'] = $news_info;
        $this->show_response($response);
    }	

    /* Response All Category */
    public function findAllCategory(){
        if($this->get_request_method() != "GET") $this->response('',406);
        $categories = $this->category->findAllForClient();
        $response = array(
            'status' => 'success', 'categories' => $categories
        );
        $this->show_response($response);
    }

    /* Submit Product Order */
    public function submitProductOrder(){
        if($this->get_request_method() != "POST") $this->response('', 406);
        $data = json_decode(file_get_contents("php://input"), true);
        if(!isset($data) || !isset($data['product_order']) || !isset($data['product_order_detail'])) $this->responseInvalidParam();

        // checking security code
        if(!isset($this->_header['Security']) || $this->_header['Security'] != $this->conf->SECURITY_CODE){
            $m = array('status' => 'failed', 'msg' => 'Invalid security code', 'data' => null);
            $this->show_response($m);
            return;
        }

        // submit order
        $resp_po = $this->product_order->insertOnePlain($data['product_order']);
        if($resp_po['status'] == "success"){
            $order_id = (int)$resp_po['data']['id'];
            $resp_pod = $this->product_order_detail->insertAllPlain($order_id, $data['product_order_detail']);
            if($resp_pod['status'] == 'success'){
                $status = 'success';
                $msg = 'Success submit product order';
                // send email
                $this->mail_handler->curlEmailOrder($order_id);
            } else {
                $this->product_order->deleteOnePlain($order_id);
                $status = 'failed';
                $msg = 'Failed when submit order.';
            }
        } else {
            $status = 'failed';
            $msg = 'Failed when submit order';
        }
        $m = array('status' => $status, 'msg' => $msg, 'data' => $resp_po['data']);
        $this->show_response($m);
        return;
    }

    private function getValue($data, $code){
        foreach($data as $d){
            if($d['code'] == $code){
                return $d['value'];
            }
        }
    }
}
?>