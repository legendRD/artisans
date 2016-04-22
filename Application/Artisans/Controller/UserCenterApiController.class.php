<?php
namespace Artisans\Controller;
class UserCenterApiController extends CommonController {
      private $_get_token         = "http://localhost/user/auth";             //获取接口token地址
      private $_reg_url           = "http://localhost/user/register";         //用户注册接口
      private $_login_url         = "http://localhost/user/login";            //用户登录
      private $_userinfo_url      = "http://localhost/user/getByUid";         //用户信息
      private $_updateinfo_url    = "http://localhost/user/updateUser";       //更新用户信息
      private $_thirdlogin_url    = "http://localhost/user/login";            //第三方登录
      private $_update_passwd_url = "http://localhost/user/changePassword";   //修改密码
      private $_deleteinfo_url    = "http://localhost/user/delUser";          //删除用户
      private $_scrypt_pwd        = 'XXX';
      
      public function _initialize() {
             //获取token
             $this->_access_token = D('Token')->getUserCenterToken($this->_get_token);
      }
      
      public function regUserBy() {
             $post_string = I('request.pos_str');
             $exit_type   = 'json';
             $key         = $this->_scrypt_pwd;
             $model       = new \Artisans\Org\Scrypt($key);
             $de_string   = $model->decrypt_base64($post_string);
             parse_str($de_string, $post_data);
             
             $data['username']	        = $username    = $post_data['username'];
        		 $data['password']	        = $password	   = $post_data['password'];
        		 $data['source_from']       = $source_from = $post_data['source_from'];	//app,web,weixin
        		 $data['user_type']	        = $user_type	 = $post_data['user_type'];	  //other
        		 $data['third_part_key']		= $user_type;	                              //other
        		 $data['third_part_value']	= $username;	                              //other
        		 
        		 $reg_type = $post_data['type'];  //注册类型，100普通用户注册，200第三方用户注册
        		 if($reg_type == 200) {
        		      if(!($user_type && $source_from && $username)) {
        		            $this->returnJsonData(300);
        		      }
        		      $hash = $this->_thirdReg($data);
        		      wlog('/share/weixinLog/artisans/user_center_api/reduser_api_'.date('Ymd').'.log', $hash);
        		 }else{
        		      if(!($username && $password && $source_from && $user_type)) {
        		           $this->returnJsonData(300);
        		      }
        		      $hash = $this->_normalReg($data);
        		 }
        		 unset($data);
        		 if(is_array($hash) && $hash) {
        		      $code	= $hash['error_code'];
			            $json_data['uid']	= $hash['data']['uid'];
			            $json_data['username']	= $hash['data']['username'];
			            $this->returnJsonData($code,$json_data);
        		 }else{
        		      $this->returnJsonData(300);
        		 }
      }
      
      public function returnJsonData($code, $data = array(), $msg = '') {
             switch($code) {
             	    	case 200:
				$hash['code']	= 200;
				$hash['message']= 'success';
				$hash['data']	= $data;
				break;
			case 300:
				$hash['code']	= 300;
				$hash['message']= 'Lack of parameter';
				break;
			case 1000:
				$hash['code']	= 1000;
				$hash['message']= 'no permissions';
				break;
			case 10000:
				$hash['code']	= 10000;
				$hash['message']= '非法的域';
				break;
			case 10002:
				$hash['code']	= 10002;
				$hash['message']= '认证失败';
				break;
			case 10003:
				$hash['code']	= 10003;
				$hash['message']= '缺少必填参数';
				break;
			case 10004:
				$hash['code']	= 10004;
				$hash['message']= '密码格式错误';
				break;
			case 10005:
				$hash['code']	= 10005;
				$hash['message']= '用户名已存在';
				break;
			case 10006:
				$hash['code']	= 10006;
				$hash['message']= '用户名不存在';
				break;
			case 10007:
				$hash['code']	= 10007;
				$hash['message']= '密码错误';
				break;
			case 10008:
				$hash['code']	= 10008;
				$hash['message']= '错误的 source_from';
				break;
			case 10009:
				$hash['code']	= 10009;
				$hash['message']= '注册失败';
				break;
			case 10011:
				$hash['code']	= 10011;
				$hash['message']= 'Third party account and user name does not match';
				break;
			case 10012:
				$hash['code']	= 10012;
				$hash['message']= 'uid不存在';
				break;
			case 10013:
				$hash['code']	= 10013;
				$hash['message']= '第三方type为空';
				break;
			default:
				$hash['code']	= 500;
				$hash['message']= 'server fail';
				break;
             }
             if(I('get.parse') == 'echo_info') {
             	pp($hash);
             }
             echo json_encode($hash);
             exit();
      }
      
