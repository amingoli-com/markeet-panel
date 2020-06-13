<?php
require_once("tools/rest.php");
require_once("tools/db.php");
require_once("tools/mail.php");
require_once("table/Product.php");
require_once("table/ProductCategory.php");
require_once("table/ProductOrder.php");
require_once("table/ProductOrderDetail.php");
require_once("table/ProductImage.php");
require_once("table/Category.php");
require_once("table/User.php");
require_once("table/Fcm.php");
require_once("table/NewsInfo.php");
require_once("table/AppVersion.php");
require_once("table/Currency.php");
require_once("table/Config.php");
require_once("client.php");
require_once("dashboard.php");

class API {
	
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
	private $app_version 			= NULL;
	private $client 				= NULL;
	private $dashboard 				= NULL;
    private $mail		 			= NULL;

	public function __construct(){
		$this->db = new DB();
		
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
        $this->client =  new CLIENT($this->db);
		$this->dashboard = new DASHBOARD($this->db);
        $this->mail = new Mail($this->db);
	}

	/*
	 * ALL API Related android client ----------------------------------------------------------------------------------
	 */
	private function info(){
		$this->client->info();
	}
	private function listFeaturedNews(){
		$this->client->findAllFeaturedNewsInfo();
	}
	private function listProduct(){
		$this->client->findAllProduct();
	}
    private function getProductDetails(){
        $this->client->findProductDetails();
    }
    private function listCategory(){
        $this->client->findAllCategory();
    }
    private function listNews(){
        $this->client->findAllNewsInfo();
    }
	private function getNewsDetails(){
        $this->client->findNewsDetails();
    }
    private function submitProductOrder(){
        $this->client->submitProductOrder();
    }

	/*
 	* ALL API Related DASHBOARD page -----------------------------------------------------------------------------------
 	*/
	private function getDashboardProduct(){
		$this->dashboard->findDashboardProductData();
	}
	private function getDashboardOthers(){
		$this->dashboard->findDashboardOthersData();
	}

	/*
	 * TABLE PRODUCT TRANSACTION ---------------------------------------------------------------------------------------
	 */
	private function getOneProduct(){
		$this->product->findOne();
	} 
	private function getAllProduct(){
		$this->product->findAll();
	}
	private function getAllProductByPage(){
		$this->product->findAllByPage();
	}
	private function getAllProductCount(){
		$this->product->allCount();
	}
	private function insertOneProduct(){
		$this->user->checkAuthorization();
		$this->product->insertOne();
	}
	private function updateOneProduct(){
		$this->user->checkAuthorization();
		$this->product->updateOne();
	}
	private function deleteOneProduct(){
		$this->user->checkAuthorization();
		$this->product->deleteOne();
	}
	
	/*
	 * TABLE PRODUCT_CATEGORY TRANSACTION ------------------------------------------------------------------------------
	 */
	private function getAllProductCategory(){
		$this->product_category->findAll();
	}
	private function insertAllProductCategory(){
		$this->user->checkAuthorization();
		$this->product_category->deleteInsertAll();
	}
	
	/*
	 * TABLE PRODUCT_ORDER TRANSACTION ---------------------------------------------------------------------------------
	 */
	private function getOneProductOrder(){
        $this->user->checkAuthorization();
		$this->product_order->findOne();
	}
	private function getAllProductOrder(){
		$this->product_order->findAll();
	}
	private function getAllProductOrderByPage(){
        $this->user->checkAuthorization();
		$this->product_order->findAllByPage();
	}
	private function getAllProductOrderCount(){
		$this->product_order->allCount();
	}
    private function insertOneProductOrder(){
        $this->user->checkAuthorization();
        $this->product_order->insertOne();
    }
    private function updateOneProductOrder(){
        $this->user->checkAuthorization();
        $this->product_order->updateOne();
    }
    private function deleteOneProductOrder(){
        $this->user->checkAuthorization();
        $this->product_order->deleteOne();
    }
    private function processProductOrder(){
        $this->user->checkAuthorization();
        $this->product_order->processOrder();
    }
	
	/*
	 * TABLE PRODUCT_ORDER_DETAIL TRANSACTION --------------------------------------------------------------------------
	 */
	private function getAllProductOrderDetail(){
		$this->product_order_detail->findAll();
	}
	private function getAllProductOrderDetailByOrderId(){
		$this->product_order_detail->findAllByOrderId();
	}
	private function insertAllProductOrderDetail(){
		$this->user->checkAuthorization();
		$this->product_order_detail->deleteInsertAll();
	}
	
	/*
	 * TABLE PRODUCT_IMAGE TRANSACTION ---------------------------------------------------------------------------------
	 */
	private function getAllProductImageByProductId(){
		$this->product_image->findAllByProductId();
	}
	private function getAllProductImage(){
		$this->product_image->findAll();
	}
	private function insertAllProductImage(){
		$this->user->checkAuthorization();
		$this->product_image->insertAll();
	}
	private function deleteProductImageByName(){
		$this->user->checkAuthorization();
		$this->product_image->delete();
	}
	
