<?php
namespace Artisans\Controller;
class OrderApiController extends CommonController {
  
    private $_source_from	= array(100,200,300,400,500); //订单来源【可选值100微信，200web,300安卓，400ios,500其它】
    private $_plat_form_id	= array(0,1,2);//平台对应的码值  0 微信  1web  2app
    private $_ucenter_from	= array('weixin','web','android','ios'); //第三方用户注册来源，对应上面订单来源
    private $_source_from_id= array(100=>0,200=>1,300=>2,400=>3,500=>4); //订单来源对应的码值
    private $_for_who		= array(100,200);	//预约【100为自己，200为朋友】
    private $_for_who_id	= array(100=>0,200=>1);
    private $_payment_type	= array(100,200,300,400); //微信100，网银200，网页淘宝300，手机淘宝400 
    
    //订单接口
    private $_getTrade_url	= "http://localhost/order/addor"; 	      //生成订单号地址
    private $_orderPay_url	= "http://localhost/Paycenter/CreatePayInfo"; //支付接口
    private $_getStatus		= "http://localhost/order/list"; 	      //获取订单状态地址
    private $_changeUrl 	= "http://localhost/Qrcode/shorturl"; 	      //长连接变为短连接
    
    //app微信支付
    private $_appid		= '';
    private $_appkey		= '';
    private $_partnerkeyid	= '';
    private $_partnerkey	= '';
    
    //日志
    private $_update_order_status	= "/share/weixinLog/artisans/pay_order/update_order";	//订单日志
    private $_pay_log			= '/share/pay_log_url/webArtisans/'; 			//vmall请求更新订单状态
    
    private $_card_type		= array(0=>'使用抵扣',1=>'使用卡券',2=>'使用红包');
    private $_pay_way_type	= array(1=>'线下支付',2=>'微信系统微信支付',3=>'vmall微信支付',4=>'vmall支付宝支付');
    private $_pay_way		= array(1,2,3,4);
    private $_pay_process	= array(1,2,3,4);     //支付流程：1正常支付，2客服引导，3客服专家在线，4.距离大于40公里支付
    private $_update_type	= array(100,200,300); //100取消订单
    private $_craft_step	= array(1,2,3,4,5,6); //XXX服务流程
    private $_scrypt_pwd	= '';
    
    public function _initialize() {
	   $access_token	= I('request.access_token');
	   $this->_access_token	= D("Token")->checkAccessToken($access_token);	//检测token
	   //$this->_access_token = true;//测试
    }
    
    //获取token
    public function getCrtAuth() {
    	$post_date   = I('request.');
    	$source_from = $post_data['source_from'];
    	if(empty($source_from)) {
    		$this->returnJsonData(300);
    	}
    	$access_token = '';
    	if($source_from == 'crt_api') {
    		$access_token = D("Token")->apiAccessToken();
    	}
    	$data['access_token'] = $access_token;
    	$this->returnJsonData(200, $data);
    }
    