      public function regUserBy() {
      	     $post_string = I('request.pos_str');
      	     $key = $this->_scrypt_pwd;
      	     $model = new \Artisans\Org\Scrypt($key);
      	     $de_string = $model->decrypt_base64($post_string);
      	     parse_str($de_string, $post_data);
      	     
      	     $data['username']	  = $username = $post_data['username'];
	     $data['password']	  = $password	= $post_data['password'];
	     $data['source_from'] = $source_from= $post_data['source_from'];	//app,web,weixin
	     $data['user_type']	  = $user_type	= $post_data['user_type'];	//other
	     $data['third_part_key']		= $user_type;	//other
	     $data['third_part_value']	= $username;	//other
	     $reg_type	= $post_data['type'];	//注册类型，100普通用户注册，200第三方用户注册
	     
	     if($reg_type == 200) {
	     	        if(!($user_type && $source_from && $username)){
				$this->returnJsonData(300);
			}
			$hash = $this->_thirdReg($data);
			wlog('/share/weixinLog/artisans/user_center_api/reguser_api_'.date('Ymd').'.log', $hash);
	     }else{
	     		if(!($username && $password && $source_from && $user_type)) {
	     			$this->returnJsonData(300);
	     		}
	     		$hash = $this->_normalReg($data);
	     }
	     unset($data);
	     if(is_array($hash) && $hash) {
	     	         $code = $hash['error_code'];
	     	         $json_data['uid'] = $hash['data']['uid'];
	     	         $json_data['username'] = $hash['data']['username'];
	     	         $this->returnJsonData($code, $json_data);
	     }else{
	     	$this->returnJsonData(300);
	     }
      }
      
      //用户注册
      public function regUser() {
             $post_data	= I('post.');
	     $data['username']	= $username = $post_data['username'];
	     $data['password']	= $password	= $post_data['password'];
	     $data['source_from']= $source_from= $post_data['source_from'];	//app,web,weixin
             $data['user_type']	= $user_type	= $post_data['user_type'];	//other
	     $data['third_part_key']		= $user_type;	//other
             $data['third_part_value']	= $username;	//other
	     $reg_type	= $post_data['type'];	//注册类型，100普通用户注册，200第三方用户注册
	     
	     if($reg_type == 200) {
	     	if(!($user_type && $source_from && $username)) {
	     		$this->returnJsonData(300);
	     	}
	     	$hash = $this->_thirdReg($data);
	     	wlog('/share/weixinLog/artisans/user_center_api/reguser_api_'.date('Ymd').'.log', $hash);
	     }else{
	     	if(!($username && $password && $source_from && $user_type)) {
	     		$this->returnJsonData(300);
	     	}
	     	$hash = $this->_normalReg($data);
	     }
	     unset($data);
	     if(is_array($hash) && $hash) {
	     	$code = $hash['error_code'];
	     	$json_data['uid'] = $hash['data']['uid'];
	     	$json_data['username'] = $hash['data']['username'];
	     	$this->returnJsonData($code, $json_data);
	     }else{
	     	$this->returnJsonData(300);
	     }
      }
      
      //用户登录
      public function loginUser() {
      	     $post_data = I('request.');
      	     $data['username']    = $username    = $post_data['username'];
      	     $data['password']    = $password    = $post_data['password'];
      	     $data['user_type']   = $user_type   = $post_data['user_type'];
      	     $data['source_from'] = $source_from = $post_data['source_from'];
      	     $data['third_part_key']   = $user_type;
      	     $data['third_part_value'] = $username;
      	     $login_type                         = $post_data['type'];	//注册类型，100普通用户，200第三方用户
      	     if($login_type == 200){
      	     	if(!($user_type && $source_from && $username)) {
      	     		$this->returnJsonData(300);
      	     	}
      	     	$hash = $this->_thirdReg($data);
      	     }else{
      	     	if(!($username && $passwrod && $source_from && $user_type)) {
      	     		$this->returnJsonData(300);
      	     	}
      	     	$hash = $this->_userLogin($data);
      	     }
      	     if(is_array($hash) && $hash) {
      	     	$code = $hash['error_code'];
      	     	$json_data['uid']      = $hash['data']['uid'];
      	     	$json_data['username'] = $hash['data']['username'];
      	     	$this->returnJsonData($code, $json_data);
      	     }else{
      	     	$this->returnJsonData(300);
      	     }
      }
      
