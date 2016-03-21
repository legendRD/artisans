<?php
namespace Artisans\Controller;
class AppArtisansController extends CommonController {
      //头像地址
      private $_headimg_upload_path = "/share/weixinImg/artisans/app/headImg/";
      private $_headimg_view_path   = "/img/weixinImg/artisans/app/headImg/";
      
      //设备地址
      private $_fun_upload_path	    = "/share/weixinImg/artisans/app/fun/";
      private $_fun_view_path	    = "/img/weixinImg/artisans/app/fun/";
      
      //支付完成之后请求地址
      private $_send_url = "http://localhost/paycenter/return_verify";
      private $_log_url  = "/share/weixinLog/artisans/app/";
      
      //日志开启状态
      private $_log_open_status = false;
      protected $info_log_url = "/share/weixinLog/artisans/app/info_log/";
      
      //app微信支付
      private $_appid		= '';
      private $_appkey		= '';
      private $_partnerkeyid	= '';
      private $_partnerkey	= '';
      
      //获取订单状态地址
      private $_getStatus 	= "http://localhost/order/list";
      
      //生成订单号地址
      private $_getTrade_url	= "http://localhost/order/addor";
      
      //支付接口
      private $_orderPay_url 	= "http://localhost/Paycenter/CreatePayInfo";
      
      //更改用户状态和XX
      private $_pay_log		= "/share/pay_log_url/webArtisans/";
      
      //长连接变为短连接
      private $_changeUrl	= "http://localhost/Qrcode/shorturl";
      
      //设备类型
      private $_fun_type	= array(1=>'手机', 2=>'平板', 3=>'笔记本', 4=>'其他');
      
      //读取配置信息的接口
      public function getConfig() {
            $this->wInfoLog('服务器配置信息，IP：'.get_ip());
            $this->wInfoLog($_REQUEST, '接收参数=>');
            $data = array();
            $config_info = M('app_config')->where(array('IsDelete'=>0))->order('ConfigId desc')->field('Codes code, Jsons content')->find();
            if($config_info) {
            	$data['code'] = $config_info['code'];
            	$json_arr     = explode(',', $config_info['content']);
            	foreach($json_arr as $value) {
            		$tmp = explode('@@', $value);
            		$data[$tmp[0]] = $tmp[1];
            	}
            	$this->returnJsonData(200, $data);
            }else{
            	$this->returnJsonData(404);
            }
      }
      
      //首页广告位
      public function getBanner() {
            
      }
      
      //给用户发送验证码
      public function sendVerifyCode() {
            
      }
      
      //查看验证码
      public function checkVerifyCode() {
            
      }
      
      /**
	 * 登陆注册功能
	 * @access	public
	 * @param	int $postData['phone'] 电话号
	 * @param	int $postData['code']  验证码
	 * @param	cid $postData['cid']   设备id
	 * @return	string
	 */
	 public function addUser() {
	       
	 }
	 
	 //uid绑定cid 接口
	 public function uidBindCid($param=null) {
	       
	 }
	 
	 //获取时间ID 接口
	 public function getTimeId($param=null) {
	       
	 }
	 
	 //查询用户信息
	 public function selectUser() {
	       
	 }
	 
	 //更新用户信息
	 public function updateUser() {
	       
	 }
	 
	 //获取产品列表
	 public function getServiceList() {
	       
	 }
	 
	 //产品下面XXX
	 public function proUserinfo() {
	       
	 }
	 
	 //城市下面XXX
	 public function cityUserinfo() {
	       
	 }
	 
	 //获取单个XXX信息
	 public function getOneInfo() {
	       
	 }
	 
	 //获取时间点XX
	 public function getUserTime($craft_id) {
	       
	 }
	 
	 public function getTimeStatus($craft_id, $date) {
	       
	 }
	 
	 //添加设备
	 public function addEquipment() {
	       
	 }
	 
	 //我的设备
	 public function myEquipment() {
	       
	 }
	 
	 //设备H5地址
	 public function viewHfive() {
	       
	 }
	 
	 //设备H5页面
	 public function appViewH5() {
	       
	 }
	 
	 //成功跳转页
	 public function success() {
	       
	 }
	 
	 //朋友圈
	 public function friendCircle() {
	       
	 }
	 
	 //产品详情页
	 public function productDetails() {
	       
	 }
	 
	 //创建订单
	 public function createOrderInfo() {
	       
	 }
	 
	 //获取支付链接
	 public function appOrderPay() {
	       
	 }
	 
	 //查询单个订单信息
	 public function selectOrderInfo() {
	       
	 }
	 
	 //查询多个订单信息
	 public function checkOrderInfo() {
	       
	 }
	 
	 //取消订单
	 public function cancleOrder() {
	       
	 }
	 
	 //XXX点评数据
	 public function checkEvaluation() {
	       
	 }
	 
	 //提交点评
	 public function createComments() {
	       
	 }
	 
	 //首单减5
	 public function reduceFiveyuan() {
	       
	 }
	 
	 //获取app版本
	 public function getAppVersion() {
	       
	 }
	 
	 //插入微信支付token
	 public function insAppPaytoken() {
	       
	 }
	 
	 //获取签名
	 public function get_sign_name() {
	       
	 }
	 
	 //日志
	 private function wInfoLog($data, $prefix='') {
	       
	 }
	 
	 public function upload_test() {
	       
	 }
	 
	 //获取订单状态
	 public function getOrderStatus($status) {
	       
	 }
	 
	 //解析XXX信息
	 public function parseUserinfo($userinfo) {
	       
	 }
	 
	 //返回数据
	 public function returnJsonData($num, $data=array(), $msg='') {
	       
	 }
	 
	 private function echoInfo($info, $parse='') {
	       
	 }
	 
	 //更新支付方式接口
	 public function updatePayWay() {
	       
	 }
	 
	 //获取产品价格
	 public function getProPrice() {
	       
	 }
}
