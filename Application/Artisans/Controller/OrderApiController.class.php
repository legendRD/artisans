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
}
