<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class User extends REST{
 
	private $mysqli = NULL;
	private $db = NULL;
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
		$this->conf = new CONF(); // Create conf class
    }
	
	// security for filter manipulate data		
	public function checkAuthorization(){
		$resp = array("status" => 'Failed', "msg" => 'Unauthorized' );
		if(isset($this->_header['Token']) && !empty($this->_header['Token'])){
			$token = $this->_header['Token'];
			$query = "SELECT id FROM user WHERE password='$token' ";
			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
			if($r->num_rows < 1) {
				$this->show_response($resp);
			}
		} else {
			$this->show_response($resp);
		}
	}		

	public function processLogin(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$customer = json_decode(file_get_contents("php://input"),true);
		$username = $customer["username"];
		$password = $customer["password"];
		if(!empty($username) and !empty($password)){ // empty checker
			$query="SELECT id, name, username, email, password FROM user WHERE password = '".md5($password)."' AND username = '$username' LIMIT 1";
			$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
			if($r->num_rows > 0) {
				$result = $r->fetch_assoc();
			    $resp = array('status' => "success", "user" => $result);
				$this->show_response($resp);
			}
			$error = array('status' => "failed", "msg" => "Username or Password not found");
			$this->show_response($error);
		}
		$error = array('status' => "failed", "msg" => "Invalid username or Password");
		$this->show_response($error);
	}

	public function findOne(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$id = (int)$this->_request['id'];
		$query="SELECT id, name, username, email FROM user WHERE id=$id";
		$this->show_response($this->db->get_one($query));
	}

    public function findOneToken(){
        $query="SELECT password FROM user LIMIT 1";
        return $this->db->get_one($query)['password'];
    }

	public function updateOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		if($this->conf->DEMO_VERSION){
			$m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
			$this->show_response($m);
		}
		$user = json_decode(file_get_contents("php://input"),true);
		if(!isset($user['id'])) $this->responseInvalidParam();
		$id = (int)$user['id'];
		$password = $user['user']['password'];
		if($password == '*****'){
			$column_names = array('id', 'name', 'username', 'email');
		}else{
			$user['user']['password'] = md5($password);
			$column_names = array('id', 'name', 'username', 'email', 'password');
		}
		$table_name = 'user';
		$pk = 'id';
		$resp = $this->db->post_update($id, $user, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}

	public function insertOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		if($this->conf->DEMO_VERSION){
			$m = array('status' => "failed", "msg" => "Ops, this is demo version", "data" => null);
			$this->show_response($m);
		}
		$user = json_decode(file_get_contents("php://input"),true);
		$user['password'] = md5($user['password']);
		$column_names = array('name', 'username', 'email', 'password');
		$table_name = 'user';
		$pk = 'id';
		$resp = $this->db->post_one($user, $pk, $column_names, $table_name);
		$this->show_response($resp);
	}	
	
}	
?>