<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class ProductCategory extends REST{
	
	private $mysqli = NULL;
	private $db = NULL; 
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT * FROM product_category pc";
		$this->show_response($this->db->get_list($query));
	}
	
	public function deleteInsertAll(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$product_category = json_decode(file_get_contents("php://input"),true);
		if(!isset($product_category))$this->responseInvalidParam();
		
		$column_names = array('product_id', 'category_id');
		$table_name = 'product_category';
		try {
			$query="DELETE FROM ".$table_name." WHERE product_id = ".$product_category[0]['product_id'];
			$this->mysqli->query($query);
		} catch(Exception $e) {}
		$this->show_response($this->db->post_array($product_category, $column_names, $table_name));
	}
	
}	
?>