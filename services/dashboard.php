<?php
require_once(realpath(dirname(__FILE__) . "/tools/rest.php"));

/*
 * This class handle all data display at dashboard
 */
class DASHBOARD extends REST{

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
    private $app_version 			= NULL;

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
    }

    public function findDashboardProductData(){
        $order = array('waiting' => 0, 'processed' => 0, 'total' => 0);
        $product = array('published' => 0, 'draft' => 0, 'ready_stock' => 0, 'out_of_stock' => 0, 'suspend' => 0 );
        $category = array('published' => 0, 'draft' => 0);

        $order['waiting'] = $this->product_order->countByStatusPlain('WAITING');
        $order['processed'] = $this->product_order->countByStatusPlain('PROCESSED');
        $order['total'] = $order['waiting'] + $order['processed'];

        $product['published'] = $this->product->countByDraftPlain(0);
        $product['draft'] = $this->product->countByDraftPlain(1);
        $product['ready_stock'] = $this->product->countByStatusPlain('READY STOCK');
        $product['out_of_stock'] = $this->product->countByStatusPlain('OUT OF STOCK');
        $product['suspend'] = $this->product->countByStatusPlain('SUSPEND');

        $category['published'] = $this->category->countByDraftPlain(0);
        $category['draft'] = $this->category->countByDraftPlain(1);

        $data = array('order' => $order, 'product' => $product, 'category' => $category);
        $this->show_response($data);
    }

    public function findDashboardOthersData(){
        $news = array('featured' => 0, 'published' => 0, 'draft' => 0);
        $app = array('active' => 0, 'inactive' => 0);
        $setting = array('currency' => "", 'tax' => 0, 'featured_news' => 0);
        $notification = array('users' => 0);

        $setting_result = $this->config->findAllPlain();

        $news['featured'] = $this->news_info->countFeaturedPlain();
        $news['published'] = $this->news_info->countByDraftPlain(0);
        $news['draft'] = $this->news_info->countByDraftPlain(1);

        $app['inactive'] = $this->app_version->countInactiveVersion();
        $app['active'] = $this->app_version->countActiveVersion();

        $setting['currency'] = $this->getValue($setting_result, 'CURRENCY');
        $setting['tax'] = $this->getValue($setting_result, 'TAX');
        $setting['featured_news'] = $this->getValue($setting_result, 'FEATURED_NEWS');

        $notification['users'] = $this->fcm->allCountPlain();

        $data = array('news' => $news, 'app' => $app, 'setting' => $setting, 'notification' => $notification);
        $this->show_response($data);

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