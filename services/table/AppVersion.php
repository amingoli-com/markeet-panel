<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));

class AppVersion extends REST{
	
	private $mysqli = NULL;
	private $db = NULL;
    public $conf = NULL;

	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
        $this->conf = new CONF(); // Create config class
    }

	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$this->show_response($this->findAllPlain());
	}

	public function findAllPlain(){
		$query="SELECT * FROM app_version a ORDER BY a.id DESC";
		return $this->db->get_list($query);
	}

	public function findOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
		$id = (int)$this->_request['id'];
		$resp = $this->findOnePlain($id);
		$this->show_response($resp);
	}

	public function findOnePlain($id){
		$query="SELECT * FROM app_version a WHERE a.id=$id LIMIT 1";
		return $this->db->get_one($query);
	}
	
	public function allCount(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$q = "";
		if(isset($this->_request['q'])) $q = $this->_request['q'];
		if($q != ""){
			$query=	"SELECT COUNT(DISTINCT a.id) FROM app_version a "
					."WHERE version_code REGEXP '$q' OR version_name REGEXP '$q' ";
		} else{
			$query="SELECT COUNT(DISTINCT a.id) FROM app_version a";
		}
		$this->show_response_plain($this->db->get_count($query));
	}
	
	public function findAllByPage(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['limit']) || !isset($this->_request['page']))$this->responseInvalidParam();
		$limit = (int)$this->_request['limit'];
		$offset = ((int)$this->_request['page']) - 1;
		$q = "";
		if(isset($this->_request['q'])) $q = $this->_request['q'];
		if($q != ""){
			$query=	"SELECT a.* FROM app_version a "
					."WHERE version_code REGEXP '$q' OR version_name REGEXP '$q' "
					."ORDER BY a.id DESC LIMIT $limit OFFSET $offset";
		} else {
			$query= "SELECT a.* FROM app_version a ORDER BY a.id DESC LIMIT $limit OFFSET $offset";
		}
		$this->show_response($this->db->get_list($query));
	}

	public function insertOne(){
		if($this->get_request_method() != "POST") $this->response('', 406);
		$data = json_decode(file_get_contents("php://input"), true);
		if(!isset($data)) $this->responseInvalidParam();
        if($this->conf->DEMO_VERSION){
            $m = array('status' => "failed", "msg" => "متاسفیم این نسخه دمو است", "data" => null);
            $this->show_response($m);
            return;
        }
		$column_names = array('version_code', 'version_name', 'active', 'created_at', 'last_update');
		$table_name = 'app_version';
		$pk = 'id';
		$resp = $this->db->post_one($data, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}
	
	public function updateOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$data = json_decode(file_get_contents("php://input"),true);
		if(!isset($data['id'])) $this->responseInvalidParam();
        if($this->conf->DEMO_VERSION){
            $m = array('status' => "failed", "msg" => "متاسفیم این نسخه دمو است", "data" => null);
            $this->show_response($m);
            return;
        }
		$id = (int)$data['id'];
		$column_names = array('version_code', 'version_name', 'active', 'created_at', 'last_update');
		$table_name = 'app_version';
		$pk = 'id';
		if($data[$table_name]['active'] == 0 && $this->countActiveVersion() <= 1){
			$m = array('status' => "failed", "msg" => "باید حداقل یک نسخه فعال از اپ داشته باشید!", "data" => null);
			$this->show_response($m);
			return;
		}
		$this->show_response($this->db->post_update($id, $data, $pk, $column_names, $table_name));
	}

	public function deleteOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['id'])) $this->responseInvalidParam();
        if($this->conf->DEMO_VERSION){
            $m = array('status' => "failed", "msg" => "متاسفیم این نسخه دمو است", "data" => null);
            $this->show_response($m);
            return;
        }
		$id = (int)$this->_request['id'];
		$table_name = 'app_version';
		$pk = 'id';
		$data = $this->findOnePlain($id);
        if($data['active'] == 1 && $this->countActiveVersion() <= 1){
            $m = array('status' => "failed", "msg" => "باید حداقل یک نسخه فعال از اپ داشته باشید!", "data" => null);
            $this->show_response($m);
            return;
        }
		$this->show_response($this->db->delete_one($id, $pk, $table_name));
	}

	public function countActiveVersion(){
		$query="SELECT COUNT(DISTINCT a.id) FROM app_version a WHERE a.active = 1 ;";
		return $this->db->get_count($query);
	}

	public function countInactiveVersion(){
		$query="SELECT COUNT(DISTINCT a.id) FROM app_version a WHERE a.active = 0 ;";
		return $this->db->get_count($query);
	}
	
}	
?>