      //获取用户信息
      public function getUserInfo(){
      	     $post_data = I('request.');
      	     $data['uid'] = $post_data['uid'];
      	     if(empty($data['uid'])) {
      	     	$this->returnJsonData(300);
      	     }
      	     $data['access_token'] = $this->_access_token;
      	     $post_data = json_encode($data);
      	     unset($data);
      	     $receive = send_curl($this->userinfo_url, $post_data);
      	     $parse_data = json_decode($receive, true);
      	     
      	     if(is_array($parse_data) && $parse_data) {
      	     	$code = $parse_data['error_code'];
      	     	$hash = $parse_data['data'];
      	     	$this->returnJsonData($code, $hash);
      	     }else{
      	     	$this->returnJsonData(500);
      	     }
      }
      
      //更新用户信息
      public function updateUserInfo() {
      	     $post_data = I('request.');
      	     $data      = $post_data;
      	     $data['uid'] = $post_data['uid'];
      	     if(empty($data['uid'])) {
      	     	$this->returnJsonData(300);
      	     }
      	     $data['access_token'] = $this->_access_token;
      	     $post_data = json_encode($data);
      	     unset($data);
      	     $receive = send_curl($this->_updateinfo_url, $post_data);
      	     $parse_data = json_decode($receive, true);
      	     
      	     if(is_array($parse_data) && $parse_data) {
      	     	         $code = $parse_data['error_code'];
      	     	         $hash = $parse_data['data'];
      	     	         $this->returnJsonData($code, $hash);
      	     }else{
      	     	         $this->returnJsonData(500);
      	     }
      }
      
      //删除用户
      public function delUserInfo() {
      	     $post_data = I('request.');
      	     $data = $post_data;
      	     $data['uid'] = $post_data['uid'];
      	     if(empty($data['uid'])) {
      	     	$this->returnJsonData(300);
      	     }
      	     $data['access_token'] = $this->_access_token;
      	     $post_data = json_encode($data);
      	     unset($data);
      	     $receive = send_curl($this->_deleteinfo_url, $post_data);
      	     $parse_data = json_decode($receive, true);
      	     
      	     if(is_array($parse_data) && $parse_data) {
      	     	$code = $parse_data['error_code'];
      	     	$hash = $parse_data['data'];
      	     	$this->returnJsonData($code, $hash);
      	     }else{
      	     	$this->returnJsonData(500);
      	     }
      }
      
      //重置密码
      public function instPasswd() {
      	     $post_data = I('request.');
      	     $data['uid'] = $post_data['uid'];
      	     $data['repassword'] = $post_data['repassword'];
      	     if(!($data['uid'] && $data['repassword'])) {
      	     	$this->returnJsonData(300);
      	     }
      	     $data['access_token'] = $this->_access_token;
      	     $post_data = json_encode($data);
      	     unset($data);
      	     $receive = send_curl($this->_update_passwd_url, $post_data);
      	     $parse_data = json_decode($receive, true);
      	     
      	     if(is_array($parse_data) && $parse_data) {
      	     	$code = $parse_data['error_code'];
      	     	$hash = $parse_data['data'];
      	     	$this->returnJsonData($code, $hash);
      	     }else{
      	     	$this->returnJsonData(500);
      	     }
      }
      
      //普通用户注册
      private function _normalReg($data = array()) {
      	      if(empty($data)) {
      	      	       return false;
      	      }
      	      $request_data['username'] = $data['username'];
      	      $request_data['password'] = $data['password'];
      	      $request_data['source_from'] = $data['source_from'];
      	      $request_data['access_token'] = $this->_access_token;
      	      $request_data['user_type'] = $data['user_type'];
      	      $post_data = json_encode($request_data);
      	      unset($request_data);
      	      $receive = send_curl($this->_reg_url, $post_data);
      	      $parse_data = json_decode($receive, true);
      	      return $parse_data;
      }
      
      //第三方用户注册
      private function _thirdReg($data = array()) {
      	      if(empty($data)) {
      	      		return false;
      	      }
      	      $third_data = $this->_thirdLogin($data);
      	      //第三方用户名需要加密
      	      /*$len = strlen($third_data['username']);
      	      for($i = 0; $i < $len; $i++) {
      	      	  $ascii += ord($third_data['username'][$i]);
      	      }
      	      $username = sha1($ascii);
      	      $request_data['username'] = $username;*/
      	      $request_data['username'] = $third_data['username'];
      	      $request_data['user_type'] = $data['user_type'];
      	      $request_data['source_from'] = $data['source_from'];
      	      $request_data['third_part_key'] = $data['third_part_key'];
      	      $request_data['third_part_value'] = $third_data['third_name'];
      	      $third_login_data = $this->_userLogin($request_data);
      	      return $third_login_data;
      }
      
      //第三方登录
      private function _thirdLogin($data = array()) {
      	
      }
      
}
