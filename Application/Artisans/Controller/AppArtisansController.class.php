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
            $postData = I('request.');
            $this->wInfoLog('广告位，IP：'.get_ip());
            $this->wInfoLog($postData, '接收参数=>');
            
            $source = (int)$postData['type'];	//平台类型。1 微信，2 安卓， 3 IOS
            if(empty($source)) {
            	$this->returnJsonData(300);
            }
            
            $comm_model = D('Comm');
            $getbannerInfo = $comm_model->getbanner($source);
            if($getbannerInfo && is_array($getbannerInfo)) {
            	foreach($getbannerInfo as $val) {
            		$hash['title']    = (string)$val['Title'];
            		$hash['imgUrl']   = $val['ImgUrl'];
            		$hash['clickUrl'] = (string)$val['Url'];
            	}
            	$this->returnJsonData(200, $hash);
            }else{
            	$this->returnJsonData(404);
            }
      }
      
      //给用户发送验证码
      public function sendVerifyCode() {
            $postData	= I('request.');
	    $this->wInfoLog('发送验证码,IP:'.get_ip());
	    $this->wInfoLog($postData,'接收参数=>');
	    
	    $register_phone = trim($postData['phone']);
	    if(empty($register_phone)) {
	    	$this->returnJsonData(300);
	    }
	    $check_phone_status = check_phone($register_phone);
	    if(!($check_phone_status)) {
	    	$this->returnJsonData(1003);
	    }
	    
	    $code = mt_rand(1000, 9999);
	    $content = 'XXXXX验证码('.$code.')';
	    $sendVerify = D('Comm')->sendShortMsg($register_phone, $content, 'artisans_register');
	    if($sendVerify['SendMessageResult']['Resultcode'] == '00') {
	    	$cdate = date('Y-m-d H:i:s');
	    	$edate = date('Y-m-d H:i:s', time()+1800);
	    	
	    	$where = array('Phone'=>$register_phone, 'Source'=>0);
	    	M('cut_captcha')->where($where)->delete();
	    	
	    	$data = array(
	    		'Phone'=>$register_phone,
	    		'Captcha'=>$code,
	    		'CreaterTime'=>$cdate,
	    		'LoseTime'=>$edate,
	    		'Source'=>0
	    	);
	    	$id = M('cut_captcha')->add($data);
	    	if($id) {
	    		$this->returnJsonData(200);
	    	}
	    }
	    $this->returnJsonData(500);
      }
      
      //查看验证码
      public function checkVerifyCode() {
            $postData = I('request.');
            $this->wInfoLog('发送验证码,IP:'.get_ip());
	    $this->wInfoLog($postData,'接收参数=>');
	    
	    $register_phone = trim($postData['phone']);
	    $code	    = trim($postData['code']);
	    if(!($register_phone && $code)) {
	    	$this->returnJsonData(300);
	    }
	    $check_phone_status = check_phone($register_phone);
	    if(!($check_phone_status)) {
	    	$this->returnJsonData(1003);
	    }
	    
	    $where = array('Phone'=>$register_phone, 'Captcha'=>$code, 'Source'=>0);
	    $edate = M('cut_captcha')->where($where)->getField('LoseTime');
	    if($edate) {
	    	$second = time()-strtotime($edate);
	    	if($second > 0) {
	    		$this->returnJsonData(1004);
	    	}else{
	    		$this->returnJsonData(200);
	    	}
	    }
	    $this->returnJsonData(500);
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
	        $postData	= I('request.');
		$this->wInfoLog('登陆接口,IP:'.get_ip());
		$this->wInfoLog($postData,'接收参数=>');
		
		if(isset($postData['phone']) && isset($postData['code']) && isset($postData['login_time'])) {
			if(!check_phone($postData['phone'])) {
				$this->returnJsonData(1003);
			}
			
			$where['Phone']   = $postData['phone'];
			$where['Captcha'] = $postData['code'];
			$where['Source']  = 0;
			
			$codeStatus 	  = M('cut_captcha')->where($where)->find();
			
			if($codeStatus && $codeStatus['LoseTime']<date('Y-m-d H:i:s')) {
				$this->returnJsonData(1004);
			}
			if(!$codeStatus) {
				$this->returnJsonData(508);
			}
			if($codeStatus) {
				//用户注册
				$base_info_s = array(
					'source_from'=>'app',
					'user_type'=>'other',
					'type'=>200,
					'username'=>$postData['phone']
				);
				
				//注册用户中心
				$user_center_info = send_curl(C('ArtisansApi').'/UserCenterApi/loginUser', $base_info_s);
				$parse_data	  = json_decode($user_center_info, true);
				
				if($parse_data['code']!=200) {
					$this->returnJsonData(500);
				}
				if(isset($postData['cid'])) {
					$param = array(
						'cid'=>$postData['cid'],
						'uid'=>$parse_data['data']['uid'],
						'login_time'=>$postData['login_time']
					);
					$isBind = $this->uiBindCid($param);
					if($isBind['status']==200) {
						$data['user_id'] = $isBind['data']['user_id'];
						$data['cadate']  = $isBind['data']['cdate'];
					}
				}else{
					$data['user_id'] = $parse_data['data']['uid'];
					$data['cadate']  = $isBind['data']['cdate'];
				}
				$this->returnJsonData(200, $data);
			}
		}else{
			$this->returnJsonData(300);
		}
	 }
	 
	 //uid绑定cid 接口
	 public function uidBindCid($param=null) {
	       if($param) {
	       	  $postData = $param;
	       }else{
	       	  $postData=I('request.');
		  $this->wInfoLog('uid绑定cid的接口,IP:'.get_ip());
		  $this->wInfoLog($postData,'接收参数=>');
	       }
	       
	       if (isset($postData['cid']) && isset($postData['uid']) && isset($postData['login_time'])) {
	       	         $info = M('crt_uid_cid')->where(array('Uid'=>$postData['uid']))->select();
	       	         if(!$info) {
	       	         	//新设备登陆
	       	         	$old_login_cid  = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'State'=>1))->getField('Cid');
	       	         	$old_change_cid = M('crt_uid_cid')->where(array('Uid'=>$postData['uid'], 'State'=>1))->save(array('State'=>0));
	       	         	//走极光推送
	       	         	$array['platform'] 	= $data['platform'] ? $data['platform'] : 'all';
	       	         	$array['audience'] 	= "{'registration_id':{$old_change_cid}}";
	       	         	$array['msg_content']   = '你的账号在另一台设备登陆';
	       	         	$array['title']		= '标题';
	       	         	$array['content_type']  = '类型';
	       	         	$array['extras']	= json_encode(array('type'=>'30', 'uid'=>$postData['uid'], 'code'=>(string)$postData['login_time']));
	       	         	
	       	         	$url			= C('JPUSH_API');
	       	         	list($t1, $t2)		= explode(' ', microtime());
	       	         	$start 			= (float)sprintf('%.0f', (floatval($t1)+floatval($t2))*1000);
	       	         	file_put_contents('/share/weixinLog/artisans/jpush.txt', 'start:'.$start, FILE_APPEND);
	       	         	$ret		        = send_curl($url, $array);
	       	         	$result 		= json_decode($ret);
	       	         	$res  			= (array)$result;
	       	         	if(res['isok']) {
	       	         		list($t1, $t2) = explode(' ', microtime());
	       	         		$end = (float)sprintf('%.0f', (floatval($t1) + floatval($t2))*1000);
	       	         		file_put_contents('/share/weixinLog/artisans/jpush.txt', 'end:'.$end, FILE_APPEND);
	       	         	}
	       	         	
	       	         	//为新设备添加绑定
	       	         	$add['Uid'] 	    = $postData['uid'];
	       	         	$add['Cid'] 	    = $postData['cid'];
	       	         	$add['CreaterTime'] = date('Y-m-d H:i:s');
	       	         	$add['State']	    = 1;
	       	         	$add['CidType']     = 0;
	       	         	$add['Code']        = $postData['login_time'];
	       	         	$res		    = M('crt_uid_cid')->add($add);
	       	         }
	       }
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
