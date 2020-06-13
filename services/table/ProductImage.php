<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class ProductImage extends REST{

	private $mysqli = NULL;
	private $db = NULL;
	private $upload_path = NULL;
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
		$this->upload_path = dirname(__FILE__) . "../../../uploads/product/";
    }

	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$query="SELECT DISTINCT * FROM product_image;";
		$this->show_response($this->db->get_list($query));
	}
	
	public function findAllByProductIdPlain($product_id){
		$query="SELECT DISTINCT * FROM product_image i WHERE i.product_id=$product_id";
		return $this->db->get_list($query);
	}

	public function findAllByProductId(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['product_id']))$this->responseInvalidParam();
		$product_id = (int)$this->_request['product_id'];
		$this->show_response($this->findAllByProductIdPlain($product_id));
	}

	public function insertAll(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$product_image = json_decode(file_get_contents("php://input"),true);
		if(!isset($product_image))$this->responseInvalidParam();
		$column_names = array('product_id', 'name');
		$table_name = 'product_image';
		try {
			$query="DELETE FROM ".$table_name." WHERE product_id = ".$product_image[0]['product_id'];
			$this->mysqli->query($query);
		} catch(Exception $e) {}
		$resp = $this->db->post_array($product_image, $column_names, $table_name);
		$this->show_response($resp);
	}

	public function delete(){
		if($this->get_request_method() != "DELETE") $this->response('',406);
		if(!isset($this->_request['name']))$this->responseInvalidParam();
		$_name = $this->_request['name'];
		$table_name = 'product_image';
		$pk = 'name';
		$target_file = $this->upload_path . $_name;
		if(file_exists($target_file)){
			unlink($target_file);
		}
		$resp = $this->db->delete_one_str($_name, $pk, $table_name);
		$this->show_response($resp);
	}
	
	public function findAllByProductId_arr($product_id){
		$query = "SELECT * FROM product_image i WHERE i.product_id=".$product_id;
		return $this->db->get_list($query);
	}
	
}	
?>