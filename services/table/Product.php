<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class Product extends REST{
	
	private $mysqli = NULL;
	private $db = NULL; 
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT * FROM product p ORDER BY p.id DESC";
		$this->show_response($this->db->get_list($query));
	}

	public function findOnePlain($id){
		$query="SELECT * FROM product p WHERE p.id=$id LIMIT 1";
		return $this->db->get_one($query);
	}

	public function findOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$this->show_response($this->findOnePlain($id));
	}

	public function allCountPlain($q, $category_id){
	    $query = "SELECT COUNT(DISTINCT p.id) FROM product p ";
        $q = addslashes($q);
	    $keywordQuery = "MATCH(p.name, p.description) AGAINST('$q') ";
	    if($category_id != -1){
            $query = $query . ", product_category pc WHERE pc.product_id=p.id AND pc.category_id=$category_id ";
            if($q != "") $query = $query . "AND " . $keywordQuery ;
	    } else {
	        if($q != "") $query = $query . "WHERE " . $keywordQuery ;
	    }
		return $this->db->get_count($query);
	}

	public function allCountPlainForClient($q, $category_id){
	    $query = "SELECT COUNT(DISTINCT p.id) FROM product p ";
        $q = addslashes($q);
        $keywordQuery = "MATCH(p.name, p.description) AGAINST('$q') ";
	    if($category_id != -1){
            $query = $query . ", product_category pc WHERE p.draft=0 AND pc.product_id=p.id AND pc.category_id=$category_id ";
            if($q != "") $query = $query . "AND " . $keywordQuery ;
	    } else {
	        if($q != "") $query = $query . "WHERE p.draft=0 AND " . $keywordQuery ;
	    }
		return $this->db->get_count($query);
	}

	public function allCount(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
		$category_id = isset($this->_request['category_id']) ? ((int)$this->_request['category_id']) : -1;
		$this->show_response_plain($this->allCountPlain($q, $category_id));
	}

	public function findAllByPagePlain($limit, $offset, $q, $category_id, $column, $order){
        $query = "SELECT DISTINCT p.* FROM product p ";
        $q = addslashes($q);
        $keywordQuery = "MATCH(p.name, p.description) AGAINST('$q') ";
        if($category_id != -1){
            $query = $query . ", product_category pc WHERE pc.product_id=p.id AND pc.category_id=$category_id ";
            if($q != "") $query = $query . "AND " . $keywordQuery ;
        } else {
            if($q != "") $query = $query . "WHERE " . $keywordQuery ;
        }
		$query = $query . "ORDER BY p.".$column." ".$order." LIMIT $limit OFFSET $offset ";
		return $this->db->get_list($query);
	}

    public function findAllByPagePlainForClient($limit, $offset, $q, $category_id, $column, $order){
        $query = "SELECT DISTINCT p.* FROM product p ";
        $q = addslashes($q);
        $keywordQuery = "MATCH(p.name, p.description) AGAINST('$q') ";
        if($category_id != -1){
            $query = $query . ", product_category pc WHERE p.draft=0 AND pc.product_id=p.id AND pc.category_id=$category_id ";
            if($q != "") $query = $query . "AND " . $keywordQuery ;
        } else {
            if($q != "") $query = $query . "WHERE p.draft=0 AND " . $keywordQuery ;
        }
        $query = $query . "ORDER BY p.".$column." ".$order." LIMIT $limit OFFSET $offset ";
        return $this->db->get_list($query);
    }

	public function findAllByPage(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['limit']) || !isset($this->_request['page']))$this->responseInvalidParam();
		$limit = (int)$this->_request['limit'];
		$offset = ((int)$this->_request['page']) - 1;
		$q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
		$category_id = isset($this->_request['category_id']) ? ((int)$this->_request['category_id']) : -1;
		$column = isset($this->_request['col']) ? $this->_request['col'] : 'id';
		$order = isset($this->_request['ord']) ? $this->_request['ord'] : 'DESC';
		$this->show_response($this->findAllByPagePlain($limit, $offset, $q, $category_id, $column, $order));
	}
	
	public function insertOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$data = json_decode(file_get_contents("php://input"),true);
		if(!isset($data) ) $this->responseInvalidParam();
		$column_names = array('name', 'image', 'price', 'price_discount', 'stock', 'draft', 'description', 'status', 'created_at', 'last_update');
		$table_name = 'product';
		$pk = 'id';
		$resp = $this->db->post_one($data, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}
	
	public function updateOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$data = json_decode(file_get_contents("php://input"),true);
		if(!isset($data['id'])) $this->responseInvalidParam();
		$id = (int)$data['id'];
		$column_names = array('name', 'image', 'price', 'price_discount', 'stock', 'draft', 'description', 'status', 'created_at', 'last_update');
		$table_name = 'product';
		$pk = 'id';
		$this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
	}
	
	public function deleteOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$table_name = 'product';
		$pk = 'id';
		$this->show_response($this->db->delete_one($id, $pk, $table_name));
	}

	public function countByDraftPlain($i){
		$query = "SELECT COUNT(DISTINCT p.id) FROM product p WHERE p.draft=$i ";
		return $this->db->get_count($query);
	}

	public function countByStatusPlain($status){
		$query = "SELECT COUNT(DISTINCT p.id) FROM product p WHERE p.status='$status' ";
		return $this->db->get_count($query);
	}
}	
?>