	/*
	 * TABLE CATEGORY TRANSACTION --------------------------------------------------------------------------------------
	 */
	private function getOneCategory(){
		$this->category->findOne();
	} 
	private function getAllCategory(){
		$this->category->findAll();
	}
	private function getAllCategoryByPage(){
		$this->category->findAllByPage();
	}
	private function getAllCategoryCount(){
		$this->category->allCount();
	}
	private function getAllCategoryByProductId(){
		$this->category->getAllByProductId();
	}
	private function insertOneCategory(){
		$this->user->checkAuthorization();
		$this->category->insertOne();
	}
	private function updateOneCategory(){
		$this->user->checkAuthorization();
		$this->category->updateOne();
	}
	private function deleteOneCategory(){
		$this->user->checkAuthorization();
		$this->category->deleteOne();
	}
	
	/*
	 * TABLE USERS TRANSACTION -----------------------------------------------------------------------------------------
	 */
	private function login(){
		$this->user->processLogin();
	}

	private function getOneUser(){
		$this->user->findOne();
	}

	private function updateOneUser(){
		$this->user->checkAuthorization();
		$this->user->updateOne();
	}

	private function insertOneUser(){
		$this->user->checkAuthorization();
		$this->user->insertOne();
	}

	/*
	 * TABLE FCM TRANSACTION -------------------------------------------------------------------------------------------
	 */
	private function getAllFcm(){
		$this->fcm->findAll();
	}
	private function getAllFcmByPage(){
		$this->fcm->findAllByPage();
	}
	private function getAllFcmCount(){
		$this->fcm->allCount();
	}
	private function insertOneFcm(){
		$this->fcm->insertOne();
	}
	private function sendNotif() {
		$this->user->checkAuthorization();
		$this->fcm->processNotification();
	}
	
	/*
	 * TABLE NEWS_INFO TRANSACTION -------------------------------------------------------------------------------------
	 */
	private function getOneNewsInfo(){
		$this->news_info->findOne();
	} 
	private function getAllNewsInfo(){
		$this->news_info->findAll();
	}
	private function getAllNewsInfoByPage(){
		$this->news_info->findAllByPage();
	}
	private function getAllNewsInfoCount(){
		$this->news_info->allCount();
	}
	private function insertOneNewsInfo(){
		$this->user->checkAuthorization();
		$this->news_info->insertOne();
	}
	private function updateOneNewsInfo(){
		$this->user->checkAuthorization();
		$this->news_info->updateOne();
	}
	private function deleteOneNewsInfo(){
		$this->user->checkAuthorization();
		$this->news_info->deleteOne();
	}
	private function isFeaturedNewsExceed(){
		$this->news_info->isFeaturedNewsExceed();
	}
	
	/*
	 * TABLE CURRENCY TRANSACTION --------------------------------------------------------------------------------------
	 */
	private function getAllCurrency(){
		$this->currency->findAll();
	}
	
	/*
	 * TABLE APP_VERSION TRANSACTION -----------------------------------------------------------------------------------
	 */
	private function getOneAppVersion(){
		$this->app_version->findOne();
	} 
	private function getAllAppVersionByPage(){
		$this->app_version->findAllByPage();
	}
	private function getAllAppVersionCount(){
		$this->app_version->allCount();
	}
	private function insertOneAppVersion(){
		$this->user->checkAuthorization();
		$this->app_version->insertOne();
	}
	private function updateOneAppVersion(){
		$this->user->checkAuthorization();
		$this->app_version->updateOne();
	}
	private function deleteOneAppVersion(){
		$this->user->checkAuthorization();
		$this->app_version->deleteOne();
	}

	/*
	 * TABLE CONFIG TRANSACTION ----------------------------------------------------------------------------------------
	 */
	private function getAllConfig(){
        $this->user->checkAuthorization();
		$this->config->findAll();
	}
	private function updateAllConfig(){
		$this->user->checkAuthorization();
		$this->config->updateAll();
	}

	/*
	 * Email sender trigger after submit order
	 */
    private function sendEmail(){
        $this->user->checkAuthorization();
        $this->mail->restEmail();
    }

    /*
     * DATABASE TRANSACTION --------------------------------------------------------------------------------------------
     */
	public function checkResponse(){
		$this->db->checkResponse_Impl();
	}
	 
	/* Dynmically call the method based on the query string 
	 * Handling direct path to function
	 */
	public function processApi(){
		if(isset($_REQUEST['x']) && $_REQUEST['x']!=""){
			$func = strtolower(trim(str_replace("/","", $_REQUEST['x'])));
			if((int)method_exists($this,$func) > 0) {
				$this->$func();
			} else {
				echo 'processApi - method not exist';
				exit;
			}
		} else {
			echo 'processApi - method not exist';
			exit;
		}
	}
	
}

// Initiiate Library

$api = new API;
$api->processApi();
?>
