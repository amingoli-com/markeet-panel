<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class Category extends REST{
	
	private $mysqli = NULL;
	private $db = NULL; 
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT * FROM category c ORDER BY c.priority ASC";
		$this->show_response($this->db->get_list($query));
	}
	
	public function findAllForClient(){
		$query="SELECT * FROM category c WHERE c.draft=0 ORDER BY c.priority ASC";
		return $this->db->get_list($query);
	}

	public function findOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$query="SELECT distinct * FROM category c WHERE c.id=$id";
		$this->show_response($this->db->get_one($query));
	}

    public function allCountPlain($q, $client){
		$query = "SELECT COUNT(DISTINCT c.id) FROM category c ";
		$keywordQuery = "(c.name REGEXP '$q' OR c.name REGEXP '$q' OR c.brief REGEXP '$q') ";
		if($client != 0){
			$query = $query . " WHERE c.draft <> 1 ";
			if($q != "") $query = $query . "AND " . $keywordQuery ;
		} else {
			if($q != "") $query = $query . "WHERE " . $keywordQuery ;
		}
        return $this->db->get_count($query);
    }

	public function allCount(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
		$client = (isset($this->_request['client'])) ? ((int)$this->_request['client']) : 0;
		$this->show_response_plain($this->allCountPlain($q, $client));
	}

	public function findAllByPagePlain($limit, $offset, $q, $client){
		$query = "SELECT c.* FROM category c ";
		$keywordQuery = "(c.name REGEXP '$q' OR c.name REGEXP '$q' OR c.brief REGEXP '$q') ";
		if($client != 0){
			$query = $query . " WHERE c.draft <> 1 ";
			if($q != "") $query = $query . "AND " . $keywordQuery ;
		} else {
			if($q != "") $query = $query . "WHERE " . $keywordQuery ;
		}
		$query = $query . "ORDER BY c.id DESC LIMIT $limit OFFSET $offset ";
		return $this->db->get_list($query);
	}

	public function findAllByPage(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['limit']) || !isset($this->_request['page']))$this->responseInvalidParam();
		$limit = (int)$this->_request['limit'];
		$offset = ((int)$this->_request['page']) - 1;
		$q = (isset($this->_request['q'])) ? ($this->_request['q']) : "";
		$client = (isset($this->_request['client'])) ? ((int)$this->_request['client']) : 0;
		$this->show_response($this->findAllByPagePlain($limit, $offset, $q, $client));
	}

	public function insertOne(){
		if($this->get_request_method() != "POST") $this->response('', 406);
		$data = json_decode(file_get_contents("php://input"), true);
		if(!isset($data)) $this->responseInvalidParam();
		$column_names = array('name', 'icon', 'draft', 'brief', 'color', 'priority', 'created_at', 'last_update');
		$table_name = 'category';
		$pk = 'id';
		$resp = $this->db->post_one($data, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}
	
	public function updateOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$data = json_decode(file_get_contents("php://input"),true);
		if(!isset($data['id'])) $this->responseInvalidParam();
		$id = (int)$data['id'];
		$column_names = array('name', 'icon', 'draft', 'brief', 'color', 'priority', 'created_at', 'last_update');
		$table_name = 'category';
		$pk = 'id';
		$this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
	}

	public function deleteOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$table_name = 'category';
		$pk = 'id';
		$this->show_response($this->db->delete_one($id, $pk, $table_name));
	}

	public function getAllByProductIdPlain($product_id){
		$query = "SELECT DISTINCT c.* FROM category c WHERE c.id IN (SELECT pc.category_id FROM product_category pc WHERE pc.product_id=$product_id);";
		return $this->db->get_list($query);
	}
	
	public function getAllByProductId(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['product_id'])) $this->responseInvalidParam();
		$product_id = (int)$this->_request['product_id'];
		$this->show_response($this->getAllByProductIdPlain($product_id));
	}

	public function countByDraftPlain($i){
		$query = "SELECT COUNT(DISTINCT c.id) FROM category c WHERE c.draft=$i ";
		return $this->db->get_count($query);
	}
	
}	
?>