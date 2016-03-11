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
    private $_getTrade_url	= "http://localhost/order/addor"; //生成订单号地址
	  private $_orderPay_url	= "http://localhost/Paycenter/CreatePayInfo"; //支付接口
	  private $_getStatus		= "http://localhost/order/list"; //获取订单状态地址
	  private $_changeUrl = "http://localhost/Qrcode/shorturl"; //长连接变为短连接
}