    	//单个订单信息
	public function getOneOrder($param=null){
		if(isset($param)){
			$post_data	= $param;
			$exit_type	= 'array';
		}else{
			$post_data	= I('request.');
			$exit_type	= 'json';
		}
		$order_id	= $post_data['order_id'];
		if(!$order_id) {
			return $this->returnJsonData($exit_type,300);
		}
		if(!$this->_access_token) {
			return $this->returnJsonData($exit_type,10000);
		}
		$where = array(
				'OrderId'=>$order_id,
		);
		if($post_data['phone']) {
			$where['Phone'] = $post_data['phone'];
		}
		$order_info	= M('ord_orderinfo')->where($where)->find();
		$data	= array();
		if($order_info['OrderId']) {
			$artisans_model		= D('Artisans');
			$data['order_id']	= (int)$order_info['OrderId'];
			$data['vmall_id']	= (string)$order_info['VmallOrderId'];
			$data['name']		= (string)$order_info['Name'];
			$data['status']		= (int)$this->getOrderStatus($order_info['Status']);
			$data['phone']		= (string)$order_info['Phone'];
			$data['service_date']	= (string)$order_info['ReservationTime'];
			$data['address']	= (string)$order_info['Address'];
			if($order_info['PayWay'] == 1 && !$order_info['CraftsmanId']){
				$data['craft_name']	= 'XXXXX';
			}else{
				$data['craft_name']	= (string)$order_info['CraftsmanName'];
			}
			$data['craft_id']	= (int)$order_info['CraftsmanId'];
			$shop_info	= $artisans_model->getOrderShopInfo($order_info['OrderId']);
			$data['pro_name']	= (string)$shop_info['shop_name'];
			$data['pro_id']	= (int)$shop_info['shop_id'];
			$data['price']	= (string)$order_info['Price'];
			$data['openid']	= (string)$order_info['UserOpenid'];
			$data['user_id']= $order_info['UserId'];
			$data['pay_way']= $order_info['PayWay'];
			$data['pay_process']= $order_info['PayProcess'];
			$craft_info	= M('crt_craftsmaninfo')->where(array('CraftsmanId'=>$order_info['CraftsmanId']))->find();
			$data['craft_username']	= (string)$craft_info['UserName'];
			$data['craft_photo']	= $craft_info['HeadImgUrl']? I('server.HTTP_HOST').$craft_info['HeadImgUrl']:'';
			$data['comments']	= array();
			if($order_info['Status'] == 7) {
				//订单评论
				$comment_info	= M('prd_evaluation')->where(array('OrderId'=>$order_info['OrderId']))->select();
				foreach($comment_info as $value){
					$tmp['content']= (string)$value['Comments'];
					$tmp['stars']	= (int)$value['StarNums'];
					$tmp['label']	= (string)$value['Label'];
					$tmp['create_time']	= (string)$value['CreaterTime'];
					$tmp['source_from']	= (int)$value['Source'];
					$data['comments'][]	= $tmp;
				}
			}
		}
		return $this->returnJsonData($exit_type,200,$data);
	}
	
	//多个订单信息
	public function getMoreOrder($param=null) {
		if(isset($param)) {
			$post_data	= $param;
			$exit_type	= 'array';
		}else {
			$post_data	= I('request.');
			$exit_type	= 'json';
		}
		$user_id	= $post_data['user_id'];
		$current_page	= $post_data['current_page']?	(int)$post_data['current_page']:1;
		$page_size	= $post_data['page_size']?		(int)$post_data['page_size']:10;
		if(!($user_id && $current_page && $page_size)) {
			return $this->returnJsonData($exit_type,300);
		}
		if(!$this->_access_token) {
			return $this->returnJsonData($exit_type,10000);
		}
		$where = array(
				'UserId'=>$user_id,
		);
		$total_size	= M('ord_orderinfo')->where($where)->count();
		$total_page	= ceil($total_size/$page_size);
		$total_page	= $total_page>0? $total_page:1;
		$data	= array();
		if($total_size>0) {
			$order_info	= M('ord_orderinfo')->where($where)->order('CreaterTime desc ')->limit(($current_page-1)*$page_size,$page_size)->select();
			foreach($order_info as $value) {
				$tmp	= array();
				$tmp['order_id']	= (int)$value['OrderId'];
				$tmp['vmall_id']	= (string)$value['VmallOrderId'];
				$tmp['name']		= (string)$value['Name'];
				$tmp['status']		= (int)$this->getOrderStatus($value['Status']);
				$tmp['phone']		= (string)$value['Phone'];
				$tmp['service_date']= (string)$value['ReservationTime'];
				$tmp['address']		= (string)$value['Address'];
				$tmp['craft_name']	= (string)$value['CraftsmanName'];
				$tmp['craft_id']	= (int)$value['CraftsmanId'];
				$shop_info			= $this->getOrderShopInfo($value['OrderId']);
				$tmp['pro_name']	= (string)$shop_info['shop_name'];
				$tmp['pro_id']		= (int)$shop_info['shop_id'];
				$tmp['price']		= (string)$value['Price'];
				$tmp['pay_way']		= (string)$value['PayWay'];
				$data[]	= $tmp;
			}
		}
		return $this->returnJsonData($exit_type,200,$data);
	}
	
