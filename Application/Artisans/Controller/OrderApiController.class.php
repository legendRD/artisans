<?php
namespace Artisans\Controller;
class OrderApiController extends CommonController {
  
    private $_source_from	= array(100,200,300,400,500); //订单来源【可选值100微信，200web,300安卓，400ios,500其它】
    private $_plat_form_id	= array(0,1,2);//平台对应的码值  0 微信  1web  2app
    private $_ucenter_from	= array('weixin','web','android','ios'); //第三方用户注册来源，对应上面订单来源
    private $_source_from_id    = array(100=>0,200=>1,300=>2,400=>3,500=>4); //订单来源对应的码值
    private $_for_who		= array(100,200);	//预约【100为自己，200为朋友】
    private $_for_who_id        = array(100=>0,200=>1); 
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
    
    //域名
    private $string  ='';
    private $string0 ='';
    
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
		
		if($pay_process	== 2) { 
			$data['CraftsmanName']	= $enginner_name;							//客服导购，客服用户名放入CraftsmanName
		}else{
			$data['CraftsmanName']	= $craft_info['TrueName'];
		}
		
		/*$data['UserId']	= $register_info['uid'];
		$data['UserOpenid']	= $register_info['other'];*/
		$data['UserId']		= $user_id;
		$data['UserOpenid']	= $post_data['openid'];
		$data['NickName']	= $register_info['username'];
		$data['Name']		= $order_name;
		$data['CityName']	= $city_name;
		//客服导购和远程服务地址都为ip
		if($pay_process == 3 || $pay_process == 2) {
			$data['Address']	= $ip;
		}else{
			$data['Address']	= $address;
		}
		$data['Phone']		= $order_phone;
		$data['ReservationTime']= $reservation_time? $reservation_time:Null;
		$data['ForWho']		= $for_who;
		$data['ShortMessage']	= $wish;
		$data['Source']		= $source;
		$data['AddressId']	= $address_id;
		$data['Lat']		= $lat;
		$data['Lng']		= $lng;
		$data['AddressId']	= $address_id;
		$data['PayWay']		= $pay_way;
		$data['PayProcess']	= $pay_process;
		
		//产品激励Id
		if($pro_id) {
			$pro_reward_id = M('prd_product_reward')->where(" State=0 and ProductId=%d ", $pro_id)->getField('Id');
		}
		$data['ProductRewardId'] = $pro_reward_id? $pro_reward_id:0;
		
