<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class NewsInfo extends REST{
	
	private $mysqli = NULL;
	private $db = NULL; 
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$this->show_response($this->findAllPlain());
	}

	public function findAllPlain(){
		$query="SELECT * FROM news_info ni ORDER BY ni.id DESC";
		return $this->db->get_list($query);
	}

	public function findOnePlain($id){
		$query="SELECT distinct * FROM news_info ni WHERE ni.id=$id";
		return $this->db->get_one($query);
	}

	public function findOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$this->show_response($this->findOnePlain($id));
	}

    public function allCountPlain($q, $client){
		$query = "SELECT COUNT(DISTINCT ni.id) FROM news_info ni ";
		$keywordQuery = "(ni.title REGEXP '$q' OR ni.brief_content REGEXP '$q' OR ni.full_content REGEXP '$q') ";
		if($client != 0){
			$query = $query . " WHERE ni.draft <> 1 ";
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
		$query = "SELECT ni.* FROM news_info ni ";
		$keywordQuery = "(ni.title REGEXP '$q' OR ni.brief_content REGEXP '$q' OR ni.full_content REGEXP '$q') ";
		if($client != 0){
			$query = $query . " WHERE ni.draft <> 1 ";
			if($q != "") $query = $query . "AND " . $keywordQuery ;
		} else {
			if($q != "") $query = $query . "WHERE " . $keywordQuery ;
		}
		$query = $query . "ORDER BY ni.id DESC LIMIT $limit OFFSET $offset ";
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
		$column_names = array('title', 'brief_content', 'full_content', 'image', 'draft', 'status', 'created_at', 'last_update');
		$table_name = 'news_info';
		$pk = 'id';
		
		if($data['status'] == 'FEATURED' && $data['draft'] == 0 && $this->isFeaturedExceed() == 1){
			$msg = array('status' => "failed", "msg" => "اخبار ویژه بیش از حد مجاز است", "data" => null);
			$this->show_response($msg);
		}
		$resp = $this->db->post_one($data, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}
	
	public function updateOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$data = json_decode(file_get_contents("php://input"),true);
		if(!isset($data['id'])) $this->responseInvalidParam();
		$id = (int)$data['id'];
		$column_names = array('title', 'brief_content', 'full_content', 'image', 'draft', 'status', 'created_at', 'last_update');
		$table_name = 'news_info';
		$pk = 'id';
		if($data[$table_name]['status'] == 'FEATURED' && $data[$table_name]['draft'] == 0 && $this->isFeaturedExceed() == 1){
			$msg = array('status' => "failed", "msg" => "اخبار ویژه بیش از حد مجاز است", "data" => null);
			$this->show_response($msg);
		}
        if($data[$table_name]['status'] == 'NORMAL' && $this->countFeaturedPlain() <= 1){
            $m = array('status' => "failed", "msg" => "حداقل باید دو خبر ویژه داشته باشید", "data" => null);
            $this->show_response($m);
            return;
        }
		$this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
	}

	public function deleteOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$table_name = 'news_info';
		$pk = 'id';
		$data = $this->findOnePlain($id);
        if($data['status'] == 'FEATURED' && $this->countFeaturedPlain() <= 1){
            $m = array('status' => "failed", "msg" => "حداقل باید دو خبر ویژه داشته باشید", "data" => null);
            $this->show_response($m);
            return;
        }
		$this->show_response($this->db->delete_one($id, $pk, $table_name));
	}

	public function findAllFeatured(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$query="SELECT * FROM news_info ni WHERE ni.status='FEATURED' AND ni.draft=0 ORDER BY ni.id DESC";
		return $this->db->get_list($query);
	}

	public function countByDraftPlain($i){
		$query = "SELECT COUNT(DISTINCT ni.id) FROM news_info ni WHERE ni.draft=$i ";
		return $this->db->get_count($query);
	}

	public function countFeaturedPlain(){
		$query = "SELECT COUNT(DISTINCT ni.id) FROM news_info ni WHERE ni.status='FEATURED' AND ni.draft=0 ";
		return $this->db->get_count($query);
	}
	
	public function isFeaturedExceed(){
		$key_code = 'FEATURED_NEWS';
		$query=	"SELECT COUNT(counter) AS resp from (SELECT COUNT(id) AS counter FROM news_info WHERE status = 'FEATURED' AND draft = 0) as N "
				."WHERE N.counter >= (SELECT value FROM config WHERE code = '$key_code')";
		return $this->db->get_one($query)['resp'];
	}

	public function isFeaturedNewsExceed(){
		$this->show_response_plain($this->isFeaturedExceed());
	}
}	
?>