	//创建订单
	public function createOrder($param=null) {
		if(isset($param)) {
			$post_data = $param;
			$exit_type = 'array';
		}else{
			$post_data = I('request.');
			$exit_type = 'json';
		}
		$user_id 	= $post_data['user_id'];
		$craft_id	= $post_data['craft_id'];
		$pro_id		= $post_data['pro_id'];
		$order_name	= $post_data['name'];
		$order_phone	= $post_data['phone'];
		$address	= $post_data['address'];
		$city_id	= $post_data['city_id'];
		$source		= $post_data['source_from']; 	 //订单来源【可选值100微信，200web,300安卓，400ios】
		$address_id	= $post_data['address_id'];	 //用户地址Id
		$for_who	= $post_data['for_who'];	 //为谁预约【100为自己，200为朋友】
		$wish		= $post_data['wish'];
		$lat		= $post_data['lat'];
		$lng		= $post_data['lng'];
		
		$time_data['order_date']    = $order_date    = $post_data['order_date'];
		$time_data['order_time_id'] = $order_time_id = $post_data['order_time_id'];
		
		$card_info_data['codeid'] = $post_data['order_codeid'];	//卡券
		$card_info_data['cardid'] = $post_data['order_cardid'];
		$card_info_data['openid'] = $post_data['openid'];
		
		$pay_way	= $post_data['pay_way'];	//array(1=>'线下支付',2=>'微信系统微信支付',3=>'vmall微信支付',4=>'vmall支付宝支付');
		$package_id	= $post_data['package_id']; 	//套餐id
		
		$pay_process	= $post_data['pay_process'];	//支付流程：1正常支付，2客服引导，3客服专家在线，4.距离大于40公里支付
		$ip		= $post_data['ip'];		//客服专家在线,订单表中address存用户ip
		$enginner_name	= $post_data['enginner_name']; //客服用户名
		
		$data['IndexSource'] = $post_data['index_source']; //首页来源
		
		$coupons_id	= $post_data['coupons_id'];
		
		$artisans_model = D('Api');
		$reservation_time = $artisans_model->getOrderDate($time_data);
		
		if(!($user_id && $for_who && $city_id && $source && $pay_process && $pay_way)) {
			return $this->returnJsonData($exit_type, 300);
		}
		if(!in_array($source, $this->_source_from)) {
			return $this->returnJsonData($exit_type,2001);
		}
		if(!in_array($for_who, $this->_for_who)) {
			return $this->returnJsonData($exit_type, 2002);
		}
		if($order_phone $$ !check_phone($order_phone)) {
			return $this->returnJsonData($exit_type, 1003);
		}
		if(!in_array($pay_process, $this->_pay_process)) {
			return $this->returnJsonData($exit_type, 10004);
		}
		
		$source  = $this->_source_from_id[$source];
		$for_who = $this->_for_who_id[$for_who];
		
		//平台id
		if($source == 3) {
			$plat_from_id = 2;
		}else{
			$plat_from_id = $source;
		}
		
		$city_info = $artisans_model->getCityInfo($city_id);
		$city_name = $city_info['CityName'];
		
		if($craft_id) {
			$craft_info = M('crt_craftsmaninfo')->where(" CraftsmanId=%d", $craft_id)->find();		//XXX信息
		}
		$register_info = $artisans_model->getUserInfo($user_id);						//终端用户信息
		/*if(empty($register_info['uid'])) {
			return $this->returnJsonData($exit_type,10003);
		}*/
		
		$data['Status']			= 0;
		$data['CraftsmanId']		= $craft_id;
		$data['CraftsmanOpenid']	= $craft_info['Openid'];
		
	}
	
	//核销卡券，发送消息
	private function _paySuccessDeal($param){
		$pay_api_model	= D('PayApi');
		$pay_api_model->cleanKQ($param); //核销卡券
		$pay_api_model->sendMsg($param); //发送消息
	}
}