		//获取产品价格
		$price	= 200; //默认产品价格
		if($pro_id && $pay_process <> 2) {
			$product_info_data['CityId']	 = $city_id;
			$product_info_data['PlatformId'] = $plat_from_id;
			$product_info_data['ProductId']  = $pro_id;
			
			$pro_info	= A('Api')->getProductInfo($product_info_data);
			$pro_info_s     = $pro_info['data'];
			if($pro_info_s['promotion']) {
				$price = $pro_info_s['promotion']['endPrice'];
			}else{
				$price = $pro_info_s['Price'];
			}
			unset($pro_info, $product_info_data);
			if($pro_info_s && in_array($pro_info_s)) {
				if(in_array($source, array(2, 3)) {
					//app首单减5元
					$count = $artisans_model->isFirstOrder($register_info['uid'], array(2, 3));
					if($count == 0) {
						$price = ($price-5)<0? 0:($price-5);
					}
				}
			}else{
				return $this->returnJsonData($exit_type, 2003);
			}
			
			//用户是否使用卡券
			$pay_api_model = D('PayApi');
			$redbag_data   = $pay_api_model->getUserCardinfo($card_info_data);
			if($redbag_data['id']) {
				$price	= $price - $redbag_data['money'];
				$price	= $price>0? $price:0;
				$is_use_card	= true;
			}else{
				$is_use_card	= false;
			}
			
			//用户是否使用优惠券
			$is_use_coupons = false;
			if($is_use_card === false && $coupons_id) {
				$coupons_info = array(
					'source'     => 1,	      //来源 58同城
					'phone'      => $order_phone, 
					'coupons_id' => $coupons_id, 
					'pro_id'     => $pro_id
				);
				$activity_data = $pay_api_model->getUserCouponsinfo($coupons_info);
				if($activity_data['money']>0) {
					$price = $price - $activity_data['money'];
					$price = $price>0?$price:0;
					$is_use_coupons = true;
				}
			}
			
			//上门费
			$craft_param = array(
				'CraftsmanId' => $craft_id,
				'lat'	      => $lat,
				'lng'	      => $lng
			);
			
			$craft_info_s = A('Api')->getCraftsManInfo($craft_param);
			$trave_price  = ceil($craft_info_s['data']['Distance']*2);
			$trave_price  = $trave_price>0?$trave_price:0;
			unset($craft_info_s);
			
			//上门费超过80元 或者 XXXid为空【正常支付或者40公里】
			if(($trave_price>80 || empty($craft_id)) && (in_array($pay_process, array(1, 4))) {
				$trave_price = 80;
			}
			if($pay_way == 1) {
				//线下支付  需要加上门费
				$price = $price + $trave_price;
				$data['DoorFee'] = $trave_price;
			}else{
				$data['DoorFee'] = 0;
			}
			if($pay_process	== 2) {
				//客服导购,价格走其它产品表
				$shop_info = M('prd_shopinfo')->where(array('shopid'=>$pro_id, 'shop_status'=>1))->find();
				if(!$shop_info) {
					return $this->returnJsonData($exit_type, 10006);
				}
				$price = $shop_info['shop_price'] ? $shop_info['shop_price'] : 300;
			}
			$create_time = date('Y-m-d H:i:s');	//订单创建时间
			$data['CreaterTime'] = $create_time;
			$data['IsDelete']    = 0;
			$data['Price']       = $price;
			
			//微信正常支付需要判断产能是否存在
			if($pay_process == 1) {
				$yuyuetime = strtotime($reservation_time);
				if($yuyuetime<time() || $yuyue_time-time()<10800) {
					return $this->returnJsonData($exit_type, 1001);
				}
				$capacity_info 		= $artisans_model->getCapacity($craft_id, $order_data, $order_time_id);
				$no_use_num		= $capacity_info['NouseNum'];
				$capacity_id		= $capacity_info['CapacityId'];
				$data['CapacityId']	= $capacity_id;
				if(empty($no_use_num) || $no_use_num<0) {
					return $this->returnJsonData($exit_type, 1001);
				}
			}
			
			//微信端订单号自己生成，其他系统订单都由vmall提供订单
			if($source == 0) {
				$out_trade_no = $data['VmallOrderId'] = create_order_id();
			}
			
			//判断差能是否被占用
			if($pay_process == 1) {
				$time_num = M('crt_use')->where()->count();
				if($time_num > 0) {
					return $this->returnJsonData($exit_type, 1000);
				}
			}
			
			//价格为0
			if($price == 0) {
				$data['Status'] = 3;
			}
			
			//开启回滚
			$trans_model  = M();
			$trans_model->startTrans();
			$trans_status = true;
			
			if($pay_process == 1) {
				$trans_status = $artisans_model->reduceCapacity($capacity_id);	//减产能
				if($trans_status) {
					$trans_status = M('crt_use')->add(array('CapacityId'=>$capacity_id, 'UserId'=>$register_info['UserId'], 'CreaterTime'=>$create_time));
				}
			}
			
			if($trans_status) {
				$trans_status    = M('ord_orderinfo')->add($data);	//订单基本信息
				$data['OrderId'] = $trans_status;
			}
			if($trans_status) {
				$order_id 				= $trans_status;
				$order_status_info['order_id'] 		= $order_id;
				$order_status_info['state']		= $data['Status'];
				$order_status_info['create_time']	= $create_time;
				
				$trans_status = D('PayApi')->addOrderState($order_status_info);	//订单状态
				unset($order_status_info);
			}
			if($trans_status) {
				if($is_use_card) {
					$ord_pay_data['OrderId']	= $order_id;
					$ord_pay_data['PayWay']		= 1; //卡券
					$ord_pay_data['PayCode']	= $card_info_data['codeid'].','.$card_info_data['cardid'];
					$ord_pay_data['PayCash']	= $redbag_data['money'];
					$ord_pay_data['CreaterTime']	=$create_time;
					
					$trans_status = M('ord_pay')->add($ord_pay_data);	//订单支付明细【卡券信息】
					unset($ord_pay_data);
					$code_pay_way = 1;
				}elseif($is_use_coupons) {
					$ord_pay_data['OrderId']	= $order_id;
					$ord_pay_data['PayWay']		= 3; //优惠券
					$ord_pay_data['PayCode']	= $coupons_id;
					$ord_pay_data['PayCash']	= $activity_data['money'];
					$ord_pay_data['CreaterTime']	= $create_time;
					
					$trans_status	= M('ord_pay')->add($ord_pay_data);	//订单支付明细【优惠券信息】
					unset($ord_pay_data);
					$code_pay_way	= 3;
				}
			}
			if($trans_status) {
				if($pay_process	== 2) {
					$addpackage['product_id']	= $shop_info['shop_id'];
					$addpackage['product_name']	= $shop_info['shop_name'];
					$addpackage['product_price']    = $shop_info['shop_price'];
				}else{
					$addpackage['product_id']	= $pro_info_s['ProductId'];
					$addpackage['product_name']	= $pro_info_s['name'];
					$addpackage['product_price']    = $price;
				}
				$addpackage['order_id']		= $order_id;
				$addpackage['package_id']	= $package_id;
				
				$trans_status  =  $this->addPackage($addpackage);	//订单套餐明细
			}
			
			//调取vmall订单接口
			if($trans_status && $source<>0) {
				$tmp['product_name']	= $pro_info_s['name'];
				$pro_img		= $this->getImgUrl($pro_info_s['headImg_cdn'],$pro_info_s['headImg']);
				if(strpos($pro_img, $string)===false) {
					$tmp['product_pic'] = $pro_img;
				}else{
					$tmp['product_pic'] = str_replace($string, $string0, $pro_img);  //XXX头像替换
				}
				$tmp['count'] 		= 1;
				$tmp['product_price']	= $data['Price'];
				$shop['total_amount']	= $data['Price'];
				$shop['shop_id']	= $pro_id;
				$shop['address']	= $address;
				$shop['uid'] 		= $order_id;
				$shop['allnum'] 	= 1;
				$shop['good_arr'][] 	= $tmp;
				$shop['order_from'] 	= 200;
				
				$get_trade_info = post_http($this->_getTrade_url, $shop); //获取订单号接口
				$trade_info     = json_decode($get_trade_info, true);
				unset($shop);
				if($trade_info['data'] && is_array($trade_info['data'])) {
					$post_id 	= $trade_info['data']['uid'];	//记录id
					$out_trade_no   = $trade_info['data']['order_id'];
					$data['VmallOrderId']	= $out_trade_no;
					if($out_trade_no && $post_id) {
						$trans_status = M('ord_orderinfo')->where(array('OrderId'=>$post_id))->save(array('VmallOrderId'=>$out_trade_no, 'UpdateTime'=>date('Y-m-d H:i:s')));
					}
				}else{
					$trans_status	= false;
				}
			}
			
			if($trans_status) {
				$trans_model->commit();
				//线下支付 或者 支付金额为0 ： 核销卡券并给用户发送消息
				if( $data['Price'] == 0 || $pay_way==1 ) {
					$pay_success_data			= $data;
					$pay_success_data['cardid']		= $card_info_data['cardid'];
					$pay_success_data['codeid']		= $card_info_data['codeid'];
					$pay_success_data['product_name']	= $pro_info_s['name'];
					$pay_success_data['product_id']		= $pro_info_s['ProductId'];
	 			      //$pay_success_data['remote_type']	= $craft_info['remoteType'];
					$pay_success_data['craft_phone']	= $craft_info['Phone'];
					$pay_success_data['coupons_id']		= $coupons_id;
					$pay_success_data['code_pay_way']	= $code_pay_way;
					
					$this->_paySuccessDeal($pay_success_data);
					unset($pay_success_data);
				}
				$data['product_name']	= $addpackage['product_name'];
				unset($addpackage);
				
				$new_data = D('Comm')->lowercaseKey($data);
				return $this->returnJsonData($exit_type,200,$new_data);
			}else{
				$trans_model->rollback();
				return $this->returnJsonData($exit_type,2007,array(),'创建订单失败');
			}
	}
	
	//核销卡券，发送消息
	private function _paySuccessDeal($param){
		$pay_api_model	= D('PayApi');
		$pay_api_model->cleanKQ($param); //核销卡券
		$pay_api_model->sendMsg($param); //发送消息
	}
	
	/**
	 * 获取vmall支付链接
	 * @access	public
	 * @param	string $param['vmall_order_id'] 订单号
	 * @param	string $param['payment_type']	web版微信100，网银200，网页淘宝300，手机淘宝400，app版微信500
	 * @param	string $param['success_url']	支付成功跳转的链接地址
	 * @return	string
	 */
	public function getPayOrderUrl($param=null) {
		if(isset($param)){
			$post_data	= $param;
			$exit_type	= 'array';
		}else{
			$post_data	= I('request.');
			$exit_type	= 'json';
		}
		$vmall_order_id	= $post_data['vmall_order_id'];
		$payment_type	= $post_data['payment_type'];
		$success_url	= $post_data['success_url'];
		
		if($payment_type == 500){
			$data['payment_type']		= 100;
			$data['appid']			= $this->_appid;
			$data['appkey']			= $this->_appkey;
			$data['partnerkeyid']		= $this->_partnerkeyid;
			$data['partnerkey']		= $this->_partnerkey;
		}else{
			$data['payment_type']		= $payment_type;
		}
		
		if(!in_array($data['payment_type'], $this->_payment_type)) {
			return $this->returnJsonData($exit_type, 10005);
		}
		if(!($vmall_order_id && $payment_type)) {
			return $this->returnJsonData($exit_type, 300);
		}
		$artisans_model = D('Api');
		$order_info     = M('ord_orderinfo')->where(array('VmallOrderId'=>$vmall_order_id))->find();
		$order_id	= $order_info['OrderId'];
		if(!$order_id) {
			return $this->returnJsonData($exit_type, 1007);		//订单已失效
		}
		$data['order_id']   = $vmall_order_id;
		$data['uid']	    = $order_id;
		$data['return_url'] = $success_url	//支付成功跳转地址 
		$data['ip']	    = get_ip();
		
		$payData            = send_curl($this->_orderPay_url, $data);
		$parsePay           = json_decode($payData, true);
		unset($data);
		
		$hash = array();
		if($parsePay['data']) {
			if(is_array($parsePay['data'])) {
				$requestUrl  = joinUrl($parsePay['data']['url'], $parsePay['data']['data']);
				$hash['url'] = $requestUrl;
				if($data['payment_type'] == 100) {
					$getShortUrl = send_curl($this->_changeUrl, array('url'=>$requestUrl));
					$getShort    = json_decode($getShortUrl, true);
					$hash['url'] = $getShort['data'] ? $getShort['data'] : $requestUrl;
				}
			}else{
				return $this->returnJsonData($exit_type, 2007, array(), $parsePay['data']);
			}
		}
		return $this->returnJsonData($exit_type, 200, $hash);
	}
	
	//更新订单状态【支付成功】,发消息
	public function update_order() {
		$post_data = I('request.');
		$out_order_no	= $post_data['out_trade_no']; //订单号
		$log_url	= $this->_update_order_status.'/product_'.date('Ymd').'.log';
		wlog($log_url, "----------start----------");
		wlog($log_url, "接收参数：".serialize($post_data));
		if($out_order_no) {
			$where = array(
				'VmallOrderId'=>$out_order_no,
				'status'=>0
			);
			$rows  = M('ord_orderinfo')->where($where)->find();
		}
	}
}
