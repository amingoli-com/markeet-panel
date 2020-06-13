<?php
require_once(realpath(dirname(__FILE__) . "/../tools/rest.php"));
require_once(realpath(dirname(__FILE__) . "/../conf.php"));

class Fcm extends REST{
	
	private $mysqli = NULL;
	private $db = NULL; 
	public $conf = NULL;
    private $FCM_TOPIC   = "/topics/ALL-DEVICE";
	
	public function __construct($db) {
		parent::__construct();
		$this->db = $db;
		$this->mysqli = $db->mysqli;
		$this->conf = new CONF(); // Create config class
    }
	
	public function findAll(){
		if($this->get_request_method() != "GET") $this->response('',406); 
		$query="SELECT DISTINCT * FROM fcm f ORDER BY f.last_update DESC";
		$this->show_response($this->db->get_list($query));
	}
	
	public function allCount(){
		if($this->get_request_method() != "GET") $this->response('',406);
		$q = "";
		if(isset($this->_request['q'])) $q = $this->_request['q'];
		if($q != ""){
			$query=	"SELECT COUNT(DISTINCT f.id) FROM fcm f "
					."WHERE device REGEXP '$q' OR serial REGEXP '$q' OR app_version REGEXP '$q' OR os_version REGEXP '$q' ";
		} else {
			$query="SELECT COUNT(DISTINCT f.id) FROM fcm f";
		}
		$this->show_response_plain($this->db->get_count($query));
	}

	public function allCountPlain(){
		$query="SELECT COUNT(DISTINCT f.id) FROM fcm f";
		return $this->db->get_count($query);
	}
	
	public function findAllByPage(){
		if($this->get_request_method() != "GET") $this->response('',406);
		if(!isset($this->_request['limit']) || !isset($this->_request['page']))$this->responseInvalidParam();
		$limit = (int)$this->_request['limit'];
		$offset = ((int)$this->_request['page']) - 1;
		$q = "";
		if(isset($this->_request['q'])) $q = $this->_request['q'];
		if($q != ""){
			$query=	"SELECT DISTINCT * FROM fcm f "
					."WHERE device REGEXP '$q' OR serial REGEXP '$q' OR app_version REGEXP '$q' OR os_version REGEXP '$q' "
					."ORDER BY f.last_update DESC LIMIT $limit OFFSET $offset";
		} else {
			$query="SELECT DISTINCT * FROM fcm f ORDER BY f.last_update DESC LIMIT $limit OFFSET $offset";
		}
		$this->show_response($this->db->get_list($query));
	}

    public function findBySerial($serial){
        $query="SELECT distinct * FROM fcm f WHERE f.serial='$serial'";
        $result = $this->db->get_one($query);
        return $result;
    }

	public function insertOne(){
		if($this->get_request_method() != "POST") $this->response('',406);
		$fcm 			= json_decode(file_get_contents("php://input"),true);

        // checking security code
        if(!isset($this->_header['Security']) || $this->_header['Security'] != $this->conf->SECURITY_CODE){
            $m = array('status' => 'failed', 'msg' => 'کد امنیتی نامعتبر است', 'data' => null);
            $this->show_response($m);
            return;
        }

		$serial  		= $fcm['serial'];
		$column_names 	= array('device', 'os_version', 'app_version', 'serial', 'regid', 'created_at', 'last_update');
		$table_name 	= 'fcm';
		$pk 			= 'id';

		$query="SELECT f.* FROM fcm f WHERE f.serial='$serial' LIMIT 1";
		$r = $this->mysqli->query($query) or die($this->mysqli->error.__LINE__);
	    $nowTime = round(microtime(true) * 1000);

		if($r->num_rows > 0){ // update
			$result = $r->fetch_assoc();
			$new_fcm['id'] = (int)$result['id'];
			$new_fcm['fcm'] = $fcm;
			$new_fcm['fcm']['created_at'] = (int)$result['created_at'];
			$new_fcm['fcm']['last_update'] = $nowTime;
			$resp = $this->db->post_update($new_fcm['id'], $new_fcm, $pk, $column_names, $table_name);
		}else{ // insert
			$fcm['created_at'] = $nowTime;
			$fcm['last_update'] = $nowTime;
			$resp = $this->db->post_one($fcm, $pk, $column_names, $table_name);
		}
		$this->show_response($resp);
	}
	
	public function getAllRegId(){
		$query="SELECT DISTINCT f.regid FROM fcm f";
		return $this->db->get_list($query);
	}

    public function processNotification() {
        if ($this->get_request_method() != "POST") $this->response('', 406);
        $body = json_decode(file_get_contents("php://input"), true);
        if (!isset($body['title']) || !isset($body['content'])) $this->responseInvalidParam();
        if ($this->conf->DEMO_VERSION) {
            $m = array('status' => "failed", "msg" => "متاسفیم این نسخه دمو میباشد", "data" => null);
            $this->show_response($m);
            return;
        }
        $reg_id = $body['reg_id'];
        $body['target'] = 'ALL';
        $to = $this->FCM_TOPIC;
        $is_all = true;

        if ($reg_id != null && $reg_id != "") {
            $to = $reg_id;
            $body['target'] = 'SINGLE';
            $is_all = false;
        }

        $push_response = $this->sendPushNotification($to, $body);
        if (!$is_all && isset($push_response['results'][0]['error'])){
            $error = $push_response['results'][0]['error'];
            $this->show_response(array('status' => 'failed', 'msg' => $error));
        }

        $this->show_response(array('status' => 'success', 'msg' => 'اعلان با موفقیت ارسال شد'));
    }

    public function sendPushNotification($to, $body) {
        // Set POST variables
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array('to' => $to, 'data' => $body);
        $api_key = $this->conf->FCM_KEY;
        $headers = array('Authorization: key=' . $api_key, 'Content-Type: application/json');
        // Open connection
        $curl = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporary
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->json($fields));

        // Execute post
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
	
}	
?>